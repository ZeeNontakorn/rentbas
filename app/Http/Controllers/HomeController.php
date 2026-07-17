<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Course;
use App\Models\Court;
use App\Models\CourtClosure;
use App\Models\SiteVisit;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // Insert a visit record once per browser session.
        if (! session()->has('site_visit_recorded')) {
            SiteVisit::create();
            session()->put('site_visit_recorded', true);
        }

        $courts = Court::all()->sortBy(function ($court) {
            return $court->name;
        }, SORT_NATURAL | SORT_FLAG_CASE)->values();

        // คอร์สเรียนบาสเกตบอลที่จะโชว์บนหน้าแรก: เอาเฉพาะคอร์สที่มีแพ็กเกจ "เปิดใช้งาน" (is_active) อยู่
        $trainingCourses = Course::with(['targetGroups', 'schedules', 'packages' => function ($query) {
                $query->where('is_active', true)->orderByDesc('is_featured')->orderBy('sort_order');
            }])
            ->whereHas('packages', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('course_name')
            ->get();

        return view('home', compact('courts', 'trainingCourses'));
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
        $closures = ''; // [court_id][start_time] => closure_type
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

                $closuresType = $court->getClosureType($slotStart, $slotEnd, $date);

                if ($slotEnd->lte($now)) {
                    $status = 'past';
                } elseif ($court->isClosedAt($slotStart, $slotEnd)) {
                    $status = $closuresType ?? 'closed';
                } elseif (in_array($startStr, $courtBookedTimes)) {
                    $status = 'booked';
                } else {
                    $status = 'available';
                }
                 $slots[$court->id][$startStr] = $status;
            }
        }
                $startStr = sprintf('%02d:00:00', 6);
                $endStr   = sprintf('%02d:00:00', 6 + 1);


        return response()->json(['slots' => $slots]);
    }

    /**
     * Public API – per-day availability for a whole month (calendar dots).
     * status: free (มีอย่างน้อย 1 slot ว่าง) | full (เต็มทุก slot) | past (ทั้งวันผ่านไปแล้ว)
     */
    public function monthAvailability(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));
        try {
            $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        } catch (\Exception $e) {
            $start = now()->startOfMonth();
        }
        $end   = $start->copy()->endOfMonth();
        $now   = now();
        $today = $now->copy()->startOfDay();

        $courts = Court::orderBy('name')->get();

        // preload การจองทั้งเดือน → set: "date|court_id|HH:MM:SS"
        $bookings = Booking::whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', ['pending', 'approved'])
            ->get(['booking_date', 'court_id', 'start_time']);
        $bookedSet = [];
        foreach ($bookings as $b) {
            $bd = $b->booking_date instanceof \Carbon\CarbonInterface
                ? $b->booking_date->toDateString()
                : substr((string) $b->booking_date, 0, 10);
            $bookedSet["{$bd}|{$b->court_id}|{$b->start_time}"] = true;
        }

        // preload closures ทั้งเดือน (กันยิง query ต่อ slot)
        $closures = CourtClosure::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['court_id', 'date', 'start_time', 'end_time']);

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $ds = $d->toDateString();

            if ($d->lt($today)) {          // ทั้งวันผ่านไปแล้ว → ไม่มีจุด
                $days[$ds] = 'past';
                continue;
            }

            $available = 0;
            foreach ($courts as $court) {
                for ($h = 6; $h < 22; $h++) {
                    $startStr  = sprintf('%02d:00:00', $h);
                    $endStr    = sprintf('%02d:00:00', $h + 1);
                    $slotStart = Carbon::parse("{$ds} {$startStr}");
                    $slotEnd   = Carbon::parse("{$ds} {$endStr}");

                    if ($slotEnd->lte($now)) continue;                                   // past slot
                    if ($this->slotClosed($court, $closures, $ds, $startStr, $endStr, $slotStart, $slotEnd)) continue; // closed
                    if (isset($bookedSet["{$ds}|{$court->id}|{$startStr}"])) continue;    // booked

                    $available++;
                }
            }

            $days[$ds] = $available > 0 ? 'free' : 'full';
        }

        return response()->json(['days' => $days]);
    }

    /**
     * In-memory มิเรอร์ของ Court::isClosedAt() ใช้ closures ที่ preload มาแล้ว
     */
    private function slotClosed(Court $court, $closures, string $ds, string $startStr, string $endStr, $slotStart, $slotEnd): bool
    {
        if ($court->court_status === 'closed') {
            return true;
        }
        if ($court->closed_from && $court->closed_until
            && $slotStart->lt($court->closed_until) && $slotEnd->gt($court->closed_from)) {
            return true;
        }
        foreach ($closures as $c) {
            $cDate = $c->date instanceof \Carbon\CarbonInterface
                ? $c->date->toDateString()
                : substr((string) $c->date, 0, 10);
            if ($c->court_id == $court->id && $cDate === $ds
                && $c->start_time < $endStr && $c->end_time > $startStr) {
                return true;
            }
        }
        return false;
    }
}