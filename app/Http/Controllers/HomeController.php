<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Course;
use App\Models\Court;
use App\Models\CourtClosure;
use App\Models\SiteVisit;
use Carbon\Carbon;
use Carbon\CarbonInterface;
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

        $scheduleCourses = $trainingCourses->where('course_type', 'schedule')->values();
        $sessionCourses = $trainingCourses->where('course_type', 'session')->values();

        return view('home', compact('courts', 'trainingCourses', 'scheduleCourses', 'sessionCourses'));
    }

    /**
     * Public API – return slot status per court for a given date
     * status: available | booked | past | closed
     */
    public function schedule(Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        /** @var Court[] $courts */
        $courts = Court::orderBy('name')->get();
        $now = now();

        // ดึงการจองทั้งหมดของวันนี้ พร้อม court_section_id/end_time เพื่อเช็ค overlap ให้ถูกต้อง
        // (แทนการเทียบ start_time ตรงๆ แบบเดิม ซึ่งพลาดเคสจองครึ่งสนาม/เวลาไม่เต็มชั่วโมง)
        $bookings = Booking::whereDate('booking_date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->get(['court_id', 'court_section_id', 'start_time', 'end_time']);

        $slots = []; // [court_id][start_time] => status  (คีย์เป็นทุกๆ 30 นาที ให้ตรงกับตารางฝั่ง frontend)
        foreach ($courts as $court) {
            $fullSection = $court->defaultSection();
            $conflictIds = $fullSection ? $fullSection->conflictingSectionIds() : [];

            $courtBookings = $bookings->where('court_id', $court->id);

            for ($m = 6 * 60; $m < 22 * 60; $m += 30) {
                $startStr = sprintf('%02d:%02d:00', intdiv($m, 60), $m % 60);
                $endM = $m + 30;
                $endStr = sprintf('%02d:%02d:00', intdiv($endM, 60), $endM % 60);

                $slotStart = Carbon::parse("{$date} {$startStr}");
                $slotEnd = Carbon::parse("{$date} {$endStr}");

                $closuresType = $court->getClosureType($startStr, $endStr, $date, $fullSection);

                if ($slotEnd->lte($now)) {
                    $status = 'past';
                } elseif ($court->isClosedAt($slotStart, $slotEnd, $fullSection)) {
                    $status = $closuresType ?? 'closed';
                } else {
                    // ถือว่า booked ถ้ามีการจองใดๆ (เต็มสนามหรือครึ่งสนามที่บล็อกกัน) ทับซ้อนกับ 30 นาทีนี้อยู่
                    $overlap = $courtBookings->first(function ($b) use ($conflictIds, $startStr, $endStr) {
                        return in_array($b->court_section_id, $conflictIds, true)
                            && $b->start_time < $endStr && $b->end_time > $startStr;
                    });
                    $status = $overlap ? 'booked' : 'available';
                }
                $slots[$court->id][$startStr] = $status;
            }
        }

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
        $end = $start->copy()->endOfMonth();
        $now = now();
        $today = $now->copy()->startOfDay();

        $courts = Court::orderBy('name')->get();

        // preload การจองทั้งเดือน → group by "date|court_id" พร้อม start/end/section เพื่อเช็ค overlap
        $bookings = Booking::whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', ['pending', 'approved'])
            ->get(['booking_date', 'court_id', 'court_section_id', 'start_time', 'end_time']);
        $bookingsByDateCourt = [];
        foreach ($bookings as $b) {
            $bd = $b->booking_date instanceof CarbonInterface
                ? $b->booking_date->toDateString()
                : substr((string) $b->booking_date, 0, 10);
            $bookingsByDateCourt["{$bd}|{$b->court_id}"][] = $b;
        }

        // preload closures ทั้งเดือน (กันยิง query ต่อ slot) พร้อม court_section_id เพื่อไม่ให้ปิดเกินส่วนที่ระบุ
        $closures = CourtClosure::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['court_id', 'date', 'start_time', 'end_time', 'court_section_id']);

        // เตรียม conflict-ids ของ section "เต็มสนาม" ต่อสนามไว้ล่วงหน้า (ใช้ร่วมกันทุกวัน/ทุกชั่วโมง)
        $conflictIdsByCourt = [];
        foreach ($courts as $court) {
            $fullSection = $court->defaultSection();
            $conflictIdsByCourt[$court->id] = $fullSection ? $fullSection->conflictingSectionIds() : [];
        }

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $ds = $d->toDateString();

            if ($d->lt($today)) {          // ทั้งวันผ่านไปแล้ว → ไม่มีจุด
                $days[$ds] = 'past';

                continue;
            }

            $available = 0;
            foreach ($courts as $court) {
                $conflictIds = $conflictIdsByCourt[$court->id];
                $dayCourtBookings = $bookingsByDateCourt["{$ds}|{$court->id}"] ?? [];

                for ($m = 6 * 60; $m < 22 * 60; $m += 30) {
                    $startStr = sprintf('%02d:%02d:00', intdiv($m, 60), $m % 60);
                    $endM = $m + 30;
                    $endStr = sprintf('%02d:%02d:00', intdiv($endM, 60), $endM % 60);
                    $slotStart = Carbon::parse("{$ds} {$startStr}");
                    $slotEnd = Carbon::parse("{$ds} {$endStr}");

                    if ($slotEnd->lte($now)) {
                        continue;
                    }                                                              // past slot
                    if ($this->slotClosed($court, $closures, $ds, $startStr, $endStr, $slotStart, $slotEnd, $conflictIds)) {
                        continue;
                    } // closed

                    $overlap = false;
                    foreach ($dayCourtBookings as $b) {
                        if (in_array($b->court_section_id, $conflictIds, true)
                            && $b->start_time < $endStr && $b->end_time > $startStr) {
                            $overlap = true;
                            break;
                        }
                    }
                    if ($overlap) {
                        continue;
                    } // booked (เต็มสนามหรือครึ่งสนามที่บล็อกกัน)

                    $available++;
                }
            }

            $days[$ds] = $available > 0 ? 'free' : 'full';
        }

        return response()->json(['days' => $days]);
    }

    /**
     * In-memory มิเรอร์ของ Court::isClosedAt() ใช้ closures ที่ preload มาแล้ว
     * เช็ค court_section_id ของ closure ด้วยว่าทับซ้อนกับ section ที่กำลังพิจารณาหรือไม่
     * (closure ที่ปิดเฉพาะครึ่งสนามอื่นที่ไม่บล็อกกันไม่ควรทำให้ทั้งชั่วโมงถูกนับว่าปิด)
     */
    private function slotClosed(Court $court, $closures, string $ds, string $startStr, string $endStr, $slotStart, $slotEnd, array $conflictIds = []): bool
    {
        if ($court->court_status === 'closed') {
            return true;
        }
        if ($court->closed_from && $court->closed_until
            && $slotStart->lt($court->closed_until) && $slotEnd->gt($court->closed_from)) {
            return true;
        }
        foreach ($closures as $c) {
            $cDate = $c->date instanceof CarbonInterface
                ? $c->date->toDateString()
                : substr((string) $c->date, 0, 10);
            if ($c->court_id != $court->id || $cDate !== $ds) {
                continue;
            }
            if ($c->start_time >= $endStr || $c->end_time <= $startStr) {
                continue;
            }
            if ($c->court_section_id !== null && ! in_array($c->court_section_id, $conflictIds, true)) {
                continue;
            }

            return true;
        }

        return false;
    }
}
