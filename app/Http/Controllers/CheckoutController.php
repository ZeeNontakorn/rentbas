<?php

namespace App\Http\Controllers;

use App\Mail\BookingCancelledByAdminMail;
use App\Mail\BookingConfirmedCreditMail;
use App\Mail\BookingReceiptMail;
use App\Models\Booking;
use App\Models\CourtSection;
use App\Models\Notification;
use App\Services\CreditService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * รวม flow เช็คเอาต์ทั้งหมดไว้ที่เดียว (เดิมแยกเป็น BookingCheckoutService ต่างหาก)
 *
 * เหตุผลที่รวมกลับมาเป็นไฟล์เดียว: method พวกนี้ถูกเรียกจาก controller นี้ที่เดียว
 * ไม่มีที่อื่นเรียกใช้ซ้ำ การแยกเป็น service จึงเพิ่มไฟล์/เพิ่ม indirection โดยไม่ได้
 * ประโยชน์เรื่อง reuse จริง — ต่างจาก PricingService/CreditService ที่ถูกเรียกจาก
 * มากกว่า 1 จุด (เช่น CreditService ใช้ทั้งใน checkout และหน้า admin เติมเครดิต)
 * จึงยังคงแยกเป็น service ไว้เหมือนเดิม
 *
 * หมายเหตุเรื่อง hosting (FTP, ไม่มี CLI/SSH/cron):
 * - ไม่มี background job / queue worker ให้ใช้ → ส่งอีเมลแบบ synchronous (->send() ตรงๆ)
 *   ไม่ใช้ ->queue() เพื่อไม่ต้องพึ่ง `php artisan queue:work`
 * - ไม่มี scheduled command ให้ตั้งเวลาปล่อยสล็อตที่ล็อกไว้เกิน 15 นาที → ใช้วิธี
 *   "lazy expiration" แทน: releaseStaleLocks() เป็น bulk UPDATE คิวรีเดียว ไม่มี loop
 *   ไม่ส่งอีเมล เรียกแบบ opportunistic ตอนมีคนเข้าหน้าเช็คเอาต์/ปฏิทินจอง แค่เพื่อความ
 *   สวยงามของสถานะที่แสดงผล (history/admin) เท่านั้น — ความถูกต้องของระบบไม่ได้พึ่งพา
 *   ขั้นตอนนี้เลย เพราะ scopeOverlappingSection() ใน Booking model ก็ไม่นับสล็อตที่ล็อก
 *   หมดอายุแล้วว่าเป็น "ไม่ว่าง" อยู่แล้ว และ payWithCredit() ก็เช็คหมดอายุซ้ำก่อนหักเงินทุกครั้ง
 */
class CheckoutController extends Controller
{
    public const LOCK_MINUTES = 15;

    public function __construct(
        protected PricingService $pricingService,
        protected CreditService $creditService,
    ) {
    }

