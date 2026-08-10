<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CalendarEvent;
use App\Models\CourseCalendarOverride;
use App\Models\CourseSchedule;
use App\Models\CourtSection;
use App\Models\PrivateTrainingBooking;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CourtAvailabilityService
{
    private const DAY_KEYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

    public function __construct(
        private readonly CalendarEventOccurrenceService $occurrences,
    ) {}

    public function availableSectionsForPrivateBooking(PrivateTrainingBooking $booking): Collection
    {
        $start = Carbon::parse($booking->date->toDateString().' '.$booking->start_time);
        $end = Carbon::parse($booking->date->toDateString().' '.$booking->end_time);

        return CourtSection::with('court')
            ->where('is_active', true)
            ->whereHas('court', fn ($query) => $query->where('court_status', 'open'))
            ->get()
            ->filter(fn (CourtSection $section) => $this->isSectionAvailable(
                $section,
                $start,
                $end,
                $booking->id,
            ))
            ->values();
    }

    public function isSectionAvailable(
        CourtSection $section,
        Carbon $start,
        Carbon $end,
        ?int $ignorePrivateBookingId = null,
    ): bool {
        $section->loadMissing('court');
        if (! $section->is_active || $section->court->court_status !== 'open') {
            return false;
        }

        if ($section->court->isClosedAt($start, $end, $section)) {
            return false;
        }

        $date = $start->toDateString();
        $startTime = $start->format('H:i:s');
        $endTime = $end->format('H:i:s');
        $conflictingIds = $section->conflictingSectionIds();

        if (Booking::overlappingSection($section, $date, $startTime, $endTime)->exists()) {
            return false;
        }

        if (PrivateTrainingBooking::query()
            ->when($ignorePrivateBookingId, fn ($query) => $query->whereKeyNot($ignorePrivateBookingId))
            ->whereIn('court_section_id', $conflictingIds)
            ->whereDate('date', $date)
            ->where('status', 'confirmed')
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists()) {
            return false;
        }

        if ($this->calendarBlocksCourt($conflictingIds, $start, $end)) {
            return false;
        }

        return ! $this->courseScheduleBlocksCourt($conflictingIds, $start, $end);
    }

    private function calendarBlocksCourt(array $sectionIds, Carbon $start, Carbon $end): bool
    {
        return CalendarEvent::query()
            ->whereIn('court_section_id', $sectionIds)
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
            ->get()
            ->contains(fn (CalendarEvent $event) => collect(
                $this->occurrences->between($event, $start->copy()->startOfDay(), $end->copy()->endOfDay())
            )->contains(fn (array $occurrence) => $occurrence['start']->lt($end) && $occurrence['end']->gt($start)));
    }

    private function courseScheduleBlocksCourt(array $sectionIds, Carbon $start, Carbon $end): bool
    {
        $date = $start->toDateString();
        $dayKey = self::DAY_KEYS[$start->dayOfWeek];

        return CourseSchedule::query()
            ->with(['courtSection', 'course'])
            ->whereNotNull('court_section_id')
            ->get()
            ->contains(function (CourseSchedule $schedule) use ($sectionIds, $start, $end, $date, $dayKey) {
                $days = $schedule->weekdays ?: ($schedule->day_type === 'weekday'
                    ? ['mon', 'wed', 'fri']
                    : ['sat', 'sun']);

                if (! in_array($dayKey, $days, true)) {
                    return false;
                }

                $override = CourseCalendarOverride::query()
                    ->where('course_schedule_id', $schedule->id)
                    ->whereDate('occurrence_date', $date)
                    ->first();
                $sectionId = $override?->court_section_id ?? $schedule->court_section_id;

                if (! $sectionId || ! in_array($sectionId, $sectionIds, true)) {
                    return false;
                }

                $courseStart = $override?->starts_at
                    ?? Carbon::parse($date.' '.$schedule->start_time);
                $courseEnd = $override?->ends_at
                    ?? Carbon::parse($date.' '.$schedule->end_time);

                return $courseStart->lt($end) && $courseEnd->gt($start);
            });
    }
}
