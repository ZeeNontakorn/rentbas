<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Models\CalendarEvent;
use App\Models\PrivateTrainingBooking;
use App\Models\User;
use App\Services\CalendarEventOccurrenceService;
use App\Services\StaffScheduleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoachScheduleController extends Controller
{
    public function __construct(
        private readonly CalendarEventOccurrenceService $occurrences,
        private readonly StaffScheduleService $schedule,
    ) {}

    public function events(Request $request): JsonResponse
    {
        $coach = $this->coach($request);
        $range = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);
        $from = Carbon::parse($range['start']);
        $until = Carbon::parse($range['end']);

        $blockedEvents = $coach->availabilities()
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<', $until->toDateString())
            ->get()
            ->map(fn (Availability $slot) => [
                'id' => 'availability-'.$slot->id,
                'title' => $slot->detail ?: 'ไม่ว่าง',
                'start' => $slot->date.'T'.substr($slot->start_time, 0, 8),
                'end' => $slot->date.'T'.substr($slot->end_time, 0, 8),
                'backgroundColor' => '#64748b',
                'borderColor' => '#475569',
                'editable' => true,
                'extendedProps' => [
                    'kind' => 'blocked',
                    'recordId' => $slot->id,
                    'detail' => $slot->detail,
                    'statusLabel' => 'ไม่ว่าง',
                ],
            ]);

        $calendarEvents = $this->occurrences
            ->forCoachesBetween([$coach->id], $from, $until)
            ->map(function (array $occurrence) {
                /** @var CalendarEvent $event */
                $event = $occurrence['event'];

                return [
                    'id' => 'calendar-'.$event->id.'-'.$occurrence['occurrenceKey'],
                    'title' => $event->title,
                    'start' => $occurrence['start']->toIso8601String(),
                    'end' => $occurrence['end']->toIso8601String(),
                    'backgroundColor' => $event->color,
                    'borderColor' => $event->color,
                    'editable' => $event->recurrence === 'none',
                    'extendedProps' => [
                        'kind' => 'calendar_event',
                        'eventId' => $event->id,
                        'eventType' => $event->event_type,
                        'description' => $event->description,
                        'seriesStartsAt' => $event->starts_at->format('Y-m-d\TH:i'),
                        'seriesEndsAt' => $event->ends_at?->format('Y-m-d\TH:i'),
                        'recurrence' => $event->recurrence,
                        'recurrenceDays' => $event->recurrence_days ?? [],
                        'recurrenceUntil' => $event->recurrence_until?->toDateString(),
                        'statusLabel' => $this->eventTypeLabel($event->event_type),
                    ],
                ];
            });

        $bookingEvents = PrivateTrainingBooking::with([
            'user',
            'court',
            'courtSection',
            'coach',
            'courtAssistant',
            'packagePurchase.package',
        ])
            ->where(function ($query) use ($coach) {
                $query->where('coach_id', $coach->id)
                    ->orWhere('court_assistant_id', $coach->id);
            })
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<', $until->toDateString())
            ->whereIn('status', ['pending', 'awaiting_court', 'confirmed'])
            ->get()
            ->map(function (PrivateTrainingBooking $booking) use ($coach) {
                $color = match ($booking->status) {
                    'confirmed' => '#16a34a',
                    'awaiting_court' => '#7c3aed',
                    default => '#f97316',
                };

                return [
                    'id' => 'private-'.$booking->id,
                    'title' => ($booking->coach_id === $coach->id ? 'Private: ' : 'ผู้ช่วยสนาม: ').$booking->user->name,
                    'start' => $booking->date->toDateString().'T'.substr($booking->start_time, 0, 8),
                    'end' => $booking->date->toDateString().'T'.substr($booking->end_time, 0, 8),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'editable' => false,
                    'extendedProps' => [
                        'kind' => 'private_training',
                        'bookingId' => $booking->id,
                        'statusKey' => $booking->status,
                        'statusLabel' => $this->statusLabel($booking->status),
                        'roleLabel' => $booking->coach_id === $coach->id ? 'โค้ชผู้สอน' : 'ผู้ช่วยสนาม',
                        'roleCaption' => 'หน้าที่ของคุณ',
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
                ];
            });

        return response()->json($blockedEvents->concat($calendarEvents)->concat($bookingEvents)->values());
    }

    public function storeEvent(Request $request): JsonResponse
    {
        $coach = $this->coach($request);
        $data = $this->schedule->validateEventRequest($request, $coach);
        $event = $this->schedule->createEvent($coach, $data);

        return response()->json(['id' => $event->id], 201);
    }

    public function updateEvent(Request $request, CalendarEvent $calendarEvent): JsonResponse
    {
        $coach = $this->coach($request);
        abort_unless($calendarEvent->coach_id === $coach->id, 404);
        $data = $this->schedule->validateEventRequest($request, $coach, $calendarEvent);
        $this->schedule->updateEvent($coach, $calendarEvent, $data);

        return response()->json(['id' => $calendarEvent->id]);
    }

    public function destroyEvent(Request $request, CalendarEvent $calendarEvent)
    {
        $coach = $this->coach($request);
        abort_unless($calendarEvent->coach_id === $coach->id, 404);
        $calendarEvent->delete();

        return response()->noContent();
    }

    public function store(Request $request): JsonResponse
    {
        $coach = $this->coach($request);
        $data = $this->schedule->validateAvailabilityRequest($request);
        $slot = $this->schedule->createAvailability($coach, $data);

        return response()->json(['id' => $slot->id], 201);
    }

    public function update(Request $request, Availability $availability): JsonResponse
    {
        $coach = $this->coach($request);
        abort_unless($availability->user_id === $coach->id, 404);
        $data = $this->schedule->validateAvailabilityRequest($request, true);
        $this->schedule->updateAvailability($coach, $availability, $data);

        return response()->json(['id' => $availability->id]);
    }

    public function destroy(Request $request, Availability $availability)
    {
        $coach = $this->coach($request);
        abort_unless($availability->user_id === $coach->id, 404);
        $availability->delete();

        return response()->noContent();
    }

    private function coach(Request $request): User
    {
        $coach = $request->user();
        abort_unless($coach->role === 'staff' && in_array($coach->membership_type, ['coach', 'court_assistant'], true), 403);

        return $coach;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'awaiting_court' => 'รอจัดสนาม',
            'confirmed' => 'ยืนยันแล้ว',
            default => 'รออนุมัติ',
        };
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
