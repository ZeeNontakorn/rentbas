<?php

namespace App\Http\Controllers;

use App\Mail\AdminPaymentReceivedMail;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSection;
use App\Models\Notification;
use App\Models\PrivateTrainingBooking;
use App\Models\PromotionPackage;
use App\Models\User;
use App\Services\CreditService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class PrivateTrainingController extends Controller
{
    /** ช่วงเวลาเปิดให้จองต่อวัน */
    private const OPEN_HOUR = 8;
    private const CLOSE_HOUR = 22;

    public function __construct(
        protected PricingService $pricingService,
        protected CreditService $creditService,
    ) {
    }

    public function index(Request $request)
    {
        $search = $request->query('search');

        $coaches = User::where('role', 'staff')
            ->where('membership_type', 'coach')
            ->with('staffProfile')
            // ใช้ nested closure (fn($q) และ fn($qq)) เพื่อสร้างวงเล็บคลุมเงื่อนไข OR ป้องกันการคลาดเคลื่อนกับเงื่อนไขหลักด้านบน
            ->when($search, fn($q) => $q->where(
                fn($qq) => $qq->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->get();

        $myRequests = PrivateTrainingBooking::with('coach')
            ->where('user_id', $request->user()->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->orderByDesc('created_at')
            ->get();

        return view('private-training.index', compact('coaches', 'search', 'myRequests'));
    }

    public function show(User $coach)
    {
        $this->assertIsCoach($coach);

        $today = now()->toDateString();
        $maxDate = now()->addDays(\App\Http\Controllers\CheckoutController::ADVANCE_BOOKING_DAYS)->toDateString();

        $myUpcoming = PrivateTrainingBooking::where('coach_id', $coach->id)
            ->where('user_id', auth()->id())
            ->whereDate('date', '>=', $today)
            ->whereIn('status', ['pending', 'awaiting_court', 'confirmed'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        // แพ็กเกจโปรโมชั่นที่เปิดใช้งาน ให้ลูกค้าเลือกตอนส่งคำขอ — filter เฉพาะหมวดหมู่ 'private'
        // เพราะเป็นแพ็กเกจที่ตั้งใจไว้สำหรับ Private Training เท่านั้น (แพ็กเกจหมวด personal/group
        // เป็นของการจองสนามปกติ ไม่เกี่ยวข้องกับ flow นี้ ไม่ควรเอามาให้เลือกปนกัน)
        // ส่วนเงื่อนไขระยะเวลา (duration_hours) จะเช็คแบบ real-time ฝั่ง client ตอนลูกค้าลากเลือก
        // ช่วงเวลาในปฏิทิน (ดู select() ใน <script> ด้านล่างของหน้านี้) เพราะยังไม่รู้ระยะเวลาจนกว่า
        // จะเลือกช่วงเวลา และจะเช็คเงื่อนไขทั้งหมดอีกครั้งจริงจังตอนแอดมินจัดสนามให้ ณ ตอนที่รู้
        // court_type แล้ว — ดู PrivateTrainingController::assignCourt())
        $promotionPackages = PromotionPackage::where('is_active', true)
            ->where('category', 'private')
            ->orderBy('label')
            ->get();

        // ใช้เครื่องหมาย + (Array Union) เพื่อนำ array ธรรมดาไปต่อท้ายผลลัพธ์ที่ได้จากฟังก์ชัน compact()
        return view('private-training.show', compact('coach', 'today', 'maxDate', 'myUpcoming', 'promotionPackages') + [
            'staffProfile' => $coach->staffProfile,
        ]);
    }

    public function scheduleEvents(Request $request, User $coach)
    {
        $this->assertIsCoach($coach);
        $range = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);

        $from = Carbon::parse($range['start']);
        $until = Carbon::parse($range['end']);
        $viewerIsCoach = $request->user()->id === $coach->id;

        $availableEvents = $coach->availabilities()
            ->where('status', 'available')
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<', $until->toDateString())
            ->get()
            ->map(fn (Availability $item) => [
                'title' => 'เปิดรับจอง',
                'start' => $item->date.'T'.substr($item->start_time, 0, 8),
                'end' => $item->date.'T'.substr($item->end_time, 0, 8),
                'display' => 'background',
                'backgroundColor' => '#dcfce7',
                'extendedProps' => ['selectable' => true, 'kind' => 'available'],
            ]);

        $busyEvents = $coach->availabilities()
            ->where('status', 'booked')
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<', $until->toDateString())
            ->get()
            ->unique(fn (Availability $item) => implode('|', [$item->date, $item->start_time, $item->end_time]))
            ->map(fn (Availability $item) => [
                'title' => $viewerIsCoach ? ($item->detail ?: 'ไม่ว่าง') : 'ไม่ว่าง',
                'start' => $item->date.'T'.substr($item->start_time, 0, 8),
                'end' => $item->date.'T'.substr($item->end_time, 0, 8),
                'display' => 'block',
                'backgroundColor' => '#f97316',
                'borderColor' => '#f97316',
                'extendedProps' => ['selectable' => false, 'kind' => 'busy'],
            ]);

        $bookingEvents = PrivateTrainingBooking::with(['user', 'court', 'courtSection'])
            ->where('coach_id', $coach->id)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<', $until->toDateString())
            ->whereIn('status', ['pending', 'awaiting_court', 'confirmed'])
            ->get()
            ->map(function (PrivateTrainingBooking $booking) use ($request, $viewerIsCoach) {
                $mine = $booking->user_id === $request->user()->id;
                $title = $viewerIsCoach
                    ? 'Private: '.$booking->user->name
                    : ($mine ? 'คำขอของคุณ' : 'ไม่ว่าง');
                $color = match ($booking->status) {
                    'confirmed' => '#16a34a',
                    'awaiting_court' => '#7c3aed',
                    default => $mine ? '#3b82f6' : '#94a3b8',
                };

                return [
                    'id' => 'private-'.$booking->id,
                    'title' => $title,
                    'start' => $booking->date->toDateString().'T'.substr($booking->start_time, 0, 8),
                    'end' => $booking->date->toDateString().'T'.substr($booking->end_time, 0, 8),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'extendedProps' => [
                        'selectable' => false,
                        'kind' => 'private_training',
                        'status' => $booking->status,
                        'statusLabel' => $this->statusLabel($booking->status),
                        'court' => $booking->court
                            ? $booking->court->name.($booking->courtSection ? ' — '.$booking->courtSection->name : '')
                            : null,
                    ],
                ];
            });

        return response()->json($availableEvents->concat($busyEvents)->concat($bookingEvents)->values());
    }

    public function mySchedule(Request $request)
    {
        $coach = $request->user();
        $this->assertIsCoach($coach);

        return view('private-training.my-schedule', compact('coach'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'coach_id' => ['required', 'integer', 'exists:users,id'],
            'date' => [
                'required', 'date', 'after_or_equal:today',
                'before_or_equal:' . now()->addDays(\App\Http\Controllers\CheckoutController::ADVANCE_BOOKING_DAYS)->toDateString(),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'note' => ['nullable', 'string', 'max:500'],
            'promotion_code' => ['nullable', 'string'],
        ]);

        $coach = User::findOrFail($data['coach_id']);
        $this->assertIsCoach($coach);

        $date = Carbon::parse($data['date'])->toDateString();
        $openTime = sprintf('%02d:00', self::OPEN_HOUR);
        $closeTime = sprintf('%02d:00', self::CLOSE_HOUR);

        if ($data['start_time'] < $openTime || $data['end_time'] > $closeTime) {
            return back()->withErrors(['start_time' => "สามารถจองได้เฉพาะช่วง {$openTime}–{$closeTime} น. เท่านั้น"]);
        }

        $startsAt = Carbon::parse("{$date} {$data['start_time']}");
        $endsAt = Carbon::parse("{$date} {$data['end_time']}");
        if ($startsAt->lte(now())) {
            return back()->withErrors(['start_time' => 'ไม่สามารถจองช่วงเวลาที่ผ่านมาแล้วได้']);
        }
        if (! in_array($startsAt->minute, [0, 30], true) || ! in_array($endsAt->minute, [0, 30], true)) {
            return back()->withErrors(['start_time' => 'กรุณาเลือกเวลาเป็นช่วงละ 30 นาที']);
        }
        if ($startsAt->diffInMinutes($endsAt) > 240) {
            return back()->withErrors(['end_time' => 'จอง Private Training ได้ไม่เกิน 4 ชั่วโมงต่อคำขอ']);
        }

        $startDb = $data['start_time'] . ':00';
        $endDb = $data['end_time'] . ':00';

        // เช็คแค่ว่า code นี้มีอยู่จริงและเปิดใช้งาน (ยังไม่เช็ค court_type/duration/วันที่ ณ
        // จุดนี้ เพราะยังไม่รู้ court_type จนกว่าแอดมินจะจัดสนามให้ — จะเช็คเงื่อนไขที่เหลือ
        // ทั้งหมดอีกทีตอน assignCourt() ผ่าน PricingService::calculate() ให้ถูกต้องตรงกับ
        // สนามที่ถูกจัดจริง ถ้าใช้ไม่ได้ตอนนั้นแอดมินจะเห็น error แทนการ silently ignore)
        $promotionPackageId = null;
        if (! empty($data['promotion_code'])) {
            $promotionPackageId = PromotionPackage::where('code', $data['promotion_code'])
                ->where('is_active', true)
                ->value('id');

            if (! $promotionPackageId) {
                return back()->withErrors(['promotion_code' => 'รหัสโปรโมชั่นนี้ไม่ถูกต้องหรือถูกปิดใช้งานแล้ว']);
            }
        }

        // ใช้ DB::transaction เพื่อทำ Atomic Operation ถ้ามีข้อผิดพลาดระบบจะ Rollback สิ่งที่ query ไว้ทั้งหมด ป้องกันข้อมูลขยะ
        $error = DB::transaction(function () use ($coach, $date, $startDb, $endDb, $request, $data, $promotionPackageId) {
            $isWithinAvailableSchedule = Availability::where('user_id', $coach->id)
                ->whereDate('date', $date)
                ->where('status', 'available')
                ->where('start_time', '<=', $startDb)
                ->where('end_time', '>=', $endDb)
                ->exists();

            if (! $isWithinAvailableSchedule)
                return 'ช่วงเวลานี้ไม่ได้อยู่ในตารางที่โค้ชเปิดรับจอง';

            $isBusy = Availability::where('user_id', $coach->id)
                ->whereDate('date', $date)
                ->where('status', 'booked')
                ->where('start_time', '<', $endDb)
                ->where('end_time', '>', $startDb)
                ->exists();

            if ($isBusy)
                return 'ช่วงเวลานี้โค้ชติดภารกิจอื่นอยู่ ไม่สามารถจองได้';

            // lockForUpdate() คือ Pessimistic Locking ล็อก row ในฐานข้อมูลจนกว่า Transaction จะจบ
            // ป้องกัน Race Condition กรณีที่มีผู้ใช้ 2 คนกดจองเวลาเดียวกันเป๊ะในระดับเสี้ยววินาที
            $overlap = PrivateTrainingBooking::overlapping($coach->id, $date, $startDb, $endDb)
                ->lockForUpdate()
                ->exists();

            if ($overlap)
                return 'ช่วงเวลานี้มีการจองไปแล้ว กรุณาเลือกช่วงเวลาอื่น';

            // array_merge รวม array ก้อนที่ส่งมาจาก Request เข้ากับข้อมูลที่ถูกบังคับใส่ (ป้องกันการถูก Inject ข้อมูลผิดๆ ผ่านฟอร์ม)
            PrivateTrainingBooking::create(array_merge($data, [
                'user_id' => $request->user()->id,
                'date' => $date,
                'start_time' => $startDb,
                'end_time' => $endDb,
                'status' => 'pending',
                'promotion_package_id' => $promotionPackageId,
            ]));

            return null;
        });

        if ($error)
            return back()->withErrors(['bookings' => $error]);

        $timeLabel = $this->formatTimeRange($data['start_time'], $data['end_time']);
        $this->notifyAdmins('คำขอจองเทรนเนอร์ส่วนตัวใหม่', "คุณ {$request->user()->name} ขอจองเทรนเนอร์ส่วนตัวกับโค้ช {$coach->name} |วันที่ {$date}\nเวลา {$timeLabel}");

        return back()->with('success', 'ส่งคำขอจองเทรนเนอร์ส่วนตัวเรียบร้อย รอแอดมินอนุมัติ');
    }

    public function cancel(Request $request, PrivateTrainingBooking $privateTrainingBooking)
    {
        abort_unless($privateTrainingBooking->user_id === $request->user()->id, 403);

        if (! in_array($privateTrainingBooking->status, ['pending', 'awaiting_court'], true)) {
            return back()->withErrors(['status' => 'รายการนี้ถูกดำเนินการไปแล้ว หรือไม่สามารถยกเลิกได้']);
        }

        if ($privateTrainingBooking->isStarted()) {
            return back()->withErrors(['status' => 'ไม่สามารถยกเลิกรายการที่ถึงเวลาแล้วได้']);
        }

        $privateTrainingBooking->update(['status' => 'cancelled']);
        return back()->with('success', 'ยกเลิกคำขอจองเรียบร้อย');
    }

    /* =====================================================================
     |  ADMIN SECTION
     ===================================================================== */
    public function adminIndex(Request $request)
    {
        $status = $request->query('status', 'pending');
        
        // เก็บค่าเวลาปัจจุบันเพื่อใช้เปรียบเทียบ
        $now = now();
        $currentDate = $now->toDateString();
        $currentTime = $now->toTimeString();

        $bookings = PrivateTrainingBooking::with(['user', 'coach', 'court', 'courtSection'])
            ->when($status && $status !== 'all', function ($q) use ($status, $currentDate, $currentTime) {
                if ($status === 'overdue') {
                    // กรณีดูแท็บ "เลยกำหนด": ดึงรายการที่ค้างอยู่ (pending, awaiting_court) และเวลาล่วงเลยมาแล้ว
                    $q->whereIn('status', ['pending', 'awaiting_court'])
                      ->where(function ($query) use ($currentDate, $currentTime) {
                          $query->where('date', '<', $currentDate)
                                ->orWhere(function ($sq) use ($currentDate, $currentTime) {
                                    $sq->where('date', '=', $currentDate)
                                       ->where('start_time', '<', $currentTime);
                                });
                      });
                } else {
                    // กรณีดูแท็บอื่นๆ: กรองตาม status ที่ส่งมาปกติ
                    $q->where('status', $status);
                    
                    // แต่ถ้าเป็นแท็บที่ยังต้องรอดำเนินการ จะต้อง "ยังไม่เลยกำหนดเวลา" เท่านั้น
                    if (in_array($status, ['pending', 'awaiting_court'])) {
                        $q->where(function ($query) use ($currentDate, $currentTime) {
                            $query->where('date', '>', $currentDate)
                                  ->orWhere(function ($sq) use ($currentDate, $currentTime) {
                                      $sq->where('date', '=', $currentDate)
                                         ->where('start_time', '>=', $currentTime);
                                  });
                        });
                    }
                }
            })
            ->oldest()// oldest() มีค่าเท่ากับ orderBy('created_at', 'asc')
            ->paginate(15)
            ->withQueryString();

        $courts = Court::with(['sections' => fn ($query) => $query->where('is_active', true)])
            ->where('court_status', 'open')
            ->orderBy('name')
            ->get();

        return view('admin.private-training.index', compact('bookings', 'status', 'courts'));
    }
    public function approve(PrivateTrainingBooking $privateTrainingBooking)
    {
        if ($error = $this->checkIfNotPending($privateTrainingBooking))
            return $error;

        // overlapping() เป็น Local Scope ที่นิยามไว้ในโมเดล ช่วยลดความซ้ำซ้อนของการเขียน Query หาเวลาที่ทับซ้อน
        $overlap = PrivateTrainingBooking::overlapping(
            $privateTrainingBooking->coach_id,
            $privateTrainingBooking->date->toDateString(),
            $privateTrainingBooking->start_time,
            $privateTrainingBooking->end_time
        )
            ->whereIn('status', ['awaiting_court', 'confirmed'])
            ->where('id', '!=', $privateTrainingBooking->id)
            ->exists();

        if ($overlap) {
            return back()->withErrors(['status' => 'ไม่สามารถอนุมัติได้ เนื่องจากมีรายการอื่นที่อนุมัติแล้วทับซ้อนช่วงเวลานี้']);
        }

        $privateTrainingBooking->update(['status' => 'awaiting_court']);

        $timeLabel = $this->formatTimeRange($privateTrainingBooking->start_time, $privateTrainingBooking->end_time);
        $this->notifyUser(
            $privateTrainingBooking->user_id,
            'คำขอ Private Training ผ่านการพิจารณา',
            "คำขอกับโค้ช {$privateTrainingBooking->coach->name} วันที่ {$privateTrainingBooking->date->format('d/m/Y')}\nเวลา {$timeLabel}\nกำลังรอแอดมินจัดสนาม"
        );

        return back()->with('success', 'รับคำขอเรียบร้อยแล้ว ขั้นตอนถัดไปกรุณาจัดสนาม');
    }

    public function assignCourt(Request $request, PrivateTrainingBooking $privateTrainingBooking)
    {
        abort_unless($privateTrainingBooking->status === 'awaiting_court', 422, 'รายการนี้ไม่อยู่ในขั้นตอนจัดสนาม');

        $data = $request->validate([
            'court_section_id' => ['required', 'integer', 'exists:court_sections,id'],
        ]);

        $error = DB::transaction(function () use ($request, $privateTrainingBooking, $data) {
            $booking = PrivateTrainingBooking::whereKey($privateTrainingBooking->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($booking->status !== 'awaiting_court') {
                return 'รายการนี้ถูกดำเนินการไปแล้ว';
            }

            $section = CourtSection::with('court')->findOrFail($data['court_section_id']);
            if (! $section->is_active || $section->court->court_status !== 'open') {
                return 'สนามหรือส่วนสนามนี้ไม่ได้เปิดใช้งาน';
            }

            $date = $booking->date->toDateString();
            $start = $booking->start_time;
            $end = $booking->end_time;
            $from = Carbon::parse($date.' '.$start);
            $until = Carbon::parse($date.' '.$end);

            if ($section->court->isClosedAt($from, $until, $section)) {
                return 'สนามนี้ถูกปิดในช่วงเวลาที่เลือก';
            }

            $courtBusy = Booking::overlappingSection($section, $date, $start, $end)
                ->lockForUpdate()
                ->exists();
            $privateBusy = PrivateTrainingBooking::whereKeyNot($booking->id)
                ->whereIn('court_section_id', $section->conflictingSectionIds())
                ->whereDate('date', $date)
                ->where('status', 'confirmed')
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->lockForUpdate()
                ->exists();

            if ($courtBusy || $privateBusy) {
                return 'สนามนี้มีรายการอื่นอยู่ในช่วงเวลาที่เลือก';
            }

            // ตอนนี้รู้ court_type แล้ว (เต็ม/ครึ่งสนาม) — คำนวณราคาจริงตามช่วงเวลาปกติ
            // หรือตามแพ็กเกจโปรโมชั่นที่ลูกค้าเลือกไว้ตอนส่งคำขอ (ถ้ามี)
            $courtType = $section->code === 'full' ? 'full' : 'half';

            try {
                $priceResult = $this->pricingService->calculate([
                    'date' => $date,
                    'start_time' => substr($start, 0, 5),
                    'end_time' => substr($end, 0, 5),
                    'court_type' => $courtType,
                    'promotion_code' => $booking->promotionPackage?->code,
                ]);
            } catch (\InvalidArgumentException $e) {
                return 'คำนวณราคาไม่สำเร็จ: ' . $e->getMessage();
            }

            // ตั้งค่าราคาลงบน instance ในหน่วยความจำก่อน (ยังไม่ save) เพราะ deductForPrivateTraining()
            // อ่านค่าจาก $booking->price โดยตรง — ถ้าไม่ตั้งก่อน ค่านี้จะยังเป็น null (แถวเดิมใน DB
            // ที่ยังไม่เคยมีการจัดสนาม) แล้วไปเรียก decrement('credit_balance', null) ทำให้เกิด
            // InvalidArgumentException: Non-numeric value passed to decrement method
            $booking->price = $priceResult['total'];
            $booking->price_breakdown = $priceResult['breakdown'];
            $booking->pricing_rule_id = $priceResult['pricing_rule_id'];

            // หักเครดิตลูกค้าทันทีตอนจัดสนาม (ถ้าเครดิตไม่พอ จะ throw RuntimeException
            // แล้ว rollback ทั้ง transaction — แอดมินจะเห็น error กลับไปแก้ไข/แจ้งลูกค้าเติมเครดิตก่อน)
            try {
                $this->creditService->deductForPrivateTraining($booking->user, $booking);
            } catch (RuntimeException $e) {
                return $e->getMessage();
            }

            $booking->update([
                'court_id' => $section->court_id,
                'court_section_id' => $section->id,
                'court_assigned_by' => $request->user()->id,
                'court_assigned_at' => now(),
                'status' => 'confirmed',
                'price' => $priceResult['total'],
                'price_breakdown' => $priceResult['breakdown'],
                'pricing_rule_id' => $priceResult['pricing_rule_id'],
                'payment_status' => 'paid',
            ]);


            return null;
        });

        if ($error) {
            return back()->withErrors(['court' => $error]);
        }

        $privateTrainingBooking->refresh()->load(['coach', 'court', 'courtSection', 'user']);
        $this->notifyUser(
            $privateTrainingBooking->user_id,
            'ยืนยันการจอง Private Training แล้ว',
            "โค้ช {$privateTrainingBooking->coach->name} วันที่ {$privateTrainingBooking->date->format('d/m/Y')} เวลา {$this->formatTimeRange($privateTrainingBooking->start_time, $privateTrainingBooking->end_time)}\nสนาม {$privateTrainingBooking->court->name} — {$privateTrainingBooking->courtSection->name}\nยอดชำระ: " . number_format($privateTrainingBooking->price / 100, 2) . ' บาท (หักจากเครดิต)'
        );

        $this->notifyAdminsOfPayment(
            'Private Training',
            $privateTrainingBooking->id,
            $privateTrainingBooking->user->name ?? '-',
            "โค้ช {$privateTrainingBooking->coach->name} | {$privateTrainingBooking->court->name} — {$privateTrainingBooking->courtSection->name} | "
                . "{$privateTrainingBooking->date->toDateString()} {$this->formatTimeRange($privateTrainingBooking->start_time, $privateTrainingBooking->end_time)}",
            $privateTrainingBooking->price,
            'credit'
        );

        return back()->with('success', 'จัดสนามและยืนยัน Private Training เรียบร้อยแล้ว (หักเครดิตลูกค้าเรียบร้อย)');
    }

    public function reject(Request $request, PrivateTrainingBooking $privateTrainingBooking)
    {
        if (! in_array($privateTrainingBooking->status, ['pending', 'awaiting_court'], true)) {
            return back()->withErrors(['status' => 'รายการนี้ถูกดำเนินการไปแล้ว หรือไม่สามารถปฏิเสธได้']);
        }

        $data = $request->validate(['reject_reason' => ['required', 'string', 'max:500']]);

        $privateTrainingBooking->update([
            'status' => 'rejected',
            'reject_reason' => $data['reject_reason'],
        ]);

        $timeLabel = $this->formatTimeRange($privateTrainingBooking->start_time, $privateTrainingBooking->end_time);
        $this->notifyUser(
            $privateTrainingBooking->user_id,
            'การจองเทรนเนอร์ส่วนตัวถูกปฏิเสธ',
            "การจองกับโค้ช {$privateTrainingBooking->coach->name} |วันที่ {$privateTrainingBooking->date}\nเวลา {$timeLabel}\nถูกปฏิเสธ — เหตุผล: {$data['reject_reason']}"
        );

        return back()->with('success', 'ปฏิเสธคำขอจองเรียบร้อย');
    }

    /* =====================================================================
     |  PRIVATE HELPERS
     ===================================================================== */

    private function assertIsCoach(User $coach): void
    {
        abort_unless($coach->role === 'staff' && $coach->membership_type === 'coach', 404, 'ไม่พบข้อมูลโค้ช');
    }

    private function checkIfNotPending(PrivateTrainingBooking $booking)
    {
        return $booking->status !== 'pending'
            ? back()->withErrors(['status' => 'รายการนี้ถูกดำเนินการไปแล้ว หรือไม่สามารถแก้ไขได้'])
            : null;
    }

    private function formatTimeRange(string $start, string $end): string
    {
        // ตัดข้อความตำแหน่งที่ 0 จำนวน 5 ตัวอักษร เพื่อเอาแค่วินาทีออก (จาก '08:00:00' เป็น '08:00')
        return substr($start, 0, 5) . '-' . substr($end, 0, 5);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'awaiting_court' => 'รอจัดสนาม',
            'confirmed' => 'ยืนยันแล้ว',
            'rejected' => 'ปฏิเสธ',
            'cancelled' => 'ยกเลิก',
            default => 'รออนุมัติ',
        };
    }

    private function notifyUser(int $userId, string $title, string $message): void
    {
        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
        ]);
    }

    private function notifyAdmins(string $title, string $message): void
    {
        // pluck('id') จะดึงมาเฉพาะค่า id ในรูปแบบ Flat Array (เช่น [1, 2, 3]) ซึ่งประหยัดหน่วยความจำกว่าการโหลดมาทั้ง Model
        $admins = User::whereIn('role', ['admin', 'superadmin'])->pluck('id');
        foreach ($admins as $adminId) {
            $this->notifyUser($adminId, $title, $message);
        }
    }

    /**
     * แจ้งเตือนแอดมินทุกคนทางอีเมล เมื่อมีการชำระเงินสำเร็จ (ครอบ try/catch กันเมล
     * เซิร์ฟเวอร์ล่มแล้วดึงหน้าเว็บพัง ทั้งที่หักเครดิต/ยืนยันสำเร็จไปแล้วจริง)
     */
    private function notifyAdminsOfPayment(
        string $refType,
        int $refId,
        string $customerName,
        string $detailLine,
        int $amountSatang,
        string $paymentMethod
    ): void {
        try {
            $adminEmails = User::whereIn('role', ['admin', 'superadmin'])->pluck('email')->filter();

            foreach ($adminEmails as $email) {
                Mail::to($email)->send(new AdminPaymentReceivedMail(
                    $refType, $refId, $customerName, $detailLine, $amountSatang, $paymentMethod
                ));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ส่งอีเมลแจ้งเตือนแอดมินไม่สำเร็จ: ' . $e->getMessage());
        }
    }
}
