<?php

namespace App\Http\Controllers;

use App\Mail\PrivateTrainingConfirmedMail;
use App\Mail\PrivateTrainingRejectedMail;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\CalendarEvent;
use App\Models\CourtSection;
use App\Models\Notification;
use App\Models\PackagePurchase;
use App\Models\PrivateTrainingBooking;
use App\Models\PromotionPackage;
use App\Models\User;
use App\Services\CalendarEventOccurrenceService;
use App\Services\CourtAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PrivateTrainingController extends Controller
{
    /** ช่วงเวลาเปิดให้จองต่อวัน */
    private const OPEN_HOUR = 8;

    private const CLOSE_HOUR = 22;

    public function __construct(
        protected CalendarEventOccurrenceService $calendarOccurrences,
        protected CourtAvailabilityService $courtAvailability,
    ) {}

    public function index(Request $request)
    {
        $search = $request->query('search');

        $hasValidPackage = PackagePurchase::where('user_id', $request->user()->id)
            ->where('status', 'approved')
            ->where('remaining_use', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expired_at')->orWhere('expired_at', '>', now());
            })
            ->exists();

        $myRequests = PrivateTrainingBooking::with('coach')
            ->where('user_id', $request->user()->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->orderByDesc('created_at')
            ->get();

        // เคยมีคำขอจองมาก่อนไหม (ไม่จำกัดแค่อนาคต เอาทุกสถานะทุกช่วงเวลา เพื่อตัดสินว่าควรให้เห็นหน้ารายชื่อโค้ชไหม)
        $hasAnyBookingHistory = PrivateTrainingBooking::where('user_id', $request->user()->id)->exists();

        // แสดงรายชื่อโค้ชได้ถ้า (มีแพ็กเกจเหลือใช้ได้) หรือ (เคยมีคำขอจองมาก่อน)
        $canViewCoaches = $hasValidPackage || $hasAnyBookingHistory;

        $coaches = $canViewCoaches
            ? User::where('role', 'staff')
                ->where('membership_type', 'coach')
                ->with('staffProfile')
                ->when($search, fn ($q) => $q->where(
                    fn ($qq) => $qq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                ))
                ->orderBy('name')
                ->get()
            : collect();

        $myRequests = PrivateTrainingBooking::with(['coach', 'courtAssistant'])
            ->where('user_id', $request->user()->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->orderByDesc('created_at')
            ->get();

        return view('private-training.index', compact('coaches', 'search', 'myRequests', 'hasValidPackage', 'canViewCoaches'));
    }

    public function show(User $coach)
    {
        $this->assertIsCoach($coach);

        $today = now()->toDateString();
        $maxDate = now()->addDays(CheckoutController::ADVANCE_BOOKING_DAYS)->toDateString();

        $myUpcoming = PrivateTrainingBooking::with('courtAssistant')
            ->where('coach_id', $coach->id)
            ->where('user_id', auth()->id())
            ->whereDate('date', '>=', now()->subDays(7)->toDateString())
            ->whereIn('status', ['pending', 'awaiting_court', 'confirmed'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        // แพ็กเกจที่ user ซื้อไว้และยังใช้ได้ (type = private) — ดึงมาแสดงให้ลูกค้าเห็นว่า
        // เหลือสิทธิ์กี่ครั้ง จะถูกใช้อัตโนมัติตอนส่งคำขอ (เลือกอันใกล้หมดอายุก่อนใน store())
        $myPackagePurchases = PackagePurchase::with('package')
            ->where('user_id', auth()->id())
            ->where('status', 'approved')
            ->where('remaining_use', '>', 0)
            ->whereHas('package', fn ($q) => $q->where('type', 'private'))
            ->where(function ($q) {
                $q->whereNull('expired_at')->orWhere('expired_at', '>', now());
            })
            ->orderByRaw('expired_at IS NULL, expired_at ASC')
            ->get();

        $promotionPackages = PromotionPackage::where('is_active', true)
            ->where('category', 'private')
            ->orderBy('label')
            ->get();

        return view('private-training.show', compact('coach', 'today', 'maxDate', 'myUpcoming', 'promotionPackages', 'myPackagePurchases') + [
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

        $busyEvents = $coach->availabilities()
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<', $until->toDateString())
            ->get()
            ->unique(fn (Availability $item) => implode('|', [$item->date, $item->start_time, $item->end_time]))
            ->map(fn (Availability $item) => [
                'title' => $viewerIsCoach ? ($item->detail ?: 'ไม่ว่าง') : 'โค้ชไม่ว่าง',
                'start' => $item->date.'T'.substr($item->start_time, 0, 8),
                'end' => $item->date.'T'.substr($item->end_time, 0, 8),
                'display' => 'block',
                'backgroundColor' => '#64748b',
                'borderColor' => '#475569',
                'extendedProps' => [
                    'selectable' => false,
                    'kind' => 'unavailable',
                    'unavailableReason' => 'โค้ชมีกำหนดการในช่วงเวลานี้',
                ],
            ]);

        $calendarEvents = $this->calendarOccurrences
            ->forCoachesBetween([$coach->id], $from, $until)
            ->map(function (array $occurrence) {
                /** @var CalendarEvent $event */
                $event = $occurrence['event'];
                $isSchoolClass = $event->event_type === 'school_class';

                return [
                    'id' => 'calendar-'.$event->id.'-'.$occurrence['occurrenceKey'],
                    'title' => 'โค้ชไม่ว่าง',
                    'start' => $occurrence['start']->toIso8601String(),
                    'end' => $occurrence['end']->toIso8601String(),
                    'backgroundColor' => '#64748b',
                    'borderColor' => '#475569',
                    'editable' => false,
                    'extendedProps' => [
                        'selectable' => false,
                        'kind' => 'unavailable',
                        'statusLabel' => $isSchoolClass ? 'คลาสโรงเรียนบาส' : 'ช่วงเวลานี้ไม่ว่าง',
                        'unavailableReason' => $isSchoolClass
                            ? 'โค้ชมีคลาสโรงเรียนบาสในช่วงเวลานี้'
                            : 'โค้ชมีกำหนดการอื่นในช่วงเวลานี้',
                    ],
                ];
            });

        $bookingEvents = PrivateTrainingBooking::with([
            'user',
            'coach',
            'court',
            'courtSection',
            'courtAssistant',
            'packagePurchase.package',
        ])
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
                $color = $mine
                    ? match ($booking->status) {
                        'confirmed' => '#16a34a',
                        'awaiting_court' => '#7c3aed',
                        default => '#3b82f6',
                    }
                : '#64748b';

                return [
                    'id' => 'private-'.$booking->id,
                    'title' => $mine ? $title : 'โค้ชไม่ว่าง',
                    'start' => $booking->date->toDateString().'T'.substr($booking->start_time, 0, 8),
                    'end' => $booking->date->toDateString().'T'.substr($booking->end_time, 0, 8),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'extendedProps' => [
                        'selectable' => false,
                        'kind' => $mine ? 'private_training' : 'unavailable',
                        'isMine' => $mine,
                        'bookingId' => $mine ? $booking->id : null,
                        'status' => $mine ? $booking->status : null,
                        'statusKey' => $mine ? $booking->status : null,
                        'statusLabel' => $mine ? $this->statusLabel($booking->status) : null,
                        'customerName' => $mine ? $booking->user->name : null,
                        'customerEmail' => $mine ? $booking->user->email : null,
                        'customerPhone' => $mine ? $booking->user->phone : null,
                        'coachName' => $mine ? $booking->coach->name : null,
                        'court' => $mine && $booking->court
                            ? $booking->court->name.($booking->courtSection ? ' — '.$booking->courtSection->name : '')
                            : null,
                        'assistantName' => $mine ? $booking->courtAssistant?->name : null,
                        'packageName' => $mine ? $booking->packagePurchase?->package?->name : null,
                        'note' => $mine ? $booking->note : null,
                        'unavailableReason' => $mine ? null : 'ช่วงเวลานี้มีรายการ Private Training แล้ว',
                    ],
                ];
            });

        return response()->json($busyEvents->concat($calendarEvents)->concat($bookingEvents)->values());
    }

    public function mySchedule(Request $request)
    {
        $coach = $request->user();
        abort_unless(
            $coach->role === 'staff' && in_array($coach->membership_type, ['coach', 'court_assistant'], true),
            403,
            'หน้านี้สำหรับโค้ชและผู้ช่วยสนามเท่านั้น',
        );

        return view('private-training.my-schedule', compact('coach'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'coach_id' => ['required', 'integer', 'exists:users,id'],
            'date' => [
                'required', 'date', 'after_or_equal:today',
                'before_or_equal:'.now()->addDays(CheckoutController::ADVANCE_BOOKING_DAYS)->toDateString(),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'note' => ['nullable', 'string', 'max:500'],
            'package_purchase_id' => ['required', 'integer', 'exists:package_purchases,id'],
            'assistant_requested' => ['required', 'boolean'],
            'court_assistant_id' => [
                'nullable',
                'required_if:assistant_requested,1',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', 'staff')
                    ->where('membership_type', 'court_assistant')),
            ],
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

        $startDb = $data['start_time'].':00';
        $endDb = $data['end_time'].':00';

        $result = DB::transaction(function () use ($coach, $date, $startDb, $endDb, $request, $data) {
            User::whereKey($coach->id)->lockForUpdate()->firstOrFail();

            // ล็อกแพ็กเกจที่ลูกค้าเลือกเอง ต้องเป็นของ user นี้จริง และยังใช้ได้อยู่ ณ ขณะนี้
            $packagePurchase = PackagePurchase::where('id', $data['package_purchase_id'])
                ->where('user_id', $request->user()->id)
                ->where('status', 'approved')
                ->where('remaining_use', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('expired_at')->orWhere('expired_at', '>', now());
                })
                ->lockForUpdate()
                ->first();

            if (! $packagePurchase) {
                return 'แพ็กเกจที่เลือกไม่สามารถใช้ได้แล้ว (อาจถูกใช้หมดหรือหมดอายุไปแล้ว) กรุณาเลือกแพ็กเกจอื่น';
            }

            $isBusy = Availability::where('user_id', $coach->id)
                ->whereDate('date', $date)
                ->where('start_time', '<', $endDb)
                ->where('end_time', '>', $startDb)
                ->exists();

            if ($isBusy) {
                return 'ช่วงเวลานี้โค้ชติดภารกิจอื่นอยู่ ไม่สามารถจองได้';
            }

            if ($this->calendarOccurrences->overlapsForCoach(
                $coach->id,
                Carbon::parse($date.' '.$startDb),
                Carbon::parse($date.' '.$endDb),
            )) {
                return 'ช่วงเวลานี้โค้ชมีกำหนดการใน Calendar แล้ว กรุณาเลือกช่วงเวลาอื่น';
            }

            $overlap = PrivateTrainingBooking::overlapping($coach->id, $date, $startDb, $endDb)
                ->lockForUpdate()
                ->exists();

            if ($overlap) {
                return 'ช่วงเวลานี้มีการจองไปแล้ว กรุณาเลือกช่วงเวลาอื่น';
            }

            $assistant = null;
            if ((bool) $data['assistant_requested']) {
                $assistant = User::query()
                    ->whereKey($data['court_assistant_id'])
                    ->where('role', 'staff')
                    ->where('membership_type', 'court_assistant')
                    ->lockForUpdate()
                    ->first();

                if (! $assistant || ! $this->assistantIsAvailable($assistant, $date, $startDb, $endDb)) {
                    return 'ผู้ช่วยสนามที่เลือกไม่ว่างแล้ว กรุณาเลือกผู้ช่วยคนอื่น';
                }
            }

            $packagePurchase->decrement('remaining_use');

            return PrivateTrainingBooking::create([
                'user_id' => $request->user()->id,
                'coach_id' => $coach->id,
                'assistant_requested' => (bool) $data['assistant_requested'],
                'court_assistant_id' => $assistant?->id,
                'date' => $date,
                'start_time' => $startDb,
                'end_time' => $endDb,
                'note' => $data['note'] ?? null,
                'status' => 'pending',
                'package_purchase_id' => $packagePurchase->id,
            ]);

        });

        if (is_string($result)) {
            return back()->withErrors(['bookings' => $result]);
        }

        $timeLabel = $this->formatTimeRange($data['start_time'], $data['end_time']);
        $assistantLine = $result->court_assistant_id
            ? "\nผู้ช่วยสนาม: {$result->courtAssistant->name}"
            : '';
        $this->notifyAdmins(
            'คำขอจองเทรนเนอร์ส่วนตัวใหม่',
            "คุณ {$request->user()->name} ขอจองเทรนเนอร์ส่วนตัวกับโค้ช {$coach->name} |วันที่ {$date}\nเวลา {$timeLabel}{$assistantLine}",
            route('admin.private-training.index', ['status' => 'pending']),
        );

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

        DB::transaction(function () use ($privateTrainingBooking) {
            if ($privateTrainingBooking->package_purchase_id) {
                PackagePurchase::whereKey($privateTrainingBooking->package_purchase_id)
                    ->lockForUpdate()
                    ->increment('remaining_use');
            }

            $privateTrainingBooking->update(['status' => 'cancelled']);
        });

        return back()->with('success', 'ยกเลิกคำขอจองเรียบร้อย');
    }

    /* =====================================================================
     |  ADMIN SECTION
     ===================================================================== */
    public function adminIndex(Request $request)
    {
        $status = $request->query('status', 'pending');

        $bookings = PrivateTrainingBooking::with(['user', 'coach', 'court', 'courtSection', 'courtAssistant'])
            ->when($status && $status !== 'all', function ($q) use ($status): void {
                if ($status === 'expired') {
                    $q->expired();

                    return;
                }

                $q->where('status', $status)
                    ->notExpired();
            })
            ->oldest()// oldest() มีค่าเท่ากับ orderBy('created_at', 'asc') คือคิวที่มาก่อนจะอยู่บนสุด (First In, First Out)
            ->paginate(15)
            // withQueryString() ทำให้การกดเปลี่ยนหน้า (pagination) ยังจำ parameter เดิมใน URL ไว้ (เช่น ค่า search, status)
            ->withQueryString();

        return view('admin.private-training.index', compact('bookings', 'status'));
    }

    public function availableAssistants(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'ignore_booking_id' => ['nullable', 'integer', 'exists:private_training_bookings,id'],
        ]);
        $start = $data['start_time'].':00';
        $end = $data['end_time'].':00';

        $assistants = User::query()
            ->where('role', 'staff')
            ->where('membership_type', 'court_assistant')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->filter(fn (User $assistant) => $this->assistantIsAvailable(
                $assistant,
                $data['date'],
                $start,
                $end,
                isset($data['ignore_booking_id']) ? (int) $data['ignore_booking_id'] : null,
            ))
            ->values()
            ->map(fn (User $assistant) => ['id' => $assistant->id, 'name' => $assistant->name]);

        return response()->json(['assistants' => $assistants]);
    }

    public function availableCourts(PrivateTrainingBooking $privateTrainingBooking): JsonResponse
    {
        abort_unless($privateTrainingBooking->status === 'awaiting_court', 422, 'รายการนี้ไม่อยู่ในขั้นตอนจัดสนาม');

        $sections = $this->courtAvailability
            ->availableSectionsForPrivateBooking($privateTrainingBooking)
            ->map(fn (CourtSection $section) => [
                'id' => $section->id,
                'court_id' => $section->court_id,
                'court_name' => $section->court->name,
                'section_name' => $section->name,
                'label' => $section->court->name.' — '.$section->name,
            ]);

        return response()->json(['sections' => $sections]);
    }

    public function approve(PrivateTrainingBooking $privateTrainingBooking)
    {
        if ($error = $this->checkIfNotPending($privateTrainingBooking)) {
            return $error;
        }

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
            "คำขอกับโค้ช {$privateTrainingBooking->coach->name} วันที่ {$privateTrainingBooking->date->format('d/m/Y')}\nเวลา {$timeLabel}\nกำลังรอแอดมินจัดสนาม",
            route('private-training.index'),
        );

        return back()->with('success', 'รับคำขอเรียบร้อยแล้ว ขั้นตอนถัดไปกรุณาจัดสนาม');
    }

    public function assignCourt(Request $request, PrivateTrainingBooking $privateTrainingBooking)
    {
        abort_unless($privateTrainingBooking->status === 'awaiting_court', 422, 'รายการนี้ไม่อยู่ในขั้นตอนจัดสนาม');

        $data = $request->validate([
            'court_section_id' => ['required', 'integer', 'exists:court_sections,id'],
            'court_assistant_id' => [
                'nullable',
                Rule::requiredIf($privateTrainingBooking->assistant_requested),
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', 'staff')
                    ->where('membership_type', 'court_assistant')),
            ],
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

            $assistant = null;
            if ($booking->assistant_requested) {
                $assistant = User::query()
                    ->whereKey($data['court_assistant_id'])
                    ->where('role', 'staff')
                    ->where('membership_type', 'court_assistant')
                    ->lockForUpdate()
                    ->first();

                if (! $assistant || ! $this->assistantIsAvailable($assistant, $date, $start, $end, $booking->id)) {
                    return 'ผู้ช่วยสนามที่เลือกไม่ว่างแล้ว กรุณาเลือกผู้ช่วยคนอื่น';
                }
            }

            if (! $this->courtAvailability->isSectionAvailable($section, $from, $until, $booking->id)) {
                return 'สนามนี้ไม่ว่างแล้ว กรุณาเปิด Modal แล้วเลือกสนามใหม่';
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

            $paidByPackage = (bool) $booking->package_purchase_id;
            $priceBreakdown = [[
                'label' => 'จัดสนามสำหรับ Private Training (ไม่มีค่าใช้จ่ายเพิ่ม)',
                'minutes' => $from->diffInMinutes($until),
                'price' => 0,
            ]];

            $booking->update([
                'court_id' => $section->court_id,
                'court_section_id' => $section->id,
                'court_assigned_by' => $request->user()->id,
                'court_assigned_at' => now(),
                'court_assistant_id' => $assistant?->id,
                'status' => 'confirmed',
                'price' => 0,
                'price_breakdown' => $priceBreakdown,
                'pricing_rule_id' => null,
                'payment_status' => $paidByPackage ? 'paid_by_package' : $booking->payment_status,
            ]);

            return null;
        });

        if ($error) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => $error,
                    'errors' => ['court' => [$error]],
                ], 422);
            }

            return back()->withErrors(['court' => $error]);
        }

        $privateTrainingBooking->refresh()->load(['coach', 'court', 'courtSection', 'courtAssistant', 'user']);
        $assistantLine = $privateTrainingBooking->courtAssistant
            ? "\nผู้ช่วยสนาม {$privateTrainingBooking->courtAssistant->name}"
            : '';
        $this->notifyUser(
            $privateTrainingBooking->user_id,
            'ยืนยันการจอง Private Training แล้ว',
            "โค้ช {$privateTrainingBooking->coach->name} วันที่ {$privateTrainingBooking->date->format('d/m/Y')} เวลา {$this->formatTimeRange($privateTrainingBooking->start_time, $privateTrainingBooking->end_time)}\nสนาม {$privateTrainingBooking->court->name} — {$privateTrainingBooking->courtSection->name}{$assistantLine}\nจัดสนามเรียบร้อยแล้ว ไม่มีการหักเครดิตเพิ่ม",
            route('private-training.index'),
        );
        
        $successMessage = 'จัดสนามและยืนยัน Private Training เรียบร้อยแล้ว ไม่มีการหักเครดิตลูกค้า';
        // ส่งอีเมลแจ้งยืนยันไปหาลูกค้าโดยตรง (ครอบ try/catch กันเมลเซิร์ฟเวอร์ล่มแล้วทำให้
        // การจัดสนามที่สำเร็จไปแล้วจริงพังไปด้วย เหมือนที่ทำไว้กับ notifyAdminsOfPayment)
        try {
            if ($privateTrainingBooking->user?->email) {
                Mail::to($privateTrainingBooking->user->email)
                    ->send(new PrivateTrainingConfirmedMail($privateTrainingBooking));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ส่งอีเมลยืนยันการจองให้ลูกค้าไม่สำเร็จ: ' . $e->getMessage());
        }


        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $successMessage,
            ]);
        }

        return back()->with('success', $successMessage);
    }

    public function reject(Request $request, PrivateTrainingBooking $privateTrainingBooking)
    {
        if (! in_array($privateTrainingBooking->status, ['pending', 'awaiting_court'], true)) {
            return back()->withErrors(['status' => 'รายการนี้ถูกดำเนินการไปแล้ว หรือไม่สามารถปฏิเสธได้']);
        }

        $data = $request->validate(['reject_reason' => ['required', 'string', 'max:500']]);

        DB::transaction(function () use ($privateTrainingBooking, $data) {
            if ($privateTrainingBooking->package_purchase_id) {
                PackagePurchase::whereKey($privateTrainingBooking->package_purchase_id)
                    ->lockForUpdate()
                    ->increment('remaining_use');
            }

            $privateTrainingBooking->update([
                'status' => 'rejected',
                'reject_reason' => $data['reject_reason'],
            ]);
        });

        $timeLabel = $this->formatTimeRange($privateTrainingBooking->start_time, $privateTrainingBooking->end_time);
        $this->notifyUser(
            $privateTrainingBooking->user_id,
            'การจองเทรนเนอร์ส่วนตัวถูกปฏิเสธ',
            "การจองกับโค้ช {$privateTrainingBooking->coach->name} |วันที่ {$privateTrainingBooking->date}\nเวลา {$timeLabel}\nถูกปฏิเสธ — เหตุผล: {$data['reject_reason']}",
            route('private-training.index'),
        );

        try {
            if ($privateTrainingBooking->user?->email) {
                Mail::to($privateTrainingBooking->user->email)
                    ->send(new PrivateTrainingRejectedMail($privateTrainingBooking, $data['reject_reason']));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ส่งอีเมลปฏิเสธ Private Training ไม่สำเร็จ: ' . $e->getMessage());
        }

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
        return substr($start, 0, 5).'-'.substr($end, 0, 5);
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

    private function assistantIsAvailable(
        User $assistant,
        string $date,
        string $start,
        string $end,
        ?int $ignoreBookingId = null,
    ): bool {
        if (Availability::query()
            ->where('user_id', $assistant->id)
            ->whereDate('date', $date)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists()) {
            return false;
        }

        if ($this->calendarOccurrences->overlapsForCoach(
            $assistant->id,
            Carbon::parse($date.' '.$start),
            Carbon::parse($date.' '.$end),
        )) {
            return false;
        }

        return ! PrivateTrainingBooking::overlappingAssistant($assistant->id, $date, $start, $end)
            ->when($ignoreBookingId, fn ($query) => $query->whereKeyNot($ignoreBookingId))
            ->exists();
    }

    private function notifyUser(int $userId, string $title, string $message, ?string $actionUrl = null): void
    {
        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
        ]);
    }

    private function notifyAdmins(string $title, string $message, ?string $actionUrl = null): void
    {
        // pluck('id') จะดึงมาเฉพาะค่า id ในรูปแบบ Flat Array (เช่น [1, 2, 3]) ซึ่งประหยัดหน่วยความจำกว่าการโหลดมาทั้ง Model
        $admins = User::whereIn('role', ['admin', 'superadmin'])->pluck('id');
        foreach ($admins as $adminId) {
            $this->notifyUser($adminId, $title, $message, $actionUrl);
        }
    }
}
