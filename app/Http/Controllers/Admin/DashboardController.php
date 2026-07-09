<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClosure;
use App\Models\SiteVisit;
use App\Models\User;
use ArielMejiaDev\LarapexCharts\LarapexChart;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /** Operating hours used across all utilization/occupancy calculations. */
    private const OPEN_HOUR = 8;   // 08:00
    private const CLOSE_HOUR = 22; // 22:00

    /** Statuses that count as a "real" booking sitting on the calendar. */
    private const COUNTABLE = ['approved', 'pending'];

    private function getCommonStats()
    {
        $today = now()->toDateString();

        return [
            'today_total' => Booking::whereDate('booking_date', $today)->count(),
            'today_pending' => Booking::whereDate('booking_date', $today)->where('status', 'pending')->count(),
            'today_approved' => Booking::whereDate('booking_date', $today)->where('status', 'approved')->count(),
            'today_cancelled' => Booking::whereDate('booking_date', $today)->whereIn('status', ['rejected', 'cancelled'])->count(),
        ];
    }

    /** Duration of a booking in hours from its HH:MM:SS time strings. */
    private function durationHours(string $start, string $end): float
    {
        return max((strtotime($end) - strtotime($start)) / 3600, 0);
    }

    public function index()
    {
        $now = now();
        $todayStr = $now->toDateString();
        $hoursPerDay = self::CLOSE_HOUR - self::OPEN_HOUR; // 14

        $courts = Court::orderBy('id')->get();
        $courtCount = max($courts->count(), 1);

        // ---------------------------------------------------------------
        // KPI 1 — Today's Bookings + 7-day sparkline
        // ---------------------------------------------------------------
        $last7 = Booking::where('booking_date', '>=', $now->copy()->subDays(6)->toDateString())
            ->whereIn('status', self::COUNTABLE)
            ->get(['booking_date']);
        $sparkByDate = $last7->groupBy(fn($b) => $b->booking_date->toDateString())->map->count();

        $spark = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i)->toDateString();
            $spark[] = (int) ($sparkByDate[$d] ?? 0);
        }
        $todayBookings = end($spark);
        $prevBookings = $spark[count($spark) - 2] ?? 0;
        $bookingsTrend = $this->pctChange($todayBookings, $prevBookings);

        // ---------------------------------------------------------------
        // KPI 2 — Court Utilization Rate (today, approved hours / capacity)
        // ---------------------------------------------------------------
        $todayApproved = Booking::whereDate('booking_date', $todayStr)
            ->where('status', 'approved')
            ->get(['start_time', 'end_time', 'court_id']);
        $todayBookedHours = $todayApproved->sum(fn($b) => $this->durationHours($b->start_time, $b->end_time));
        $capacityToday = $courtCount * $hoursPerDay;
        $utilizationRate = $capacityToday > 0 ? round(min($todayBookedHours / $capacityToday * 100, 100)) : 0;

        // yesterday utilization for the trend arrow
        $ydayApproved = Booking::whereDate('booking_date', $now->copy()->subDay()->toDateString())
            ->where('status', 'approved')->get(['start_time', 'end_time']);
        $ydayHours = $ydayApproved->sum(fn($b) => $this->durationHours($b->start_time, $b->end_time));
        $ydayUtil = $capacityToday > 0 ? min($ydayHours / $capacityToday * 100, 100) : 0;
        $utilizationTrend = $this->pctChange($utilizationRate, $ydayUtil);

        // ---------------------------------------------------------------
        // KPI 3 — Booked Hours (this month, approved)
        // ---------------------------------------------------------------
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd = $now->copy()->endOfMonth()->toDateString();
        $monthApproved = Booking::whereBetween('booking_date', [$monthStart, $monthEnd])
            ->where('status', 'approved')
            ->get(['start_time', 'end_time', 'booking_date', 'court_id', 'user_id']);
        $bookedHours = round($monthApproved->sum(fn($b) => $this->durationHours($b->start_time, $b->end_time)));

        $prevMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $prevMonthEnd = $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();
        $prevMonthHours = Booking::whereBetween('booking_date', [$prevMonthStart, $prevMonthEnd])
            ->where('status', 'approved')->get(['start_time', 'end_time'])
            ->sum(fn($b) => $this->durationHours($b->start_time, $b->end_time));
        $bookedHoursTrend = $this->pctChange($bookedHours, $prevMonthHours);

        // ---------------------------------------------------------------
        // KPI 4 — Active Customers (distinct users, last 30 days)
        // ---------------------------------------------------------------
        $activeCustomers = Booking::where('booking_date', '>=', $now->copy()->subDays(30)->toDateString())
            ->whereIn('status', self::COUNTABLE)
            ->distinct('user_id')->count('user_id');
        $prevActive = Booking::whereBetween('booking_date', [
            $now->copy()->subDays(60)->toDateString(),
            $now->copy()->subDays(31)->toDateString(),
        ])->whereIn('status', self::COUNTABLE)->distinct('user_id')->count('user_id');
        $activeTrend = $this->pctChange($activeCustomers, $prevActive);

        // ---------------------------------------------------------------
        // KPI 5 — Pending Bookings (today onward, awaiting approval)
        // ---------------------------------------------------------------
        $pendingBookings = Booking::where('booking_date', '>=', $todayStr)
            ->where('status', 'pending')->count();

        // ---------------------------------------------------------------
        // KPI 6 — Cancellation Rate (this month)
        // ---------------------------------------------------------------
        $monthTotal = Booking::whereBetween('booking_date', [$monthStart, $monthEnd])->count();
        $monthCancelled = Booking::whereBetween('booking_date', [$monthStart, $monthEnd])
            ->whereIn('status', ['cancelled', 'rejected'])->count();
        $cancellationRate = $monthTotal > 0 ? round($monthCancelled / $monthTotal * 100, 1) : 0;

        $prevMonthTotal = Booking::whereBetween('booking_date', [$prevMonthStart, $prevMonthEnd])->count();
        $prevMonthCancelled = Booking::whereBetween('booking_date', [$prevMonthStart, $prevMonthEnd])
            ->whereIn('status', ['cancelled', 'rejected'])->count();
        $prevCancelRate = $prevMonthTotal > 0 ? $prevMonthCancelled / $prevMonthTotal * 100 : 0;
        $cancellationTrend = $this->pctChange($cancellationRate, $prevCancelRate);

        $kpis = [
            'today_bookings' => ['value' => $todayBookings, 'trend' => $bookingsTrend, 'spark' => $spark],
            'utilization' => ['value' => $utilizationRate, 'trend' => $utilizationTrend],
            'booked_hours' => ['value' => $bookedHours, 'trend' => $bookedHoursTrend],
            'active_customers' => ['value' => $activeCustomers, 'trend' => $activeTrend],
            'pending' => ['value' => $pendingBookings],
            'cancellation_rate' => ['value' => $cancellationRate, 'trend' => $cancellationTrend],
        ];

        // ---------------------------------------------------------------
        // Booking Trend — daily bookings, last 30 days (area chart)
        // ---------------------------------------------------------------
        $last30 = Booking::where('booking_date', '>=', $now->copy()->subDays(29)->toDateString())
            ->whereIn('status', self::COUNTABLE)
            ->get(['booking_date']);
        $trendByDate = $last30->groupBy(fn($b) => $b->booking_date->toDateString())->map->count();

        $trend = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i);
            $trend[] = [
                'label' => $d->format('M j'),
                'count' => (int) ($trendByDate[$d->toDateString()] ?? 0),
            ];
        }
        $trendTotal = array_sum(array_column($trend, 'count'));

        // ---------------------------------------------------------------
        // Cancellation Analysis — categorised (real where possible)
        // ---------------------------------------------------------------
        $cancelRows = Booking::whereBetween('booking_date', [
            $now->copy()->subDays(30)->toDateString(),
            $todayStr,
        ])->whereIn('status', ['cancelled', 'rejected'])->get(['status', 'reject_reason']);

        $cancel = ['Customer Cancel' => 0, 'Maintenance' => 0];
        foreach ($cancelRows as $r) {
            $reason = mb_strtolower($r->reject_reason ?? '');
            if (str_contains($reason, 'ซ่อม') || str_contains($reason, 'mainten') || str_contains($reason, 'ปิด')) {
                $cancel['Maintenance']++;
            } else {
                // status 'cancelled' or an uncategorised admin rejection
                $cancel['Customer Cancel']++;
            }
        }
        $cancelIsMock = array_sum($cancel) === 0;
        if ($cancelIsMock) {
            // No cancellation data yet — show representative sample.
            $cancel = ['Customer Cancel' => 70, 'Maintenance' => 30];
        }
        $cancelTotal = array_sum($cancel);

        // ---------------------------------------------------------------
        // Monthly Booking Volume — last 12 months + YoY
        // ---------------------------------------------------------------
        $since = $now->copy()->subMonths(23)->startOfMonth();
        $monthlyRows = Booking::where('booking_date', '>=', $since->toDateString())
            ->whereIn('status', self::COUNTABLE)
            ->get(['booking_date']);
        $byMonth = $monthlyRows->groupBy(fn($b) => $b->booking_date->format('Y-m'))->map->count();

        $monthly = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = $now->copy()->subMonths($i);
            $monthly[] = [
                'label' => $m->format('M'),
                'count' => (int) ($byMonth[$m->format('Y-m')] ?? 0),
            ];
        }
        $last12Sum = array_sum(array_column($monthly, 'count'));
        $prev12Sum = 0;
        for ($i = 23; $i >= 12; $i--) {
            $m = $now->copy()->subMonths($i);
            $prev12Sum += (int) ($byMonth[$m->format('Y-m')] ?? 0);
        }
        $yoy = $this->pctChange($last12Sum, $prev12Sum);

        // ---------------------------------------------------------------
        // Peak Booking Hours — utilization per hour slot, today
        // ---------------------------------------------------------------
        $peakHours = [];
        for ($h = self::OPEN_HOUR; $h < self::CLOSE_HOUR; $h++) {
            $slotStart = sprintf('%02d:00:00', $h);
            $slotEnd = sprintf('%02d:00:00', $h + 1);
            $busy = $todayApproved->filter(
                fn($b) => $b->start_time < $slotEnd && $b->end_time > $slotStart
            )->count();
            $peakHours[] = [
                'label' => sprintf('%02d:00', $h),
                'pct' => (int) round(min($busy / $courtCount * 100, 100)),
            ];
        }

        // ---------------------------------------------------------------
        // Court Utilization — per court, this week (rings)
        // ---------------------------------------------------------------
        $weekStart = $now->copy()->startOfWeek()->toDateString();
        $weekEnd = $now->copy()->endOfWeek()->toDateString();
        $weekApproved = Booking::whereBetween('booking_date', [$weekStart, $weekEnd])
            ->where('status', 'approved')
            ->get(['start_time', 'end_time', 'court_id']);
        $weekCapacity = $hoursPerDay * 7;

        $courtUtil = [];
        foreach ($courts as $court) {
            $hrs = $weekApproved->where('court_id', $court->id)
                ->sum(fn($b) => $this->durationHours($b->start_time, $b->end_time));
            $courtUtil[] = [
                'name' => $court->name,
                'hours' => round($hrs),
                'pct' => $weekCapacity > 0 ? (int) round(min($hrs / $weekCapacity * 100, 100)) : 0,
            ];
        }

        // ---------------------------------------------------------------
        // Occupancy Timeline — court x hour grid, today
        // ---------------------------------------------------------------
        $todayOccupancy = Booking::whereDate('booking_date', $todayStr)
            ->where('status', 'approved')
            ->get(['start_time', 'end_time', 'court_id']);
        $todayClosures = CourtClosure::whereDate('date', $todayStr)
            ->get(['court_id', 'start_time', 'end_time']);

        $occHours = range(self::OPEN_HOUR, self::CLOSE_HOUR - 1);
        $occupancy = [];
        foreach ($courts as $court) {
            $cells = [];
            $fullyClosed = $court->court_status === 'closed';
            foreach ($occHours as $h) {
                $slotStart = sprintf('%02d:00:00', $h);
                $slotEnd = sprintf('%02d:00:00', $h + 1);

                $isMaint = $fullyClosed || $todayClosures->first(
                    fn($c) => $c->court_id === $court->id && $c->start_time < $slotEnd && $c->end_time > $slotStart
                ) !== null;
                $isBusy = $todayOccupancy->first(
                    fn($b) => $b->court_id === $court->id && $b->start_time < $slotEnd && $b->end_time > $slotStart
                ) !== null;

                $cells[] = $isMaint ? 'maintenance' : ($isBusy ? 'occupied' : 'available');
            }
            $occupancy[] = ['name' => $court->name, 'cells' => $cells];
        }
        $occupancyHours = $occHours;

        // ---------------------------------------------------------------
        // Today's Schedule — every confirmed session today, with status
        // ---------------------------------------------------------------
        $todaysSchedule = Booking::with(['user', 'court'])
            ->whereDate('booking_date', $todayStr)
            ->where('status', 'approved')
            ->orderBy('start_time')
            ->get()
            ->map(function ($b) use ($now) {
                $start = Carbon::parse($b->booking_date->toDateString() . ' ' . $b->start_time);
                $end = Carbon::parse($b->booking_date->toDateString() . ' ' . $b->end_time);
                $state = $end->lte($now) ? 'completed' : ($start->lte($now) ? 'ongoing' : 'upcoming');

                return [
                    'name' => $b->user->name ?? 'ไม่ระบุ',
                    'court' => $b->court->name ?? '-',
                    'time' => $start->format('H:i'),
                    'hours' => $this->durationHours($b->start_time, $b->end_time),
                    'state' => $state,
                ];
            });

        // ---------------------------------------------------------------
        // Upcoming Bookings — next sessions, with "in X" countdown
        // ---------------------------------------------------------------
        $upcoming = Booking::with(['user', 'court'])
            ->where('status', 'approved')
            ->where(function ($q) use ($todayStr, $now) {
                $q->where('booking_date', '>', $todayStr)
                    ->orWhere(function ($q2) use ($todayStr, $now) {
                        $q2->whereDate('booking_date', $todayStr)
                            ->where('start_time', '>', $now->format('H:i:s'));
                    });
            })
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get()
            ->map(function ($b) use ($now) {
                $start = Carbon::parse($b->booking_date->toDateString() . ' ' . $b->start_time);

                return [
                    'name' => $b->user->name ?? 'ไม่ระบุ',
                    'court' => $b->court->name ?? '-',
                    'time' => $start->format('H:i'),
                    'in' => $now->diffForHumans($start, ['syntax' => Carbon::DIFF_ABSOLUTE, 'parts' => 2, 'short' => true]),
                ];
            });

        // ---------------------------------------------------------------
        // Top Customers — by hours booked this month
        // ---------------------------------------------------------------
        $topCustomers = $monthApproved->groupBy('user_id')->map(function ($rows) {
            return [
                'hours' => round($rows->sum(fn($b) => $this->durationHours($b->start_time, $b->end_time))),
                'count' => $rows->count(),
                'user_id' => $rows->first()->user_id,
            ];
        })->sortByDesc('hours')->take(5)->values();

        $userNames = User::whereIn('id', $topCustomers->pluck('user_id'))->pluck('name', 'id');
        $topCustomers = $topCustomers->map(function ($c) use ($userNames) {
            $name = $userNames[$c['user_id']] ?? 'ไม่ระบุ';
            $c['name'] = $name;
            $c['initials'] = mb_strtoupper(mb_substr($name, 0, 2));

            return $c;
        });

        // ---------------------------------------------------------------
        // Recent Activities — reconstructed feed (approx, no audit log)
        // ---------------------------------------------------------------
        $recent = collect();
        foreach (Booking::with(['user', 'court'])->latest('created_at')->limit(6)->get() as $b) {
            $recent->push([
                'time' => $b->created_at,
                'type' => 'new',
                'text' => ($b->user->name ?? 'ลูกค้า') . ' สร้างการจองใหม่ ' . ($b->court->name ?? ''),
            ]);
        }
        foreach (Booking::with(['court'])->whereIn('status', ['cancelled', 'rejected'])->latest('updated_at')->limit(4)->get() as $b) {
            $recent->push([
                'time' => $b->updated_at,
                'type' => 'cancel',
                'text' => 'การจอง #' . $b->id . ' ถูกยกเลิก' . ($b->court ? ' (' . $b->court->name . ')' : ''),
            ]);
        }
        foreach (Booking::with(['user'])->where('status', 'approved')->latest('updated_at')->limit(4)->get() as $b) {
            $recent->push([
                'time' => $b->updated_at,
                'type' => 'confirm',
                'text' => 'ยืนยันการจองของ ' . ($b->user->name ?? 'ลูกค้า') . ' แล้ว',
            ]);
        }
        foreach (User::latest()->limit(4)->get() as $u) {
            $recent->push([
                'time' => $u->created_at,
                'type' => 'user',
                'text' => $u->name . ' สมัครสมาชิกใหม่',
            ]);
        }
        $recentActivities = $recent->filter(fn($a) => $a['time'] !== null)
            ->sortByDesc('time')->take(6)
            ->map(fn($a) => [
                'type' => $a['type'],
                'text' => $a['text'],
                'ago' => $a['time']->diffForHumans(),
            ])->values();

        // ---------------------------------------------------------------
        // Membership & Visit stats — this week vs this month (team lead ask)
        // ---------------------------------------------------------------
        $weekStartDt = $now->copy()->startOfWeek();
        $monthStartDt = $now->copy()->startOfMonth();

        $memberStats = [
            'week' => User::where('created_at', '>=', $weekStartDt)->count(),
            'month' => User::where('created_at', '>=', $monthStartDt)->count(),
        ];
        $visitStats = [
            'week' => SiteVisit::where('created_at', '>=', $weekStartDt)->count(),
            'month' => SiteVisit::where('created_at', '>=', $monthStartDt)->count(),
        ];

        // ---------------------------------------------------------------
        // Charts — built with Larapex Charts (data prepared as plain arrays)
        // ---------------------------------------------------------------
        $font = 'Kanit, sans-serif';

        $trendChart = (new LarapexChart)->areaChart()
            ->setFontFamily($font)
            ->setColors(['#f97316'])
            ->setStroke(3, ['#f97316'], 'smooth')
            ->setHeight(240)
            ->setGrid()
            ->setDataset([
                ['name' => 'การจอง', 'data' => array_column($trend, 'count')],
            ])
            ->setXAxis(array_column($trend, 'label'));

        $cancelColorMap = [
            'Customer Cancel' => '#f97316',
            'Maintenance' => '#94a3b8',
        ];
        $cancelChart = (new LarapexChart)->donutChart()
            ->setFontFamily($font)
            ->setColors(array_map(fn($l) => $cancelColorMap[$l] ?? '#94a3b8', array_keys($cancel)))
            ->setHeight(320)
            ->setLabels(array_keys($cancel))
            ->setDataset(array_values($cancel));

        $monthlyChart = (new LarapexChart)->barChart()
            ->setFontFamily($font)
            ->setColors(['#1e293b', '#4a80d7', '#f97316', '#facc15', '#10b981', '#f43f5e', '#f59e0b', '#94a3b8', '#8b5cf6', '#ec4899', '#22d3ee', '#f97316'])
            ->setHeight(280)
            ->setGrid()
            ->setDataset([
                ['name' => 'การจอง', 'data' => array_column($monthly, 'count')],
            ])
            ->setXAxis(array_column($monthly, 'label'));

        $peakChart = (new LarapexChart)->horizontalBarChart()
            ->setFontFamily($font)
            ->setColors(['#f97316'])
            ->setHeight(360)
            ->setGrid()
            ->setDataset([
                ['name' => 'Utilization %', 'data' => array_column($peakHours, 'pct')],
            ])
            ->setXAxis(array_column($peakHours, 'label'));

        // One radial gauge per court (colour by utilization level)
        $courtCharts = [];
        foreach ($courtUtil as $c) {
            $ring = $c['pct'] >= 70 ? '#10b981' : ($c['pct'] >= 40 ? '#f59e0b' : '#f43f5e');
            $courtCharts[] = [
                'name' => $c['name'],
                'hours' => $c['hours'],
                'chart' => (new LarapexChart)->radialChart()
                    ->setFontFamily($font)
                    ->setColors([$ring])
                    ->setHeight(200)
                    ->setLabels([$c['name']])
                    ->addData([$c['pct']]),
            ];
        }

        return view('admin.dashboard', compact(
            'kpis',
            'trend',
            'trendTotal',
            'cancel',
            'cancelTotal',
            'cancelIsMock',
            'monthly',
            'yoy',
            'last12Sum',
            'peakHours',
            'courtUtil',
            'occupancy',
            'occupancyHours',
            'todaysSchedule',
            'upcoming',
            'topCustomers',
            'recentActivities',
            'memberStats',
            'visitStats',
            'trendChart',
            'cancelChart',
            'monthlyChart',
            'peakChart',
            'courtCharts'
        ));
    }

    /** Signed percentage change between current and previous, rounded. */
    private function pctChange($current, $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    public function bookings(Request $request)
    {
        $stats = $this->getCommonStats();

        $status = $request->query('status');
        $court_id = $request->query('court_id');
        $date = $request->query('date', now()->toDateString()); // Default today

        $bookings = Booking::with(['user', 'court'])
            ->whereDate('booking_date', $date)
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($court_id, fn($q) => $q->where('court_id', $court_id))
            ->orderByDesc('start_time')
            ->paginate(20)
            ->withQueryString();

        $range = $request->query('range', 7);
        $startDate = now()->subDays((int) $range);

        $sideStatsQuery = Booking::where('created_at', '>=', $startDate);

        $sideStats = [
            'total' => (clone $sideStatsQuery)->count(),
            'pending' => (clone $sideStatsQuery)->where('status', 'pending')->count(),
            'approved' => (clone $sideStatsQuery)->where('status', 'approved')->count(),
            'cancelled' => (clone $sideStatsQuery)->whereIn('status', ['rejected', 'cancelled'])->count(),
        ];

        $courts = Court::all();

        return view('admin.bookings', compact('stats', 'bookings', 'status', 'date', 'court_id', 'sideStats', 'courts', 'range'));
    }
}
