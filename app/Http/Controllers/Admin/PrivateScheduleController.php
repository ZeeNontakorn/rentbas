<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Availability;
use App\Models\CalendarEvent;
use App\Models\PrivateTrainingBooking;
use App\Models\User;
use App\Services\CalendarEventOccurrenceService;
use App\Services\StaffScheduleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrivateScheduleController extends Controller
{
    public function __construct(
        private readonly CalendarEventOccurrenceService $occurrences,
        private readonly StaffScheduleService $schedule,
    ) {}

    public function index(Request $request)
    {
        $staffs = $this->staffQuery()->orderBy('name')->get(['id', 'name', 'membership_type']);
        $requestedId = $request->query('staff_id', $request->query('coach_id'));
        $selectedStaffId = $requestedId === 'all'
            ? 'all'
            : ($staffs->contains('id', (int) $requestedId) ? (int) $requestedId : 'all');

        return view('admin.private-training.schedule', compact('staffs', 'selectedStaffId'));
    }

    public function events(Request $request): JsonResponse
    {
        $data = $request->validate([
            'staff_id' => ['required', 'string'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);
        $showAll = $data['staff_id'] === 'all';
        $staff = $showAll ? null : $this->staff((int) $data['staff_id']);
        $staffIds = $showAll ? $this->staffQuery()->pluck('id') : collect([$staff->id]);
        $fromDate = Carbon::parse($data['start']);
        $untilDate = Carbon::parse($data['end']);
        $from = $fromDate->toDateString();
        $until = $untilDate->toDateString();

        $availabilities = Availability::with('user')
            ->whereIn('user_id', $staffIds)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<', $until)
            ->get()
            ->map(fn (Availability $slot) => [
                'id' => 'availability-'.$slot->id,
                'title' => ($showAll ? $slot->user->name.' · ' : '').($slot->detail ?: 'กำหนดการเดิม'),
                'start' => $slot->date.'T'.substr($slot->start_time, 0, 8),
                'end' => $slot->date.'T'.substr($slot->end_time, 0, 8),
                'backgroundColor' => '#5f6368',
                'borderColor' => '#5f6368',
                'editable' => true,
                'extendedProps' => [
                    'kind' => 'availability',
                    'recordId' => $slot->id,
                    'rawTitle' => $slot->detail ?: 'กำหนดการเดิม',
                    'statusLabel' => 'กำหนดการเดิม',
                    'detail' => $slot->detail,
                    'staffId' => $slot->user_id,
                    'staffName' => $slot->user->name,
                ],
            ]);

        $calendarEvents = $this->occurrences
            ->forCoachesBetween($staffIds, $fromDate, $untilDate)
            ->map(function (array $occurrence) use ($showAll) {
                /** @var CalendarEvent $event */
                $event = $occurrence['event'];

                return [
                    'id' => 'calendar-'.$event->id.'-'.$occurrence['occurrenceKey'],
                    'title' => ($showAll ? $event->coach->name.' · ' : '').$event->title,
                    'start' => $occurrence['start']->toIso8601String(),
                    'end' => $occurrence['end']->toIso8601String(),
                    'backgroundColor' => $event->color,
                    'borderColor' => $event->color,
                    'editable' => $event->recurrence === 'none',
                    'extendedProps' => [
                        'kind' => 'calendar_event',
                        'eventId' => $event->id,
                        'rawTitle' => $event->title,
                        'eventType' => $event->event_type,
                        'statusLabel' => $this->eventTypeLabel($event->event_type),
                        'description' => $event->description,
                        'staffId' => $event->coach_id,
                        'staffName' => $event->coach->name,
                        'seriesStartsAt' => $event->starts_at->format('Y-m-d\TH:i'),
                        'seriesEndsAt' => $event->ends_at?->format('Y-m-d\TH:i'),
                        'recurrence' => $event->recurrence,
                        'recurrenceDays' => $event->recurrence_days ?? [],
                        'recurrenceUntil' => $event->recurrence_until?->toDateString(),
                    ],
                ];
            });

        $bookings = PrivateTrainingBooking::with([
            'user',
            'coach',
            'courtAssistant',
            'court',
            'courtSection',
            'packagePurchase.package',
        ])
            ->where(function ($query) use ($staffIds) {
                $query->whereIn('coach_id', $staffIds)
                    ->orWhereIn('court_assistant_id', $staffIds);
            })
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<', $until)
            ->whereIn('status', ['pending', 'awaiting_court', 'confirmed'])
            ->get()
            ->flatMap(function (PrivateTrainingBooking $booking) use ($staffIds, $showAll) {
                return collect([
                    ['staff' => $booking->coach, 'prefix' => 'Private'],
                    ['staff' => $booking->courtAssistant, 'prefix' => 'ผู้ช่วยสนาม'],
                ])->filter(fn (array $item) => $item['staff'] && $staffIds->contains($item['staff']->id))
                    ->map(fn (array $item) => [
                        'id' => 'booking-'.$booking->id.'-staff-'.$item['staff']->id,
                        'title' => ($showAll ? $item['staff']->name.' · ' : '').$item['prefix'].': '.$booking->user->name,
                        'start' => $booking->date->toDateString().'T'.substr($booking->start_time, 0, 8),
                        'end' => $booking->date->toDateString().'T'.substr($booking->end_time, 0, 8),
                        'backgroundColor' => $booking->status === 'confirmed' ? '#7c3aed' : '#f97316',
                        'borderColor' => $booking->status === 'confirmed' ? '#6d28d9' : '#ea580c',
                        'editable' => false,
                        'extendedProps' => [
                            'kind' => 'booking',
                            'bookingId' => $booking->id,
                            'statusKey' => $booking->status,
                            'statusLabel' => match ($booking->status) {
                                'confirmed' => 'ยืนยันแล้ว',
                                'awaiting_court' => 'รอจัดสนาม',
                                default => 'รออนุมัติ',
                            },
                            'staffId' => $item['staff']->id,
                            'staffName' => $item['staff']->name,
                            'roleLabel' => $item['prefix'] === 'Private' ? 'โค้ชผู้สอน' : 'ผู้ช่วยสนาม',
                            'roleCaption' => 'แสดงในตารางของ',
                            'customerName' => $booking->user->name,
                            'customerEmail' => $booking->user->email,
                            'customerPhone' => $booking->user->phone,
                            'coachName' => $booking->coach->name,
                            'assistantName' => $booking->courtAssistant?->name,
                            'court' => $booking->court
                                ? $booking->court->name.($booking->courtSection ? ' — '.$booking->courtSection->name : '')
                                : null,
                            'packageName' => $booking->packagePurchase?->package?->name,
                            'note' => $booking->note,
                        ],
                    ]);
            });

        return response()->json($availabilities->concat($calendarEvents)->concat($bookings)->values());
    }

    public function storeEvent(Request $request): JsonResponse
    {
        $staffId = $request->validate(['staff_id' => ['required', 'integer', 'exists:users,id']]);
        $staff = $this->staff((int) $staffId['staff_id']);
        $data = $this->schedule->validateEventRequest($request, $staff);
        $event = $this->schedule->createEvent($staff, $data);

        return response()->json(['id' => $event->id], 201);
    }

    public function updateEvent(Request $request, CalendarEvent $calendarEvent): JsonResponse
    {
        $staffId = $request->validate(['staff_id' => ['required', 'integer', 'exists:users,id']]);
        $staff = $this->staff((int) $staffId['staff_id']);
        $data = $this->schedule->validateEventRequest($request, $staff, $calendarEvent);
        $event = $this->schedule->updateEvent($staff, $calendarEvent, $data);

        return response()->json(['id' => $event->id]);
    }

    public function destroyEvent(CalendarEvent $calendarEvent)
    {
        $calendarEvent->delete();

        return response()->noContent();
    }

    public function updateAvailability(Request $request, Availability $availability): JsonResponse
    {
        $staff = $this->staff((int) $availability->user_id);
        $data = $this->schedule->validateAvailabilityRequest($request, true);
        $slot = $this->schedule->updateAvailability($staff, $availability, $data);

        return response()->json(['id' => $slot->id]);
    }

    public function destroyAvailability(Availability $availability)
    {
        $this->staff((int) $availability->user_id);
        $availability->delete();

        return response()->noContent();
    }

    private function staff(int $id): User
    {
        return $this->staffQuery()->whereKey($id)->firstOrFail();
    }

    private function staffQuery()
    {
        return User::query()
            ->where('role', 'staff')
            ->whereIn('membership_type', ['coach', 'court_assistant']);
    }

    private function eventTypeLabel(string $type): string
    {
        return match ($type) {
            'school_class' => 'คลาสโรงเรียนบาส',
            'private_training_manual' => 'Private Training (กำหนดเอง)',
            'work' => 'งาน',
            'leave' => 'ลางาน',
            default => 'กิจกรรมส่วนตัว',
        };
    }
}
