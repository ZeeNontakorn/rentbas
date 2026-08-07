<?php

namespace App\Services;

use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarEventOccurrenceService
{
    private const WEEKDAYS = [
        'sun' => Carbon::SUNDAY,
        'mon' => Carbon::MONDAY,
        'tue' => Carbon::TUESDAY,
        'wed' => Carbon::WEDNESDAY,
        'thu' => Carbon::THURSDAY,
        'fri' => Carbon::FRIDAY,
        'sat' => Carbon::SATURDAY,
    ];

    /**
     * คืน occurrence จริงที่อยู่ในช่วงปฏิทินที่ร้องขอ
     * แต่ละรายการประกอบด้วย start, end และ occurrenceKey
     */
    public function between(CalendarEvent $event, Carbon $from, Carbon $until): array
    {
        $from = $from->copy();
        $until = $until->copy();
        $seriesEnd = $event->recurrence_until
            ? $event->recurrence_until->copy()->endOfDay()->min($until)
            : $until;

        if ($event->starts_at->gt($seriesEnd)) {
            return [];
        }

        if ($event->recurrence === 'none') {
            return $this->overlapsRange($event->starts_at, $this->occurrenceEnd($event, $event->starts_at), $from, $until)
                ? [$this->occurrence($event, $event->starts_at)]
                : [];
        }

        if ($event->recurrence === 'weekly') {
            return $this->weeklyOccurrences($event, $from, $seriesEnd);
        }

        $occurrences = [];
        $start = $event->starts_at->copy();
        $monthIndex = 0;

        if ($event->recurrence === 'daily' && $start->lt($from)) {
            $days = (int) max(0, $start->copy()->startOfDay()->diffInDays($from->copy()->startOfDay()));
            $start->addDays($days);
        }

        if ($event->recurrence === 'monthly') {
            while ($this->occurrenceEnd($event, $start)->lt($from)) {
                $monthIndex++;
                $start = $event->starts_at->copy()->addMonthsNoOverflow($monthIndex);
            }
        }

        while ($start->lte($seriesEnd)) {
            $end = $this->occurrenceEnd($event, $start);
            if ($this->overlapsRange($start, $end, $from, $until)) {
                $occurrences[] = $this->occurrence($event, $start);
            }

            if ($event->recurrence === 'daily') {
                $start->addDay();
            } else {
                $monthIndex++;
                $start = $event->starts_at->copy()->addMonthsNoOverflow($monthIndex);
            }
        }

        return $occurrences;
    }

    public function forCoachesBetween(Collection|array $coachIds, Carbon $from, Carbon $until): Collection
    {
        return CalendarEvent::with('coach')
            ->whereIn('coach_id', collect($coachIds)->values())
            ->where('starts_at', '<', $until)
            ->where(function ($query) use ($from) {
                $query->where('recurrence', '!=', 'none')
                    ->orWhereNull('ends_at')
                    ->orWhere('ends_at', '>', $from);
            })
            ->where(function ($query) use ($from) {
                $query->whereNull('recurrence_until')
                    ->orWhereDate('recurrence_until', '>=', $from->toDateString());
            })
            ->get()
            ->flatMap(fn (CalendarEvent $event) => collect($this->between($event, $from, $until))
                ->map(fn (array $occurrence) => $occurrence + ['event' => $event]));
    }

    public function overlapsForCoach(
        int $coachId,
        Carbon $start,
        Carbon $end,
        ?int $ignoreEventId = null,
    ): bool {
        $events = CalendarEvent::query()
            ->where('coach_id', $coachId)
            ->when($ignoreEventId, fn ($query) => $query->where('id', '!=', $ignoreEventId))
            ->where('starts_at', '<', $end)
            ->where(function ($query) use ($start) {
                $query->where('recurrence', '!=', 'none')
                    ->orWhereNull('ends_at')
                    ->orWhere('ends_at', '>', $start);
            })
            ->where(function ($query) use ($start) {
                $query->whereNull('recurrence_until')
                    ->orWhereDate('recurrence_until', '>=', $start->toDateString());
            })
            ->get();

        return $events->contains(fn (CalendarEvent $event) => collect(
            $this->between($event, $start->copy()->startOfDay(), $end->copy()->endOfDay())
        )->contains(fn (array $occurrence) => $this->overlapsRange(
            $occurrence['start'],
            $occurrence['end'],
            $start,
            $end,
        )));
    }

    private function weeklyOccurrences(CalendarEvent $event, Carbon $from, Carbon $seriesEnd): array
    {
        $selectedDays = collect($event->recurrence_days ?: [])
            ->filter(fn (string $day) => isset(self::WEEKDAYS[$day]))
            ->map(fn (string $day) => self::WEEKDAYS[$day])
            ->values()
            ->all();

        // ข้อมูลเก่าที่ไม่มี recurrence_days ให้ทำซ้ำวันเดียวตาม starts_at เหมือนเดิม
        if ($selectedDays === []) {
            $selectedDays = [$event->starts_at->dayOfWeek];
        }

        $cursor = $from->copy()->startOfDay()->max($event->starts_at->copy()->startOfDay());
        $occurrences = [];

        while ($cursor->lte($seriesEnd)) {
            if (in_array($cursor->dayOfWeek, $selectedDays, true)) {
                $start = $cursor->copy()->setTimeFrom($event->starts_at);
                $end = $this->occurrenceEnd($event, $start);

                if ($start->gte($event->starts_at) && $this->overlapsRange($start, $end, $from, $seriesEnd)) {
                    $occurrences[] = $this->occurrence($event, $start);
                }
            }

            $cursor->addDay();
        }

        return $occurrences;
    }

    private function occurrence(CalendarEvent $event, Carbon $start): array
    {
        return [
            'start' => $start->copy(),
            'end' => $this->occurrenceEnd($event, $start),
            'occurrenceKey' => $start->format('YmdHis'),
        ];
    }

    private function occurrenceEnd(CalendarEvent $event, Carbon $start): Carbon
    {
        if (! $event->ends_at) {
            return $event->all_day
                ? $start->copy()->addDay()
                : $start->copy()->addMinutes(30);
        }

        return $start->copy()->addSeconds((int) $event->starts_at->diffInSeconds($event->ends_at));
    }

    private function overlapsRange(Carbon $start, Carbon $end, Carbon $from, Carbon $until): bool
    {
        return $start->lt($until) && $end->gt($from);
    }
}
