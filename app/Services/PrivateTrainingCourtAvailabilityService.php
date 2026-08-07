<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSection;
use App\Models\PrivateTrainingBooking;
use Carbon\Carbon;

class PrivateTrainingCourtAvailabilityService
{
    /**
     * Return court sections that are free for the requested private training slot.
     *
     * @return array<int, array{id:int, court_id:int, court_name:string, section_id:int, section_name:string, section_code:string}>
     */
    public function getAvailableSections(PrivateTrainingBooking $booking): array
    {
        $date = $booking->date instanceof \Carbon\CarbonInterface ? $booking->date->toDateString() : (string) $booking->date;
        $start = $booking->start_time;
        $end = $booking->end_time;

        $courts = Court::where('court_status', 'open')
            ->with(['sections' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get();

        $available = [];

        foreach ($courts as $court) {
            foreach ($court->sections as $section) {
                $from = Carbon::parse($date . ' ' . $start);
                $until = Carbon::parse($date . ' ' . $end);

                if ($court->isClosedAt($from, $until, $section)) {
                    continue;
                }

                $courtBusy = Booking::overlappingSection($section, $date, $start, $end)->exists();
                $privateBusy = PrivateTrainingBooking::whereKeyNot($booking->id)
                    ->whereIn('court_section_id', $section->conflictingSectionIds())
                    ->whereDate('date', $date)
                    ->where('status', 'confirmed')
                    ->where('start_time', '<', $end)
                    ->where('end_time', '>', $start)
                    ->exists();

                if ($courtBusy || $privateBusy) {
                    continue;
                }

                $available[] = [
                    'id' => $section->id,
                    'court_id' => $court->id,
                    'court_name' => $court->name,
                    'section_id' => $section->id,
                    'section_name' => $section->name,
                    'section_code' => $section->code,
                ];
            }
        }

        return $available;
    }
}