    /**
     * คำนวณราคาล่วงหน้าแบบ real-time (ก่อนกดจอง) — ใช้ตอนผู้ใช้เลือกเวลา/แพ็กเกจในหน้า UI
     * ไม่ล็อกสล็อต ไม่สร้าง booking แค่ preview ราคาเท่านั้น
     */
    public function quote(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'court_type' => ['required', 'in:full,half'],
            'promotion_code' => ['nullable', 'string'],
        ]);

        try {
            $result = $this->pricingService->calculate($data);
        } catch (RuntimeException|\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    /**
     * ขั้นที่ 1: ผู้ใช้เลือกเวลาเสร็จ กด "ดำเนินการจอง" -> ล็อกสล็อต 15 นาที + คำนวณราคาจริง
     * แล้วพาไปหน้าเลือกวิธีชำระเงิน (credit / promptpay)
     */
    public function reserve(Request $request)
    {
        $data = $request->validate([
            'court_section_id' => ['required', 'integer', 'exists:court_sections,id'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'promotion_code' => ['nullable', 'string'],
        ]);

        // เก็บกวาดสล็อตที่ล็อกไว้เกิน 15 นาทีแบบ opportunistic ก่อนเช็คทับซ้อน
        // (ไม่จำเป็นต่อความถูกต้อง แค่ทำให้สถานะในตารางสวยขึ้น ดู docblock บนสุดของไฟล์)
        self::releaseStaleLocks();

        $section = CourtSection::findOrFail($data['court_section_id']);

        try {
            $booking = DB::transaction(function () use ($request, $section, $data) {
                $conflictIds = $section->conflictingSectionIds();

                // ล็อกแถวที่เกี่ยวข้องทั้งหมดก่อนเช็คทับซ้อน กันสอง request แข่งกันอ่านค่าที่ยังไม่ commit
                $overlap = Booking::whereIn('court_section_id', $conflictIds)
                    ->whereDate('booking_date', $data['booking_date'])
                    ->where(function ($q) use ($data) {
                        $q->where('start_time', '<', $data['end_time'])
                            ->where('end_time', '>', $data['start_time']);
                    })
                    ->where(function ($q) {
                        $q->whereIn('status', ['pending', 'approved'])
                            ->orWhere(function ($qq) {
                                $qq->where('status', 'pending_payment')->where('locked_until', '>', now());
                            });
                    })
                    ->lockForUpdate()
                    ->exists();

                if ($overlap) {
                    throw new RuntimeException('ช่วงเวลานี้เพิ่งถูกจองหรือถูกล็อกไปแล้ว กรุณาเลือกเวลาอื่น');
                }

                $courtType = $section->code === 'full' ? 'full' : 'half';
                $priceResult = $this->pricingService->calculate([
                    'date' => $data['booking_date'],
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'court_type' => $courtType,
                    'promotion_code' => $data['promotion_code'] ?? null,
                ]);

                return Booking::create([
                    'user_id' => $request->user()->id,
                    'court_id' => $section->court_id,
                    'court_section_id' => $section->id,
                    'booking_date' => $data['booking_date'],
                    'start_time' => $data['start_time'] . ':00',
                    'end_time' => $data['end_time'] . ':00',
                    'status' => 'pending_payment',
                    'booking_source' => 'manual', // เปลี่ยนเป็น credit/promptpay จริงตอนเลือกวิธีจ่ายเงิน
                    'payment_status' => 'unpaid',
                    'price' => $priceResult['total'],
                    'pricing_rule_id' => $priceResult['pricing_rule_id'],
                    'promotion_package_id' => $priceResult['promotion_package_id'],
                    'price_breakdown' => $priceResult['breakdown'],
                    'locked_until' => now()->addMinutes(self::LOCK_MINUTES),
                ]);
            });
        } catch (RuntimeException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }

        return redirect()->route('checkout.show', $booking)->with('locked_until', $booking->locked_until);
    }

    /**
     * หน้าเลือกวิธีชำระเงิน แสดง countdown 15 นาที + ราคาที่คำนวณไว้
     */
    public function show(Booking $booking, Request $request)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        if ($booking->status !== 'pending_payment' || ($booking->locked_until && $booking->locked_until->isPast())) {
            return redirect()->route('booking.index')->withErrors(['booking' => 'รายการนี้หมดเวลาแล้ว กรุณาจองใหม่']);
        }

        return view('checkout.show', compact('booking'));
    }

    /**
     * Option A: ชำระด้วยเครดิต — หักเงิน, auto-approve ทันที, ยกเลิก manual booking ที่ทับซ้อน,
     * ส่งอีเมลยืนยัน+ใบเสร็จ (synchronous — ไม่มี queue worker ให้ใช้บน hosting นี้)
     */
    public function payWithCredit(Booking $booking, Request $request)
    {
        try {
            $confirmed = DB::transaction(function () use ($booking, $request) {
                $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

                if ($locked->status !== 'pending_payment') {
                    throw new RuntimeException('รายการนี้ไม่ได้อยู่ในสถานะรอชำระเงิน (อาจหมดเวลาไปแล้ว)');
                }

                if ($locked->locked_until && $locked->locked_until->isPast()) {
                    $locked->update(['status' => 'expired', 'payment_status' => 'failed']);
                    throw new RuntimeException('หมดเวลาชำระเงิน (15 นาที) กรุณาทำการจองใหม่');
                }

                abort_unless($locked->user_id === $request->user()->id, 403);

                $this->creditService->deductForBooking($request->user(), $locked);

                $locked->update([
                    'status' => 'approved',
                    'booking_source' => 'credit',
                    'payment_method' => 'credit',
                    'payment_status' => 'paid',
                    'locked_until' => null,
                ]);

                $this->rejectOverlappingManualBookings($locked);

                return $locked->fresh();
            });
        } catch (RuntimeException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        if ($confirmed->user?->email) {
            Mail::to($confirmed->user->email)->send(new BookingConfirmedCreditMail($confirmed));
            Mail::to($confirmed->user->email)->send(new BookingReceiptMail($confirmed));
        }

        return redirect()->route('history')
            ->with('success', "ชำระเงินสำเร็จ! การจอง #{$confirmed->id} ได้รับการยืนยันแล้ว");
    }

    /**
     * Option B: [WIP] QR PromptPay mock — ยังไม่มี auto-verification จริง
     */
    public function payWithPromptpay(Booking $booking, Request $request)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        if ($booking->status !== 'pending_payment') {
            return back()->withErrors(['payment' => 'รายการนี้ไม่ได้อยู่ในสถานะรอชำระเงิน']);
        }

        $booking->update([
            'booking_source' => 'promptpay',
            'payment_method' => 'promptpay',
            'payment_status' => 'pending_slip',
            // สถานะ booking ยังคง pending_payment จนกว่าแอดมินจะตรวจสลิปแล้วเปลี่ยนเป็น pending (manual queue)
            // TODO: ส่วนอัปโหลดสลิป/ตรวจสอบอัตโนมัติ ยังไม่ implement ในเฟสนี้
        ]);

        return view('checkout.promptpay-mock', ['booking' => $booking->fresh()]);
    }

    /**
     * เมื่อ credit booking ผ่านและอนุมัติอัตโนมัติแล้ว ให้ยกเลิก booking แบบ manual (รออนุมัติ)
     * ที่ยังค้างอยู่และทับซ้อนช่วงเวลา/section เดียวกัน เพราะ credit flow มี priority สูงกว่า
     * Manual Admin Approval เสมอ
     */
    protected function rejectOverlappingManualBookings(Booking $confirmed): void
    {
        $section = $confirmed->courtSection ?? $confirmed->court->defaultSection();
        $conflictIds = $section ? $section->conflictingSectionIds() : [$confirmed->court_id];

        $overlapping = Booking::whereIn('court_section_id', $conflictIds)
            ->whereDate('booking_date', $confirmed->booking_date)
            ->where('id', '!=', $confirmed->id)
            ->where('status', 'pending')
            ->where('booking_source', 'manual')
            ->where(function ($q) use ($confirmed) {
                $q->where('start_time', '<', $confirmed->end_time)
                    ->where('end_time', '>', $confirmed->start_time);
            })
            ->get();

        foreach ($overlapping as $manualBooking) {
            $manualBooking->update([
                'status' => 'rejected',
                'reject_reason' => 'ระบบยกเลิกอัตโนมัติ เนื่องจากมีผู้ใช้อื่นจองและชำระเงินในช่วงเวลาเดียวกันสำเร็จก่อน',
            ]);

            Notification::create([
                'user_id' => $manualBooking->user_id,
                'title' => 'การจองถูกปฏิเสธอัตโนมัติ',
                'message' => "การจอง {$manualBooking->court->name} วันที่ {$manualBooking->booking_date->toDateString()} "
                    . 'ถูกยกเลิกอัตโนมัติ เนื่องจากมีผู้ใช้อื่นชำระเงินในช่วงเวลาเดียวกันสำเร็จก่อนคุณ',
            ]);

            if ($manualBooking->user?->email) {
                Mail::to($manualBooking->user->email)->send(new BookingCancelledByAdminMail(
                    $manualBooking,
                    'ระบบยกเลิกอัตโนมัติ เนื่องจากมีการชำระเงินอื่นสำเร็จก่อนในช่วงเวลาเดียวกัน'
                ));
            }
        }
    }

    /**
     * ปล่อยสล็อตที่ล็อกไว้เกิน 15 นาทีแล้วยังไม่จ่ายเงิน — bulk UPDATE คิวรีเดียว ไม่ loop ไม่ส่งเมล
     * (ไม่กระทบผู้ใช้ที่กำลังจ่ายอยู่ เพราะ payWithCredit ล็อกแถวและเช็คสถานะซ้ำเองอยู่แล้ว)
     *
     * ไม่ต้องมี cron — เรียกแบบ opportunistic จากหน้าที่คนเข้าบ่อย (เช่น reserve() ด้านบน,
     * หรือหน้าปฏิทินจอง/ประวัติการจอง) แค่เพื่อให้สถานะที่แสดงผลดูสะอาด ไม่ค้างเป็น
     * "รอชำระเงิน" ตลอดไปในสายตาแอดมิน
     */
    public static function releaseStaleLocks(): void
    {
        Booking::where('status', 'pending_payment')
            ->where('locked_until', '<', now())
            ->update(['status' => 'expired']);
    }
}
