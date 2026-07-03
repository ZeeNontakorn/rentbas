<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Court;
use App\Models\SiteVisit;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // Track unique visit per session
        if (!session()->has('has_visited')) {
            SiteVisit::create([]);
            session()->put('has_visited', true);
        }

        $courts = Court::orderBy('name')->get();

        return view('home', compact('courts'));
    }

    /**
     * Public API – return slot status per court for a given date
     * status: available | booked | past | closed
     */
    public function schedule(Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        /** @var \App\Models\Court[] $courts */
        $courts = Court::orderBy('name')->get();
        $now    = now();

        // Collect all booked start_times per court for this date
        $bookings = \App\Models\Booking::whereDate('booking_date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->get(['court_id', 'start_time']);

        $slots = []; // [court_id][start_time] => status

        foreach ($courts as $court) {
            $courtBookedTimes = $bookings
                ->where('court_id', $court->id)
                ->pluck('start_time')
                ->toArray();

            for ($h = 6; $h < 22; $h++) {
                $startStr = sprintf('%02d:00:00', $h);
                $endStr   = sprintf('%02d:00:00', $h + 1);

                $slotStart = \Carbon\Carbon::parse("{$date} {$startStr}");
                $slotEnd   = \Carbon\Carbon::parse("{$date} {$endStr}");

                if ($slotEnd->lte($now)) {
                    $status = 'past';
                } elseif ($court->isClosedAt($slotStart, $slotEnd)) {
                    $status = 'closed';
                } elseif (in_array($startStr, $courtBookedTimes)) {
                    $status = 'booked';
                } else {
                    $status = 'available';
                }

                $slots[$court->id][$startStr] = $status;
            }
        }

        return response()->json(['slots' => $slots]);
    }
}
