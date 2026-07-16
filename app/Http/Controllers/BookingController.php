<?php

namespace App\Http\Controllers;

use App\Mail\BookingApprovedMail;
use App\Mail\BookingCancelledByAdminMail;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSection;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class BookingController extends Controller
{
    /**
     * Landing page — เลือกสนาม (สนามเดียว) และเวลา (เลือกได้หลายช่วงเวลาพร้อมกัน)
     */
    public function index(Request $request)
    {
        $courts = Court::all()->sortBy(function ($court) {
            return $court->name;
        }, SORT_NATURAL | SORT_FLAG_CASE)->values();
        $dateParam = $request->query('date', now()->toDateString());

        try {
            $parsedDate = Carbon::parse($dateParam);
            $maxDate = now()->addMonth();
            $minDate = now()->startOfDay();

            if ($parsedDate->startOfDay()->gt($maxDate->startOfDay())) {
                $date = $maxDate->toDateString();
            } elseif ($parsedDate->startOfDay()->lt($minDate)) {
                $date = now()->toDateString();
            } else {
                $date = $parsedDate->toDateString();
            }
        } catch (\Exception $e) {
            $date = now()->toDateString();
        }

        $courtId = $request->query('court_id', $courts->first()?->id);
        $selectedCourt = $courts->firstWhere('id', $courtId);

        $matrix = $selectedCourt ? $this->buildAvailabilityMatrix($selectedCourt, $date) : null;
        // เก็บ $slots (แบบเดิม เต็มสนาม/รายชั่วโมง) ไว้เพื่อ backward-compat กับโค้ด/วิวอื่นที่อาจยังอ้างอิงอยู่
        $slots = $matrix ? $matrix['legacySlots'] : [];

        return view('booking.index', compact('courts', 'date', 'selectedCourt', 'slots', 'matrix'));
    }

    /**
     * Google-Calendar-style booking view — ข้อมูลชุดเดียวกับ index() แต่ render เป็นตารางเวลาแนวตั้ง
     */
    public function calendar(Request $request)
    {
        $courts = Court::all()->sortBy(function ($court) {
            return $court->name;
        }, SORT_NATURAL | SORT_FLAG_CASE)->values();
        $dateParam = $request->query('date', now()->toDateString());

        try {
            $parsedDate = Carbon::parse($dateParam);
            $maxDate = now()->addMonth();
            $minDate = now()->startOfDay();

            if ($parsedDate->startOfDay()->gt($maxDate->startOfDay())) {
                $date = $maxDate->toDateString();
            } elseif ($parsedDate->startOfDay()->lt($minDate)) {
                $date = now()->toDateString();
            } else {
                $date = $parsedDate->toDateString();
            }
        } catch (\Exception $e) {
            $date = now()->toDateString();
        }

        $courtId = $request->query('court_id', $courts->first()?->id);
        $selectedCourt = $courts->firstWhere('id', $courtId);

        $matrix = $selectedCourt ? $this->buildAvailabilityMatrix($selectedCourt, $date) : null;

        $mappedRows = collect($matrix['rows'])->map(fn($r) => [
        'start' => substr($r['start'], 0, 5),
        'end' => substr($r['end'], 0, 5),
        'sections' => collect($r['sections'])->map(fn($s) => $s['status'])->all(),
        ])->all();

        return view('booking.calendar', compact('courts', 'date', 'selectedCourt', 'matrix', 'mappedRows'));
    }

    /**
     * สร้างตารางความว่าง (matrix) ของสนามหนึ่งสนาม ในวันที่ระบุ โดยแบ่งตาม
     * ส่วนของสนาม (section: เต็มสนาม/ครึ่งซ้าย/ครึ่งขวา) และความละเอียดเวลาต่อสนาม
     * (slot_interval_minutes) เพื่อรองรับทั้งการจองครึ่งสนามและเวลาแบบไม่เต็มชั่วโมง
     *
     * โครงสร้างผลลัพธ์ถูกออกแบบให้ frontend ทั้ง 2 แบบ (grid และ calendar) ใช้ข้อมูลชุดเดียวกันได้:
     * - sections: คอลัมน์/เลนที่จองได้ พร้อม id/code/name
     * - rows: แถวเวลาแต่ละช่วง (ยาว = slot_interval_minutes) แต่ละแถวมีสถานะของทุก section
     * - openTime/closeTime/intervalMinutes/minBookingMinutes: ใช้ฝั่ง JS สำหรับ snap เวลา/บังคับระยะเวลาขั้นต่ำ
     */
    protected function buildAvailabilityMatrix(Court $court, string $date): array
    {
        $sections = $court->activeSections();
        if ($sections->isEmpty()) {
            // กันเคสสนามที่ยังไม่มี section เลย (ข้อมูลเก่าก่อน migration) ไม่ให้หน้าเว็บพัง
            $fallback = $court->defaultSection();
            $sections = $fallback ? collect([$fallback]) : collect();
        }

        $interval = max(5, (int) ($court->slot_interval_minutes ?? 30));
        $minMinutes = max($interval, (int) ($court->min_booking_minutes ?? 60));
        $openMinutes = 6 * 60;   // 06:00
        $closeMinutes = 22 * 60; // 22:00
        $dateCarbon = Carbon::parse($date);
        $now = now();

        $bookings = Booking::where('court_id', $court->id)
            ->whereDate('booking_date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->get();

        // เตรียม conflict-map ต่อ section หนึ่งครั้ง (แทนการ query ซ้ำทุกแถว/ทุกคอลัมน์)
        $conflictMap = [];
        foreach ($sections as $section) {
            $conflictMap[$section->id] = $section->conflictingSectionIds();
        }

        $rows = [];
        $legacySlots = [];
        $fullSection = $sections->firstWhere('code', 'full');

        for ($m = $openMinutes; $m < $closeMinutes; $m += $interval) {
            $start = sprintf('%02d:%02d:00', intdiv($m, 60), $m % 60);
            $endM = $m + $interval;
            $end = sprintf('%02d:%02d:00', intdiv($endM, 60), $endM % 60);

            $slotStart = $dateCarbon->copy()->setTimeFromTimeString($start);
            $slotEnd = $dateCarbon->copy()->setTimeFromTimeString($end);
            $isPast = $slotStart->lte($now);

            $rowSections = [];
            foreach ($sections as $section) {
                $isClosed = $court->isClosedAt($slotStart, $slotEnd, $section);

                $conflictIds = $conflictMap[$section->id];
                $booking = $bookings->first(function ($b) use ($conflictIds, $start, $end) {
                    return in_array($b->court_section_id, $conflictIds, true)
                        && $b->start_time < $end
                        && $b->end_time > $start;
                });

                $status = 'available';
                if ($isClosed) $status = 'closed';
                elseif ($booking) $status = $booking->status; // pending | approved (ของ section ตัวเองหรือ section ที่บล็อกกัน)
                elseif ($isPast) $status = 'past';

                $rowSections[$section->id] = [
                    'status' => $status,
                    'own' => $booking?->court_section_id === $section->id,
                ];
            }

            $rows[] = [
                'start' => $start,
                'end' => $end,
                'label' => substr($start, 0, 5) . ' - ' . substr($end, 0, 5),
                'sections' => $rowSections,
            ];

            // legacy hourly/full-section slot format (สำหรับโค้ดเก่าที่ยังอ่าน $slots)
            if ($fullSection && $m % 60 === 0) {
                $legacySlots[] = [
                    'label' => sprintf('%02d:00 - %02d:00', intdiv($m, 60), intdiv($m, 60) + 1),
                    'start' => $start,
                    'end' => sprintf('%02d:00:00', intdiv($m, 60) + 1),
                    'status' => $rowSections[$fullSection->id]['status'] ?? 'closed',
                    'booking' => null,
                ];
            }
        }

        return [
            'sections' => $sections->map(fn ($s) => ['id' => $s->id, 'code' => $s->code, 'name' => $s->name])->all(),
            'rows' => $rows,
            'intervalMinutes' => $interval,
            'minBookingMinutes' => $minMinutes,
            'openTime' => sprintf('%02d:%02d', intdiv($openMinutes, 60), $openMinutes % 60),
            'closeTime' => sprintf('%02d:%02d', intdiv($closeMinutes, 60), $closeMinutes % 60),
            'legacySlots' => $legacySlots,
        ];
    }

    /**
     * เช็คว่าเวลาที่ส่งมา (HH:MM) ตรงกับ interval ของสนามนั้น และระยะเวลารวม >= ขั้นต่ำหรือไม่
     */
    protected function isValidTimeRangeForCourt(Court $court, string $start, string $end): bool
    {
        $interval = max(5, (int) ($court->slot_interval_minutes ?? 30));
        $minMinutes = max($interval, (int) ($court->min_booking_minutes ?? 60));

        [$sh, $sm] = array_map('intval', explode(':', $start));
        [$eh, $em] = array_map('intval', explode(':', $end));
        $startMin = $sh * 60 + $sm;
        $endMin = $eh * 60 + $em;
        $duration = $endMin - $startMin;

        if ($duration < $minMinutes) return false;
        if ($startMin % $interval !== 0) return false;
        if ($endMin % $interval !== 0) return false;

        return true;
    }

    /**
     * Show hourly slots for a court (kept for backwards compatibility if needed, but not used in new flow)
     */
    public function show(Court $court, Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        return redirect()->route('booking.index', ['court_id' => $court->id, 'date' => $date]);
    }

    /**
     * Store one or more bookings at once (เลือกได้หลายสนาม/หลายเวลาในคราวเดียว)
     */
    public function store(Request $request)
    {
        $maxDate = now()->addMonth()->toDateString();

        $data = $request->validate([
            'booking_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . $maxDate],
            'bookings' => ['required', 'array', 'min:1'],
            'bookings.*.court_id' => ['required', 'integer', 'exists:courts,id'],
            'bookings.*.court_section_id' => ['nullable', 'integer', 'exists:court_sections,id'],
            'bookings.*.start_time' => ['required', 'date_format:H:i'],
            'bookings.*.end_time' => ['required', 'date_format:H:i', 'after:bookings.*.start_time'],
        ]);

        $bookingDate = $data['booking_date'];
        $items = $data['bookings'];

        foreach ($items as $item) {
            if ($item['start_time'] < '06:00' || $item['end_time'] > '22:00') {
                return back()->withErrors(['start_time' => 'สามารถจองได้เฉพาะช่วง 06:00–22:00 เท่านั้น']);
            }

            $court = Court::find($item['court_id']);
            if ($court && ! $this->isValidTimeRangeForCourt($court, $item['start_time'], $item['end_time'])) {
                $interval = max(5, (int) ($court->slot_interval_minutes ?? 30));
                $minMinutes = max($interval, (int) ($court->min_booking_minutes ?? 60));
                return back()->withErrors([
                    'start_time' => "{$court->name}: เวลาต้องเริ่ม/จบตรงกับช่วงทุก {$interval} นาที และจองอย่างน้อย {$minMinutes} นาที",
                ]);
            }
        }

        $result = DB::transaction(function () use ($items, $bookingDate, $request) {
            $created = [];
            $failed = [];

            foreach ($items as $item) {
                $court = Court::findOrFail($item['court_id']);

                // ถ้า frontend ยังไม่ได้ส่ง court_section_id มา (เช่น flow เก่า/สนามที่ยังไม่แบ่งครึ่ง)
                // ให้ fallback ไปใช้ section "เต็มสนาม" ของ court นั้นโดยอัตโนมัติ
                $section = isset($item['court_section_id'])
                    ? CourtSection::where('court_id', $court->id)->find($item['court_section_id'])
                    : $court->defaultSection();

                if (!$section) {
                    $failed[] = "{$court->name} ({$item['start_time']}-{$item['end_time']}): ไม่พบส่วนของสนามที่เลือก";
                    continue;
                }

                if (!$section->is_active) {
                    $failed[] = "{$court->name} ({$item['start_time']}-{$item['end_time']}): ส่วนของสนามนี้ปิดใช้งานอยู่";
                    continue;
                }

                $startAt = Carbon::parse("{$bookingDate} {$item['start_time']}");
                $endAt = Carbon::parse("{$bookingDate} {$item['end_time']}");

                if ($startAt->lte(now())) {
                    $failed[] = "{$court->name} ({$item['start_time']}-{$item['end_time']}): ช่วงเวลาที่ผ่านมาแล้ว";
                    continue;
                }

                if ($court->isClosedAt($startAt, $endAt, $section)) {
                    $failed[] = "{$court->name} ({$item['start_time']}-{$item['end_time']}): สนามปิดให้บริการ";
                    continue;
                }

                $startDb = $item['start_time'] . ':00';
                $endDb = $item['end_time'] . ':00';

                $overlap = Booking::overlappingSection($section, $bookingDate, $startDb, $endDb)
                    ->lockForUpdate()
                    ->exists();

                if ($overlap) {
                    $failed[] = "{$court->name} ({$item['start_time']}-{$item['end_time']}): มีการจองอยู่แล้ว";
                    continue;
                }

                $booking = Booking::create([
                    'user_id' => $request->user()->id,
                    'court_id' => $court->id,
                    'court_section_id' => $section->id,
                    'booking_date' => $bookingDate,
                    'start_time' => $startDb,
                    'end_time' => $endDb,
                    'status' => 'pending',
                ]);

                $created[] = [
                    'court_name' => $court->name,
                    'date' => $bookingDate,
                    'time' => substr($item['start_time'], 0, 5) . ' - ' . substr($item['end_time'], 0, 5),
                    'status' => 'รออนุมัติ',
                ];
            }

            return ['created' => $created, 'failed' => $failed];
        });

        // แจ้งเตือนแอดมิน "1 ครั้งต่อ 1 การจอง" ไม่ว่าผู้ใช้จะเลือกกี่ช่วงเวลาก็ตาม
        // (เดิมยิงแยกทีละ item ในลูป ทำให้ badge จำนวนแจ้งเตือนพุ่งเกินจริงเวลาเลือกหลายเวลาพร้อมกัน)
        if (!empty($result['created'])) {
            // แต่ละรายการขึ้นบรรทัดใหม่ (คั่นด้วย , แล้วตามด้วย \n) แทนที่จะเรียงติดกันเป็นพืดยาว
            $summaryLines = collect($result['created'])
                ->map(fn ($c) => "{$c['court_name']} {$c['time']}")
                ->implode(",\n");

            $count = count($result['created']);
            $title = $count > 1 ? "คำขอจองใหม่ ({$count} รายการ)" : 'คำขอจองใหม่';

            User::where('role', 'admin')->get()->each(function ($admin) use ($request, $bookingDate, $summaryLines, $title) {
                Notification::create([
                    'user_id' => $admin->id,
                    'title' => $title,
                    'message' => "คุณ {$request->user()->name} ขอจอง |วันที่ {$bookingDate}\n{$summaryLines}",
                ]);
            });
        }

        if (empty($result['created'])) {
            return back()->withErrors(['bookings' => 'ไม่สามารถทำการจองได้: ' . implode(', ', $result['failed'])]);
        }

        $response = back()->with('success_booking', $result['created']);

        if (!empty($result['failed'])) {
            $response->withErrors(['bookings' => 'บางรายการจองไม่สำเร็จ: ' . implode(', ', $result['failed'])]);
        }

        return $response;
    }

    /**
     * Cancel a booking (user)
     */
    public function cancel(Request $request, Booking $booking)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        if ($booking->status !== 'pending') {
            return back()->withErrors(['status' => 'ไม่สามารถยกเลิกรายการนี้ได้ เนื่องจากได้รับการอนุมัติหรือดำเนินการไปแล้ว']);
        }

        if ($booking->isStarted()) {
            return back()->withErrors(['status' => 'ไม่สามารถยกเลิกรายการที่ถึงเวลาเล่นแล้วได้']);
        }

        $booking->update(['status' => 'cancelled']);

        User::where('role', 'admin')->get()->each(function ($admin) use ($booking) {
            $bDate = Carbon::parse($booking->booking_date)->toDateString();
            Notification::create([
                'user_id' => $admin->id,
                'title' => 'ผู้ใช้ยกเลิกการจอง',
                'message' => "คุณ {$booking->user->name} ยกเลิกการจอง {$booking->court->name} วันที่ {$bDate}",
            ]);
        });

        return back()->with('success', 'ยกเลิกการจองเรียบร้อย');
    }

    public function approve(Booking $booking)
    {
        if ($booking->status !== 'pending') {
            return back()->withErrors(['status' => 'รายการนี้ถูกดำเนินการไปแล้ว']);
        }

        $bDate = Carbon::parse($booking->booking_date)->toDateString();
        $startAt = Carbon::parse("{$bDate} {$booking->start_time}");
        $endAt = Carbon::parse("{$bDate} {$booking->end_time}");

        if ($booking->court->isClosedAt($startAt, $endAt, $booking->courtSection)) {
            return back()->withErrors(['status' => 'ไม่สามารถอนุมัติได้เนื่องจากสนามปิดให้บริการในช่วงเวลานี้']);
        }

        $conflictingSectionIds = $booking->courtSection
            ? $booking->courtSection->conflictingSectionIds()
            : [$booking->court_id]; // fallback หา booking เก่าที่ยังไม่มี section (ไม่ควรเกิดขึ้นหลัง backfill)

        $overlap = Booking::whereIn('court_section_id', $conflictingSectionIds)
            ->whereDate('booking_date', $bDate)
            ->where('status', 'approved')
            ->where('id', '!=', $booking->id)
            ->where(function ($q) use ($booking) {
                $q->where('start_time', '<', $booking->end_time)
                    ->where('end_time', '>', $booking->start_time);
            })
            ->exists();

        if ($overlap) {
            return back()->withErrors(['status' => 'ไม่สามารถอนุมัติได้เนื่องจากมีรายการจองอื่นที่อนุมัติแล้วในช่วงเวลานี้ (ทับซ้อนกัน)']);
        }

        $booking->update(['status' => 'approved']);

        Notification::create([
            'user_id'=>$booking->user_id,
            'title'=>'การจองได้รับการอนุมัติ',
            'message'=>"การจอง {$booking->court->name} วันที่ {$bDate}\nเวลา " . substr($booking->start_time, 0, 5) . '-' . substr($booking->end_time, 0, 5) . "\nได้รับการอนุมัติแล้ว",
        ]);

        if ($booking->user?->email) {
            Mail::to($booking->user->email)
                ->send(new BookingApprovedMail($booking));
        }

        return back()->with('success', 'อนุมัติการจองเรียบร้อย');
    }

    /**
     * Reject booking (admin)
     */
    public function reject(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'reject_reason' => ['required', 'string', 'max:500'],
        ]);

        if ($booking->status !== 'pending') {
            return back()->withErrors(['status' => 'รายการนี้ถูกดำเนินการไปแล้ว']);
        }

        $booking->update([
            'status' => 'rejected',
            'reject_reason' => $data['reject_reason'],
        ]);

        $bDate = Carbon::parse($booking->booking_date)->toDateString();
        Notification::create([
            'user_id'=>$booking->user_id,
            'title'=>'การจองถูกปฏิเสธ',
            'message'=>"การจอง {$booking->court->name} |วันที่ {$bDate}\nเวลา " . substr($booking->start_time, 0, 5) . '-' . substr($booking->end_time, 0, 5) . "\nถูกปฏิเสธ — เหตุผล: {$data['reject_reason']}",
        ]);
         if ($booking->user?->email) {
            Mail::to($booking->user->email)
                ->send(new BookingCancelledByAdminMail($booking, $data['reject_reason']));
        }

        return back()->with('success','ปฏิเสธการจองเรียบร้อย');
    }

    /**
     * Booking history (current/past)
     */
    public function history(Request $request)
    {
        $userId = $request->user()->id;
        $today = now()->toDateString();

        $current = Booking::with('court')
            ->where('user_id', $userId)
            ->where(function ($q) use ($today) {
                $q->whereIn('status', ['pending', 'approved'])
                    ->whereDate('booking_date', '>=', $today);
            })
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();

        $past = Booking::with('court')
            ->where('user_id', $userId)
            ->where(function ($q) use ($today) {
                $q->whereIn('status', ['rejected', 'cancelled'])
                    ->orWhereDate('booking_date', '<', $today);
            })
            ->orderByDesc('updated_at')
            ->get();

        return view('booking.history', compact('current', 'past'));
    }
    /**
     * Approve multiple bookings at once (admin)
     */
    public function bulkApprove(Request $request)
    {
        $data = $request->validate([
            'booking_ids' => ['required', 'array', 'min:1'],
            'booking_ids.*' => ['integer', 'exists:bookings,id'],
        ]);

        $approvedCount = 0;
        $failed = [];

        foreach ($data['booking_ids'] as $id) {
            $booking = Booking::find($id);

            if (!$booking || $booking->status !== 'pending') {
                continue; // ข้ามรายการที่ถูกดำเนินการไปแล้วหรือหาไม่เจอ
            }

            $bDate = Carbon::parse($booking->booking_date)->toDateString();
            $startAt = Carbon::parse("{$bDate} {$booking->start_time}");
            $endAt = Carbon::parse("{$bDate} {$booking->end_time}");

            if ($booking->court->isClosedAt($startAt, $endAt, $booking->courtSection)) {
                $failed[] = "{$booking->court->name} ({$bDate}): สนามปิดให้บริการ";
                continue;
            }

            $conflictingSectionIds = $booking->courtSection
                ? $booking->courtSection->conflictingSectionIds()
                : [$booking->court_id];

            $overlap = Booking::whereIn('court_section_id', $conflictingSectionIds)
                ->whereDate('booking_date', $bDate)
                ->where('status', 'approved')
                ->where('id', '!=', $booking->id)
                ->where(function ($q) use ($booking) {
                    $q->where('start_time', '<', $booking->end_time)
                        ->where('end_time', '>', $booking->start_time);
                })
                ->exists();

            if ($overlap) {
                $failed[] = "{$booking->court->name} ({$bDate} " . substr($booking->start_time, 0, 5) . "): มีรายการทับซ้อน";
                continue;
            }

            $booking->update(['status' => 'approved']);

            Notification::create([
                'user_id' => $booking->user_id,
                'title' => 'การจองได้รับการอนุมัติ',
                'message' => "การจอง {$booking->court->name} วันที่ {$bDate}\nเวลา " . substr($booking->start_time, 0, 5) . '-' . substr($booking->end_time, 0, 5) . "\nได้รับการอนุมัติแล้ว",
            ]);

            if ($booking->user?->email) {
                Mail::to($booking->user->email)
                    ->send(new BookingApprovedMail($booking));
            }

            $approvedCount++;
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'approved' => $approvedCount,
                'failed' => $failed,
            ]);
        }

        $response = back()->with('success', "อนุมัติสำเร็จ {$approvedCount} รายการ");
        if (!empty($failed)) {
            $response->withErrors(['bookings' => 'บางรายการอนุมัติไม่สำเร็จ: ' . implode(', ', $failed)]);
        }
        return $response;
    }

    /**
     * Reject multiple bookings at once with the same reason (admin)
     */
    public function bulkReject(Request $request)
    {
        $data = $request->validate([
            'booking_ids' => ['required', 'array', 'min:1'],
            'booking_ids.*' => ['integer', 'exists:bookings,id'],
            'reject_reason' => ['required', 'string', 'max:500'],
        ]);

        $rejectedCount = 0;

        foreach ($data['booking_ids'] as $id) {
            $booking = Booking::find($id);

            if (!$booking || $booking->status !== 'pending') {
                continue; // ข้ามรายการที่ถูกดำเนินการไปแล้วหรือหาไม่เจอ
            }

            $booking->update([
                'status' => 'rejected',
                'reject_reason' => $data['reject_reason'],
            ]);

            $bDate = Carbon::parse($booking->booking_date)->toDateString();
            Notification::create([
                'user_id' => $booking->user_id,
                'title' => 'การจองถูกปฏิเสธ',
                'message' => "การจอง {$booking->court->name} |วันที่ {$bDate}\nเวลา " . substr($booking->start_time, 0, 5) . '-' . substr($booking->end_time, 0, 5) . "\nถูกปฏิเสธ — เหตุผล: {$data['reject_reason']}",
            ]);

            if ($booking->user?->email) {
                Mail::to($booking->user->email)
                    ->send(new BookingCancelledByAdminMail($booking, $data['reject_reason']));
            }

            $rejectedCount++;
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'rejected' => $rejectedCount,
            ]);
        }

        return back()->with('success', "ปฏิเสธสำเร็จ {$rejectedCount} รายการ");
    }
}
