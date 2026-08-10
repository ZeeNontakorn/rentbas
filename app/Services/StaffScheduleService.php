<?php

namespace App\Services;

use App\Models\Availability;
use App\Models\CalendarEvent;
use App\Models\PrivateTrainingBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffScheduleService
{
    private const OPEN_TIME = '08:00';

    private const CLOSE_TIME = '22:00';

    public function __construct(
        private readonly CalendarEventOccurrenceService $occurrences,
    ) {}

    public function validateEventRequest(Request $request, User $staff, ?CalendarEvent $existing = null): array
    {
        $dateRules = ['required', 'date'];
        if (! $existing) {
            $dateRules[] = 'after_or_equal:today';
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'date' => $dateRules,
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'event_type' => ['required', 'in:general,work,leave,school_class,private_training_manual'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'recurrence' => ['required', 'in:none,daily,weekly,monthly'],
            'recurrence_days' => ['exclude_unless:recurrence,weekly', 'required', 'array', 'min:1'],
            'recurrence_days.*' => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'recurrence_until' => [
                'nullable',
                'required_unless:recurrence,none',
                'date',
                'after_or_equal:date',
                'before_or_equal:'.now()->addYears(2)->toDateString(),
            ],
        ]);

        $startsAt = Carbon::parse($data['date'].' '.$data['start_time']);
        $endsAt = Carbon::parse($data['date'].' '.$data['end_time']);

        if ($data['event_type'] === 'private_training_manual' && $staff->membership_type !== 'coach') {
            throw ValidationException::withMessages([
                'event_type' => 'ประเภท Private Training ใช้ได้เฉพาะ Schedule ของโค้ช',
            ]);
        }

        if ($data['start_time'] < self::OPEN_TIME || $data['end_time'] > self::CLOSE_TIME) {
            throw ValidationException::withMessages([
                'start_time' => 'เพิ่มกำหนดการได้เฉพาะเวลา 08:00–22:00 น.',
            ]);
        }

        if (! $existing && $startsAt->lte(now())) {
            throw ValidationException::withMessages([
                'start_time' => 'ไม่สามารถเพิ่มกำหนดการในช่วงเวลาที่ผ่านมาแล้วได้',
            ]);
        }

        if (! in_array($startsAt->minute, [0, 30], true) || ! in_array($endsAt->minute, [0, 30], true)) {
            throw ValidationException::withMessages([
                'start_time' => 'กรุณาเลือกเวลาเป็นช่วงละ 30 นาที',
            ]);
        }

        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'all_day' => false,
            'color' => strtolower($data['color']),
            'recurrence' => $data['recurrence'],
            'recurrence_days' => $data['recurrence'] === 'weekly'
                ? array_values(array_unique($data['recurrence_days'] ?? []))
                : null,
            'recurrence_until' => $data['recurrence'] === 'none' ? null : $data['recurrence_until'],
            'coach_id' => $staff->id,
            'coach_name' => null,
            'event_type' => $data['event_type'],
            'package_type' => null,
            'court_section_id' => null,
            'student_names' => null,
        ];
    }

    public function createEvent(User $staff, array $data): CalendarEvent
    {
        return DB::transaction(function () use ($staff, $data) {
            $staff = User::whereKey($staff->id)->lockForUpdate()->firstOrFail();
            $candidate = new CalendarEvent($data);
            $this->ensureEventIsFree($staff, $candidate);

            return $staff->calendarEvents()->create($data);
        });
    }

    public function updateEvent(User $staff, CalendarEvent $event, array $data): CalendarEvent
    {
        return DB::transaction(function () use ($staff, $event, $data) {
            $staff = User::whereKey($staff->id)->lockForUpdate()->firstOrFail();
            $candidate = new CalendarEvent($data);
            $this->ensureEventIsFree($staff, $candidate, $event->id);
            $event->update($data);

            return $event->refresh();
        });
    }

    public function validateAvailabilityRequest(Request $request, bool $allowPast = false): array
    {
        $dateRules = ['required', 'date'];
        if (! $allowPast) {
            $dateRules[] = 'after_or_equal:today';
        }

        $data = $request->validate([
            'date' => $dateRules,
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'detail' => ['nullable', 'string', 'max:255'],
        ]);

        $startsAt = Carbon::parse($data['date'].' '.$data['start_time']);
        $endsAt = Carbon::parse($data['date'].' '.$data['end_time']);

        if ($data['start_time'] < self::OPEN_TIME || $data['end_time'] > self::CLOSE_TIME) {
            throw ValidationException::withMessages([
                'start_time' => 'กำหนดช่วงไม่ว่างได้เฉพาะเวลา 08:00–22:00 น.',
            ]);
        }

        if (! $allowPast && $startsAt->lte(now())) {
            throw ValidationException::withMessages([
                'start_time' => 'ไม่สามารถเพิ่มช่วงเวลาที่ผ่านมาแล้วได้',
            ]);
        }

        if (! in_array($startsAt->minute, [0, 30], true) || ! in_array($endsAt->minute, [0, 30], true)) {
            throw ValidationException::withMessages([
                'start_time' => 'กรุณาเลือกเวลาเป็นช่วงละ 30 นาที',
            ]);
        }

        return $data;
    }

    public function createAvailability(User $staff, array $data): Availability
    {
        return DB::transaction(function () use ($staff, $data) {
            $staff = User::whereKey($staff->id)->lockForUpdate()->firstOrFail();
            $this->ensureSlotIsFree($staff, $data);

            return $staff->availabilities()->create($this->slotData($data));
        });
    }

    public function updateAvailability(User $staff, Availability $availability, array $data): Availability
    {
        return DB::transaction(function () use ($staff, $availability, $data) {
            $staff = User::whereKey($staff->id)->lockForUpdate()->firstOrFail();
            $this->ensureSlotIsFree($staff, $data, $availability->id);
            $availability->update($this->slotData($data));

            return $availability->refresh();
        });
    }

    private function ensureEventIsFree(User $staff, CalendarEvent $candidate, ?int $ignoreEventId = null): void
    {
        $seriesEnd = $candidate->recurrence_until?->copy()->endOfDay()
            ?? $candidate->starts_at->copy()->endOfDay();
        $checkingFrom = $ignoreEventId
            ? $candidate->starts_at->copy()->startOfDay()->max(now()->startOfDay())
            : $candidate->starts_at->copy()->startOfDay();
        $occurrences = $checkingFrom->lte($seriesEnd)
            ? $this->occurrences->between($candidate, $checkingFrom, $seriesEnd)
            : [];

        $availabilities = Availability::query()
            ->where('user_id', $staff->id)
            ->whereBetween('date', [$checkingFrom->toDateString(), $seriesEnd->toDateString()])
            ->lockForUpdate()
            ->get();
        $bookings = PrivateTrainingBooking::query()
            ->where(function ($query) use ($staff) {
                $query->where('coach_id', $staff->id)
                    ->orWhere('court_assistant_id', $staff->id);
            })
            ->whereBetween('date', [$checkingFrom->toDateString(), $seriesEnd->toDateString()])
            ->whereIn('status', ['pending', 'awaiting_court', 'confirmed'])
            ->lockForUpdate()
            ->get();
        $otherEvents = CalendarEvent::query()
            ->where('coach_id', $staff->id)
            ->when($ignoreEventId, fn ($query) => $query->where('id', '!=', $ignoreEventId))
            ->where('starts_at', '<=', $seriesEnd)
            ->where(function ($query) use ($checkingFrom) {
                $query->whereNull('recurrence_until')
                    ->orWhereDate('recurrence_until', '>=', $checkingFrom->toDateString());
            })
            ->lockForUpdate()
            ->get();

        foreach ($occurrences as $occurrence) {
            $start = $occurrence['start'];
            $end = $occurrence['end'];
            $date = $start->toDateString();

            $overlapsAvailability = $availabilities->contains(fn (Availability $slot) => $slot->date === $date
                && $slot->start_time < $end->format('H:i:s')
                && $slot->end_time > $start->format('H:i:s'));
            $overlapsBooking = $bookings->contains(fn (PrivateTrainingBooking $booking) => $booking->date->toDateString() === $date
                && $booking->start_time < $end->format('H:i:s')
                && $booking->end_time > $start->format('H:i:s'));
            $overlapsEvent = $otherEvents->contains(fn (CalendarEvent $event) => collect(
                $this->occurrences->between($event, $start->copy()->startOfDay(), $end->copy()->endOfDay())
            )->contains(fn (array $existing) => $existing['start']->lt($end) && $existing['end']->gt($start)));

            if ($overlapsAvailability || $overlapsBooking || $overlapsEvent) {
                throw ValidationException::withMessages([
                    'start_time' => 'กำหนดการทับกับรายการเดิมในวันที่ '.$start->format('d/m/Y').' กรุณาเลือกเวลาอื่น',
                ]);
            }
        }
    }

    private function ensureSlotIsFree(User $staff, array $data, ?int $ignoreAvailabilityId = null): void
    {
        $start = $data['start_time'].':00';
        $end = $data['end_time'].':00';

        $overlapsAnotherBlock = Availability::query()
            ->where('user_id', $staff->id)
            ->whereDate('date', $data['date'])
            ->when($ignoreAvailabilityId, fn ($query) => $query->where('id', '!=', $ignoreAvailabilityId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->lockForUpdate()
            ->exists();

        if ($overlapsAnotherBlock) {
            throw ValidationException::withMessages([
                'start_time' => 'ช่วงเวลานี้ทับกับ Schedule ที่บันทึกไว้แล้ว',
            ]);
        }

        $overlapsBooking = PrivateTrainingBooking::query()
            ->where(function ($query) use ($staff) {
                $query->where('coach_id', $staff->id)
                    ->orWhere('court_assistant_id', $staff->id);
            })
            ->whereDate('date', $data['date'])
            ->whereIn('status', ['pending', 'awaiting_court', 'confirmed'])
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->lockForUpdate()
            ->exists();

        if ($overlapsBooking) {
            throw ValidationException::withMessages([
                'start_time' => 'ช่วงเวลานี้มีคำขอ Private Training อยู่แล้ว จึงตั้งเป็นไม่ว่างไม่ได้',
            ]);
        }

        if ($this->occurrences->overlapsForCoach(
            $staff->id,
            Carbon::parse($data['date'].' '.$data['start_time']),
            Carbon::parse($data['date'].' '.$data['end_time']),
        )) {
            throw ValidationException::withMessages([
                'start_time' => 'ช่วงเวลานี้ทับกับกำหนดการใน Calendar แล้ว',
            ]);
        }
    }

    private function slotData(array $data): array
    {
        return [
            'date' => $data['date'],
            'start_time' => $data['start_time'].':00',
            'end_time' => $data['end_time'].':00',
            'status' => 'booked',
            'detail' => $data['detail'] ?? null,
        ];
    }
}
