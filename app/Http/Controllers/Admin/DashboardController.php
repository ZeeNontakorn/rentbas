<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClosure;
use App\Models\SiteVisit;
use App\Models\User;
use ArielMejiaDev\LarapexCharts\LarapexChart;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    /** Operating hours used across all utilization/occupancy calculations. */
    private const OPEN_HOUR = 8;   // 08:00
    private const CLOSE_HOUR = 22; // 22:00

    /** Statuses that count as a "real" booking sitting on the calendar. */
    private const COUNTABLE = ['approved', 'pending'];

    /** How many rows the Recent Activities / Top Customers feeds keep. */
    private const FEED_LIMIT = 30;

    private function getCommonStats()
    {
        $today = now()->toDateString();

        return [
            'today_total' => Booking::whereDate('booking_date', $today)->count(),
            'today_pending' => Booking::whereDate('booking_date', '>=', $today)->where('status', 'pending')->count(),
            'today_approved' => Booking::where('status', 'approved')->count(),
            'today_cancelled' => Booking::whereDate('booking_date', $today)->whereIn('status', ['cancelled'])->count(),
            'today_rejected' => Booking::where('status', 'rejected')->count(),
        ];
    }

    /** Duration of a booking in hours from its HH:MM:SS time strings. */
    private function durationHours(string $start, string $end): float
    {
        return max((strtotime($end) - strtotime($start)) / 3600, 0);
    }

    /**
     * Resolve the "view_date" query param into a safe Y-m-d string.
     * Falls back to today on missing/invalid/future input.
     */
    private function resolveViewDate(Request $request, string $todayStr): string
    {
        $requested = $request->query('view_date');

        if (! $requested) {
            return $todayStr;
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $requested)->startOfDay();
        } catch (\Exception $e) {
            return $todayStr;
        }

        // Don't allow picking a date in the future.
        if ($parsed->toDateString() > $todayStr) {
            return $todayStr;
        }

        return $parsed->toDateString();
    }

    /**
     * Distinct years that actually appear in bookings.booking_date, newest
     * first, always including the current year so it's selectable even
     * before any bookings exist for it.
     *
     * booking_date is stored as a 'Y-m-d ...' style string (e.g.
     * "2026-01-01"), so the year is pulled with LEFT(booking_date, 4)
     * rather than MySQL's YEAR(), which can return unexpected results if
     * the column isn't a strict DATE/DATETIME type.
     */
    private function availableBookingYears(int $currentYear): array
    {
        $years = Booking::selectRaw('DISTINCT LEFT(booking_date, 4) as yr')
            ->whereNotNull('booking_date')
            ->pluck('yr')
            ->map(fn ($y) => (int) $y)
            ->all();

        $years[] = $currentYear;

        return collect($years)->unique()->sortDesc()->values()->all();
    }

    /**
     * Resolve the "view_year" query param into a safe int year — must be
     * one of the years that actually exist in booking_date (or current year).
     */
    private function resolveViewYear(Request $request, int $currentYear, array $allowedYears): int
    {
        $requested = $request->query('view_year');

        if (! $requested || ! ctype_digit((string) $requested)) {
            return $currentYear;
        }

        $year = (int) $requested;

        return in_array($year, $allowedYears, true) ? $year : $currentYear;
    }

    /**
     * Court x hour-slot grid for a given date: 'available' | 'occupied' | 'maintenance'.
     */
    private function buildOccupancy(string $date, Collection $courts): array
    {
        $approved = Booking::whereDate('booking_date', $date)
            ->where('status', 'approved')
            ->get(['start_time', 'end_time', 'court_id']);
        $closures = CourtClosure::whereDate('date', $date)
            ->get(['court_id', 'start_time', 'end_time']);

        $occHours = range(self::OPEN_HOUR, self::CLOSE_HOUR - 1);
        $occupancy = [];
        foreach ($courts as $court) {
            $cells = [];
            $fullyClosed = $court->court_status === 'closed';
            foreach ($occHours as $h) {
                $slotStart = sprintf('%02d:00:00', $h);
                $slotEnd = sprintf('%02d:00:00', $h + 1);

                $isMaint = $fullyClosed || $closures->first(
                    fn($c) => $c->court_id === $court->id && $c->start_time < $slotEnd && $c->end_time > $slotStart
                ) !== null;
                $isBusy = $approved->first(
                    fn($b) => $b->court_id === $court->id && $b->start_time < $slotEnd && $b->end_time > $slotStart
                ) !== null;

                $cells[] = $isMaint ? 'maintenance' : ($isBusy ? 'occupied' : 'available');
            }
            $occupancy[] = ['name' => $court->name, 'cells' => $cells];
        }

        return $occupancy;
    }

    /** Top customers by hours booked, from an already-loaded set of approved bookings. */
    private function buildTopCustomers(Collection $monthApproved): Collection
    {
        $top = $monthApproved->groupBy('user_id')->map(function ($rows) {
            return [
                'hours' => round($rows->sum(fn($b) => $this->durationHours($b->start_time, $b->end_time))),
                'count' => $rows->count(),
                'user_id' => $rows->first()->user_id,
            ];
        })->sortByDesc('hours')->take(self::FEED_LIMIT)->values();

        $userNames = User::whereIn('id', $top->pluck('user_id'))->pluck('us_name', 'id');

        return $top->map(function ($c) use ($userNames) {
            $name = $userNames[$c['user_id']] ?? 'ไม่ระบุ';
            $c['name'] = $name;
            $c['initials'] = mb_strtoupper(mb_substr($name, 0, 2));

            return $c;
        })->values();
    }

    /** Recent Activities — reconstructed feed (approx, no audit log). */
    private function buildRecentActivities(): Collection
    {
        $recent = collect();
        foreach (Booking::with(['user', 'court'])->latest('created_at')->limit(15)->get() as $b) {
            $recent->push([
                'time' => $b->created_at,
                'type' => 'new',
                'text' => ($b->user->us_name ?? 'ลูกค้า') . ' สร้างการจองใหม่ ' . ($b->court->name ?? ''),
            ]);
        }
        foreach (Booking::with(['court'])->whereIn('status', ['cancelled', 'rejected'])->latest('updated_at')->limit(10)->get() as $b) {
            $recent->push([
                'time' => $b->updated_at,
                'type' => 'cancel',
                'text' => 'การจอง #' . $b->id . ' ถูกยกเลิก' . ($b->court ? ' (' . $b->court->name . ')' : ''),
            ]);
        }
        foreach (Booking::with(['user'])->where('status', 'approved')->latest('updated_at')->limit(10)->get() as $b) {
            $recent->push([
                'time' => $b->updated_at,
                'type' => 'confirm',
                'text' => 'ยืนยันการจองของ ' . ($b->user->us_name ?? 'ลูกค้า') . ' แล้ว',
            ]);
        }
        foreach (User::latest()->limit(10)->get() as $u) {
            $recent->push([
                'time' => $u->created_at,
                'type' => 'user',
                'text' => $u->us_name . ' สมัครสมาชิกใหม่',
            ]);
        }

        return $recent->filter(fn($a) => $a['time'] !== null)
            ->sortByDesc('time')->take(self::FEED_LIMIT)
            ->map(fn($a) => [
                'type' => $a['type'],
                'text' => $a['text'],
                'ago' => $a['time']->diffForHumans(),
            ])->values();
    }

    public function index(Request $request)
    {
        $now = now();
        $todayStr = $now->toDateString();
        $hoursPerDay = self::CLOSE_HOUR - self::OPEN_HOUR; // 14

        // The day that "Peak Booking Hours", "Cancellation Analysis" and
        // "Occupancy Timeline" all reflect. Defaults to today; can be changed
        // by clicking a point on the Booking Trend chart or a "← Today"
        // button, which re-fetch via ?view_date=Y-m-d without a page reload.
        $viewDate = $this->resolveViewDate($request, $todayStr);
        $viewDateCarbon = Carbon::parse($viewDate);
        $isToday = $viewDate === $todayStr;
        $viewDateLabel = $isToday
            ? 'วันนี้ (' . $viewDateCarbon->translatedFormat('d M') . ')'
            : $viewDateCarbon->translatedFormat('d F Y');

        // The calendar year that "Monthly Booking Volume" reflects. The
        // dropdown only lists years that actually appear in booking_date
        // (plus the current year). Defaults to the current year.
        $availableYears = $this->availableBookingYears($now->year);
        $viewYear = $this->resolveViewYear($request, $now->year, $availableYears);

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
        // This KPI always reflects *today*, regardless of $viewDate.
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

        $kpis = [
            'today_bookings' => ['value' => $todayBookings, 'trend' => $bookingsTrend, 'spark' => $spark],
            'utilization' => ['value' => $utilizationRate, 'trend' => $utilizationTrend],
            'booked_hours' => ['value' => $bookedHours, 'trend' => $bookedHoursTrend],
            'active_customers' => ['value' => $activeCustomers, 'trend' => $activeTrend],
            'pending' => ['value' => $pendingBookings],
        ];

        // ---------------------------------------------------------------
        // Booking Trend — daily bookings + cancellations, last 30 days
        // (area chart). Each point carries its ISO date so the chart can
        // be clicked to change $viewDate (see the manual chart script in
        // the view).
        // ---------------------------------------------------------------
        $last30 = Booking::where('booking_date', '>=', $now->copy()->subDays(29)->toDateString())
            ->whereIn('status', self::COUNTABLE)
            ->get(['booking_date']);
        $trendByDate = $last30->groupBy(fn($b) => $b->booking_date->toDateString())->map->count();

        $last30Cancelled = Booking::where('booking_date', '>=', $now->copy()->subDays(29)->toDateString())
            ->whereIn('status', ['cancelled', 'rejected'])
            ->get(['booking_date']);
        $trendCancelledByDate = $last30Cancelled->groupBy(fn($b) => $b->booking_date->toDateString())->map->count();

        $trend = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i);
            $trend[] = [
                'label' => $d->format('M j'),
                'date' => $d->toDateString(),
                'count' => (int) ($trendByDate[$d->toDateString()] ?? 0),
                'cancelled' => (int) ($trendCancelledByDate[$d->toDateString()] ?? 0),
            ];
        }
        $trendTotal = array_sum(array_column($trend, 'count'));
        $trendCancelledTotal = array_sum(array_column($trend, 'cancelled'));

        // ---------------------------------------------------------------
        // Cancellation Analysis — booked vs. cancelled, for $viewDate
        // ---------------------------------------------------------------
        $viewDateBookedCount = Booking::whereDate('booking_date', $viewDate)
            ->whereIn('status', self::COUNTABLE)->count();
        $viewDateCancelledCount = Booking::whereDate('booking_date', $viewDate)
            ->whereIn('status', ['cancelled', 'rejected'])->count();

        $cancel = ['Booked' => $viewDateBookedCount, 'Cancelled' => $viewDateCancelledCount];
        $cancelTotal = array_sum($cancel);

        // ---------------------------------------------------------------
        // Monthly Booking Volume — calendar year (Jan–Dec) + YoY
        // Reflects $viewYear, selectable via the dropdown under the YoY chip.
        // ---------------------------------------------------------------
        $yearStart = Carbon::create($viewYear, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($viewYear, 12, 31)->endOfDay();
        $yearRows = Booking::whereBetween('booking_date', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->whereIn('status', self::COUNTABLE)
            ->get(['booking_date']);
        $byMonth = $yearRows->groupBy(fn($b) => (int) $b->booking_date->format('n'))->map->count();

        $monthly = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthly[] = [
                'label' => Carbon::create($viewYear, $m, 1)->format('M'),
                'count' => (int) ($byMonth[$m] ?? 0),
            ];
        }
        $last12Sum = array_sum(array_column($monthly, 'count'));

        $prevYearStart = Carbon::create($viewYear - 1, 1, 1)->startOfDay();
        $prevYearEnd = Carbon::create($viewYear - 1, 12, 31)->endOfDay();
        $prev12Sum = Booking::whereBetween('booking_date', [$prevYearStart->toDateString(), $prevYearEnd->toDateString()])
            ->whereIn('status', self::COUNTABLE)->count();
        $yoy = $this->pctChange($last12Sum, $prev12Sum);

        // ---------------------------------------------------------------
        // Peak Booking Hours — total bookings per hour slot, for $viewDate
        // Shows raw booking counts (not utilization %) as a line chart.
        // ---------------------------------------------------------------
        $viewDateApproved = Booking::whereDate('booking_date', $viewDate)
            ->where('status', 'approved')
            ->get(['start_time', 'end_time', 'court_id']);

        $peakHours = [];
        for ($h = self::OPEN_HOUR; $h < self::CLOSE_HOUR; $h++) {
            $slotStart = sprintf('%02d:00:00', $h);
            $slotEnd = sprintf('%02d:00:00', $h + 1);
            $busy = $viewDateApproved->filter(
                fn($b) => $b->start_time < $slotEnd && $b->end_time > $slotStart
            )->count();
            $peakHours[] = [
                'label' => sprintf('%02d:00', $h),
                'count' => $busy,
            ];
        }

        // ---------------------------------------------------------------
        // Court Utilization — per court, this week: grouped bar comparing
        // Approved / Pending / Cancelled(+Rejected) booking counts.
        // ---------------------------------------------------------------
        $weekStart = $now->copy()->startOfWeek()->toDateString();
        $weekEnd = $now->copy()->endOfWeek()->toDateString();
        $weekBookings = Booking::whereBetween('booking_date', [$weekStart, $weekEnd])
            ->whereIn('status', ['approved', 'pending', 'cancelled', 'rejected'])
            ->get(['court_id', 'status']);

        $courtStatusStats = [];
        foreach ($courts as $court) {
            $rows = $weekBookings->where('court_id', $court->id);
            $courtStatusStats[] = [
                'name' => $court->name,
                'approved' => $rows->where('status', 'approved')->count(),
                'pending' => $rows->where('status', 'pending')->count(),
                'cancelled' => $rows->whereIn('status', ['cancelled', 'rejected'])->count(),
            ];
        }

        // ---------------------------------------------------------------
        // Occupancy Timeline — court x hour grid, for $viewDate
        // ---------------------------------------------------------------
        $occupancy = $this->buildOccupancy($viewDate, $courts);
        $occupancyHours = range(self::OPEN_HOUR, self::CLOSE_HOUR - 1);

        // ---------------------------------------------------------------
        // Top Customers — by hours booked this month
        // ---------------------------------------------------------------
        $topCustomers = $this->buildTopCustomers($monthApproved);

        // ---------------------------------------------------------------
        // Recent Activities — reconstructed feed (approx, no audit log)
        // ---------------------------------------------------------------
        $recentActivities = $this->buildRecentActivities();

        // ---------------------------------------------------------------
        // Membership & Visit stats — daily trend, last 30 days
        // (was a week-vs-month bar comparison; now a line trend to match
        // the Booking Trend chart, per team lead request)
        // ---------------------------------------------------------------
        $memberRows = User::where('created_at', '>=', $now->copy()->subDays(29)->startOfDay())
            ->get(['created_at']);
        $memberByDate = $memberRows->groupBy(fn($u) => $u->created_at->toDateString())->map->count();

        $visitRows = SiteVisit::where('created_at', '>=', $now->copy()->subDays(29)->startOfDay())
            ->get(['created_at']);
        $visitByDate = $visitRows->groupBy(fn($v) => $v->created_at->toDateString())->map->count();

        $memberTrend = [];
        $visitTrend = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i);
            $label = $d->format('M j');
            $memberTrend[] = ['label' => $label, 'count' => (int) ($memberByDate[$d->toDateString()] ?? 0)];
            $visitTrend[] = ['label' => $label, 'count' => (int) ($visitByDate[$d->toDateString()] ?? 0)];
        }

        // ---------------------------------------------------------------
        // Charts — built with Larapex Charts (data prepared as plain arrays)
        // ---------------------------------------------------------------
        $font = 'Kanit, sans-serif';

        // NOTE: Booking Trend, Cancellation Analysis (donut), Peak Booking
        // Hours (line), and Occupancy Timeline (heatmap) are all hand-rolled
        // directly in the Blade view with raw ApexCharts, instead of via
        // Larapex's ->script(), because that helper can't expose JS click
        // events or be updated in place with updateSeries()/updateOptions()
        // — which all four need for the AJAX view-date switching and,
        // for Occupancy, JSON-encoded court/cell data is passed straight
        // to the view ($occupancy / $occupancyHours) instead of being
        // wrapped in a LarapexChart object.

        $monthlyChart = (new LarapexChart)->barChart()
            ->setFontFamily($font)
            ->setColors(['#1e293b', '#4a80d7', '#f97316', '#facc15', '#10b981', '#f43f5e', '#f59e0b', '#94a3b8', '#8b5cf6', '#ec4899', '#22d3ee', '#f97316'])
            ->setHeight(280)
            ->setGrid()
            ->setDataset([
                ['name' => 'การจอง', 'data' => array_column($monthly, 'count')],
            ])
            ->setXAxis(array_column($monthly, 'label'));

        // Court Utilization — Approved (green) / Pending (grey) / Cancelled
        // (red) booking counts per court, this week. Rendered as a
        // horizontal bar chart directly in the Blade view with raw
        // ApexCharts (like Peak Hours / Cancellation / Occupancy above) —
        // Larapex's ->horizontal() isn't chainable in this package version
        // (returns a string instead of $this), so it can't be used here.
        // $courtStatusStats is passed straight to the view as JSON.

        // Members & Visits — line charts, daily trend over the last 30 days
        // (same shape as the Booking Trend chart above).
        $memberChart = (new LarapexChart)->lineChart()
            ->setFontFamily($font)
            ->setColors(['#60a5fa'])
            ->setStroke(3, ['#60a5fa'], 'smooth')
            ->setHeight(240)
            ->setGrid()
            ->setDataset([
                ['name' => 'ผู้สมัครสมาชิก', 'data' => array_column($memberTrend, 'count')],
            ])
            ->setXAxis(array_column($memberTrend, 'label'));

        $visitChart = (new LarapexChart)->lineChart()
            ->setFontFamily($font)
            ->setColors(['#4ade80'])
            ->setStroke(3, ['#4ade80'], 'smooth')
            ->setHeight(240)
            ->setGrid()
            ->setDataset([
                ['name' => 'ผู้เข้าชม', 'data' => array_column($visitTrend, 'count')],
            ])
            ->setXAxis(array_column($visitTrend, 'label'));

        return view('admin.dashboard', compact(
            'kpis',
            'trend',
            'trendTotal',
            'trendCancelledTotal',
            'cancel',
            'cancelTotal',
            'monthly',
            'yoy',
            'last12Sum',
            'peakHours',
            'occupancy',
            'occupancyHours',
            'topCustomers',
            'recentActivities',
            'monthlyChart',
            'courtStatusStats',
            'memberChart',
            'visitChart',
            'viewDate',
            'viewDateLabel',
            'isToday',
            'todayStr',
            'viewYear',
            'availableYears'
        ));
    }

    /**
     * AJAX endpoint: returns Peak Booking Hours, Cancellation Analysis, and
     * Occupancy Timeline data for a given ?view_date=Y-m-d, without
     * re-rendering the whole page. Used by the Booking Trend chart's click
     * handler and the "← Today" buttons.
     */
    public function viewDateData(Request $request)
    {
        $todayStr = now()->toDateString();
        $viewDate = $this->resolveViewDate($request, $todayStr);
        $viewDateCarbon = Carbon::parse($viewDate);
        $isToday = $viewDate === $todayStr;
        $viewDateLabel = $isToday
            ? 'วันนี้ (' . $viewDateCarbon->translatedFormat('d M') . ')'
            : $viewDateCarbon->translatedFormat('d F Y');

        $courts = Court::orderBy('id')->get();

        $viewDateApproved = Booking::whereDate('booking_date', $viewDate)
            ->where('status', 'approved')
            ->get(['start_time', 'end_time']);

        $peakHours = [];
        for ($h = self::OPEN_HOUR; $h < self::CLOSE_HOUR; $h++) {
            $slotStart = sprintf('%02d:00:00', $h);
            $slotEnd = sprintf('%02d:00:00', $h + 1);
            $busy = $viewDateApproved->filter(
                fn($b) => $b->start_time < $slotEnd && $b->end_time > $slotStart
            )->count();
            $peakHours[] = [
                'label' => sprintf('%02d:00', $h),
                'count' => $busy,
            ];
        }

        $viewDateBookedCount = Booking::whereDate('booking_date', $viewDate)
            ->whereIn('status', self::COUNTABLE)->count();
        $viewDateCancelledCount = Booking::whereDate('booking_date', $viewDate)
            ->whereIn('status', ['cancelled', 'rejected'])->count();

        $occupancy = $this->buildOccupancy($viewDate, $courts);

        return response()->json([
            'viewDate' => $viewDate,
            'viewDateLabel' => $viewDateLabel,
            'isToday' => $isToday,
            'peakHours' => $peakHours,
            'cancel' => [
                'booked' => $viewDateBookedCount,
                'cancelled' => $viewDateCancelledCount,
                'total' => $viewDateBookedCount + $viewDateCancelledCount,
            ],
            'occupancy' => $occupancy,
        ]);
    }

    /**
     * AJAX endpoint: returns fresh Top Customers + Recent Activities data.
     * Polled every 10s from the dashboard to keep both feeds live without a
     * page reload.
     */
    public function liveData(Request $request)
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd = $now->copy()->endOfMonth()->toDateString();

        $monthApproved = Booking::whereBetween('booking_date', [$monthStart, $monthEnd])
            ->where('status', 'approved')
            ->get(['start_time', 'end_time', 'court_id', 'user_id']);

        return response()->json([
            'updatedAt' => $now->format('H:i:s'),
            'topCustomers' => $this->buildTopCustomers($monthApproved),
            'recentActivities' => $this->buildRecentActivities(),
        ]);
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

        $status = $request->query('status', 'pending');
        $court_id = $request->query('court_id');
        $date = $request->query('date');

        $bookings = Booking::with(['user', 'court'])
            //วันที่
            ->when($date, fn($q) => $q->whereDate('booking_date', $date))

            //สถานะ
            ->when($status === 'pending', fn($q) => $q->whereDate('booking_date', '>=', now()->toDateString()))
            ->where('status', $status ? $status : 'pending')

            //สนาม
            ->when($court_id, fn($q) => $q->where('court_id', $court_id))

            //เรียงจากคนที่ส่งมาก่อน
            ->orderBy('created_at', 'asc')

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
