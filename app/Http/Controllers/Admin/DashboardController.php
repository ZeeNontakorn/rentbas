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
    private const COUNTABLE = ['approved', 'pending_payment'];

    /** How many rows the Recent Activities / Top Customers feeds keep. */
    private const FEED_LIMIT = 30;

    /** Valid values for the global Day / Month / Year chart selector. */
    private const VIEW_TYPES = ['day', 'month', 'year'];

    private function getCommonStats()
    {
        $today = now()->toDateString();

        return [
            'today_total' => Booking::whereDate('booking_date', $today)->count(),
            'today_pending' => Booking::whereDate('booking_date', '>=', $today)->where('status', 'pending_payment')->count(),
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
     * Resolve the "view_type" query param (day/month/year) that drives
     * Booking Trend, Cancellation Analysis, Peak Booking Hours, Court
     * Utilization, and Occupancy Timeline. Defaults to "day".
     */
    private function resolveViewType(Request $request): string
    {
        $type = $request->query('view_type', 'day');

        return in_array($type, self::VIEW_TYPES, true) ? $type : 'day';
    }

    /**
     * Resolve the "chart_month" query param (Y-m) used when view_type is
     * "month". Falls back to the current month on missing/invalid/future
     * input. Kept independent from "view_month" (Members/Visits picker).
     */
    private function resolveChartMonth(Request $request, string $currentMonthStr): string
    {
        $requested = $request->query('chart_month');

        if (! $requested || ! preg_match('/^\d{4}-\d{2}$/', $requested)) {
            return $currentMonthStr;
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $requested . '-01')->startOfMonth();
        } catch (\Exception $e) {
            return $currentMonthStr;
        }

        if ($parsed->format('Y-m') > $currentMonthStr) {
            return $currentMonthStr;
        }

        return $parsed->format('Y-m');
    }

    /**
     * Resolve the "chart_year" query param used when view_type is "year".
     * Must be one of the years that actually exist in booking_date (or the
     * current year). Kept independent from any other year param.
     */
    private function resolveChartYear(Request $request, int $currentYear, array $allowedYears): int
    {
        $requested = $request->query('chart_year');

        if (! $requested || ! ctype_digit((string) $requested)) {
            return $currentYear;
        }

        $year = (int) $requested;

        return in_array($year, $allowedYears, true) ? $year : $currentYear;
    }

    /**
     * Resolve the "view_month" query param (Y-m) into a safe string.
     * Falls back to the current month on missing/invalid/future input.
     * This is the independent Members/Visits month picker.
     */
    private function resolveViewMonth(Request $request, string $currentMonth): string
    {
        $requested = $request->query('view_month');

        if (! $requested || ! preg_match('/^\d{4}-\d{2}$/', $requested)) {
            return $currentMonth;
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $requested . '-01')->startOfMonth();
        } catch (\Exception $e) {
            return $currentMonth;
        }

        if ($parsed->format('Y-m') > $currentMonth) {
            return $currentMonth;
        }

        return $parsed->format('Y-m');
    }

    /**
     * Distinct years that actually appear in bookings.booking_date, newest
     * first, always including the current year so it's selectable even
     * before any bookings exist for it. Used by the Year mode of the chart
     * selector.
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
     * Last 12 calendar months (including the current one), newest first.
     * Shared by the Members/Visits month picker and the chart selector's
     * Month mode dropdown.
     */
    private function availableMonths(Carbon $now): array
    {
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $m = $now->copy()->subMonthsNoOverflow($i);
            $months[] = [
                'value' => $m->format('Y-m'),
                'label' => $m->translatedFormat('F Y'),
            ];
        }

        return $months;
    }

    /**
     * Resolve the [start, end] Y-m-d date range that Cancellation Analysis,
     * Peak Booking Hours, Occupancy Timeline, and Court Utilization should
     * aggregate over, based on the current view_type.
     */
    private function periodRange(string $viewType, string $viewDate, string $chartMonth, int $chartYear, Carbon $now): array
    {
        if ($viewType === 'month') {
            $start = Carbon::createFromFormat('Y-m-d', $chartMonth . '-01')->startOfMonth();
            $end = $chartMonth === $now->format('Y-m') ? $now->copy() : $start->copy()->endOfMonth();

            return [$start->toDateString(), $end->toDateString()];
        }

        if ($viewType === 'year') {
            $start = Carbon::create($chartYear, 1, 1)->startOfDay();
            $end = $chartYear === $now->year ? $now->copy() : Carbon::create($chartYear, 12, 31)->endOfDay();

            return [$start->toDateString(), $end->toDateString()];
        }

        // day
        return [$viewDate, $viewDate];
    }

    /** Human label shown under each period-driven chart's heading. */
    private function resolvePeriodLabel(string $viewType, string $viewDate, string $chartMonth, int $chartYear, Carbon $now): string
    {
        if ($viewType === 'year') {
            return (string) $chartYear;
        }

        if ($viewType === 'month') {
            return Carbon::createFromFormat('Y-m-d', $chartMonth . '-01')->translatedFormat('F Y');
        }

        $d = Carbon::parse($viewDate);

        return $viewDate === $now->toDateString()
            ? 'วันนี้ (' . $d->translatedFormat('d M') . ')'
            : $d->translatedFormat('d F Y');
    }

    /** Whether the selected period is the "current" one (drives the reset button). */
    private function isCurrentPeriod(string $viewType, string $viewDate, string $chartMonth, int $chartYear, Carbon $now, string $todayStr, string $currentMonthStr): bool
    {
        return match ($viewType) {
            'year' => $chartYear === $now->year,
            'month' => $chartMonth === $currentMonthStr,
            default => $viewDate === $todayStr,
        };
    }

    /**
     * Court x hour-slot grid for a single date: 'available' | 'occupied' | 'maintenance'.
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

    /**
     * Court x hour-slot grid averaged over a multi-day period: each cell is
     * the % of days in the range that hour was occupied for that court
     * (maintenance-closed days are excluded from the denominator).
     */
    private function buildPeriodOccupancy(string $start, string $end, Collection $courts): array
    {
        $approved = Booking::whereBetween('booking_date', [$start, $end])
            ->where('status', 'approved')
            ->get(['booking_date', 'start_time', 'end_time', 'court_id']);
        $closures = CourtClosure::whereBetween('date', [$start, $end])
            ->get(['date', 'court_id', 'start_time', 'end_time']);

        $days = Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1;
        $occHours = range(self::OPEN_HOUR, self::CLOSE_HOUR - 1);

        $occupancy = [];
        foreach ($courts as $court) {
            $fullyClosed = $court->court_status === 'closed';
            $cells = [];

            foreach ($occHours as $h) {
                if ($fullyClosed) {
                    $cells[] = 0;
                    continue;
                }

                $slotStart = sprintf('%02d:00:00', $h);
                $slotEnd = sprintf('%02d:00:00', $h + 1);

                $closedDays = $closures->filter(
                    fn($c) => $c->court_id === $court->id && $c->start_time < $slotEnd && $c->end_time > $slotStart
                )->pluck('date')->map(fn($d) => (string) $d)->unique()->count();

                $occupiedDays = $approved->filter(
                    fn($b) => $b->court_id === $court->id && $b->start_time < $slotEnd && $b->end_time > $slotStart
                )->pluck('booking_date')->map(fn($d) => (string) $d)->unique()->count();

                $bookableDays = max($days - $closedDays, 1);
                $cells[] = (int) round(min($occupiedDays / $bookableDays * 100, 100));
            }

            $occupancy[] = ['name' => $court->name, 'cells' => $cells, 'closed' => $fullyClosed];
        }

        return $occupancy;
    }

    /**
     * Wraps buildOccupancy()/buildPeriodOccupancy(): returns a single-day
     * discrete grid when start == end, otherwise a multi-day % grid. The
     * 'mode' key tells the front-end which heatmap rendering to use.
     */
    private function buildOccupancyPayload(string $start, string $end, Collection $courts): array
    {
        if ($start === $end) {
            return ['mode' => 'day', 'rows' => $this->buildOccupancy($start, $courts)];
        }

        return ['mode' => 'period', 'rows' => $this->buildPeriodOccupancy($start, $end, $courts)];
    }

    /**
     * Court Utilization — booked (approved) hours ÷ total operating-hours
     * capacity for the period, per court, sorted highest first. Used for
     * every view_type (day/month/year) so the card always reflects the
     * exact same period as the other charts.
     */
    private function buildCourtUtilization(string $start, string $end, Collection $courts): array
    {
        $hoursPerDay = self::CLOSE_HOUR - self::OPEN_HOUR;
        $days = Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1;
        $capacity = $hoursPerDay * $days;

        $approved = Booking::whereBetween('booking_date', [$start, $end])
            ->where('status', 'approved')
            ->get(['start_time', 'end_time', 'court_id']);

        $rows = [];
        foreach ($courts as $court) {
            $hours = $approved->where('court_id', $court->id)
                ->sum(fn($b) => $this->durationHours($b->start_time, $b->end_time));
            $pct = $capacity > 0 ? (int) round(min($hours / $capacity * 100, 100)) : 0;
            $rows[] = ['name' => $court->name, 'pct' => $pct];
        }

        usort($rows, fn($a, $b) => $b['pct'] <=> $a['pct']);

        return $rows;
    }

    /**
     * Peak Booking Hours data for a date range — pending (pending_payment
     * only), cancelled (cancelled+rejected), approved, and rejected
     * booking counts bucketed by hour-of-day (bookings from every day in
     * the range that touch that hour slot are summed together). Shared by
     * index() and viewDateData() so both stay in sync.
     */
    private function buildPeakHours(string $start, string $end): array
    {
        $bookings = Booking::whereBetween('booking_date', [$start, $end])
            ->whereIn('status', ['approved', 'pending_payment', 'cancelled', 'rejected'])
            ->get(['start_time', 'end_time', 'status']);

        $labels = [];
        $total = [];
        $cancelled = [];
        $approved = [];
        $rejected = [];

        for ($h = self::OPEN_HOUR; $h < self::CLOSE_HOUR; $h++) {
            $slotStart = sprintf('%02d:00:00', $h);
            $slotEnd = sprintf('%02d:00:00', $h + 1);

            $inSlot = $bookings->filter(
                fn($b) => $b->start_time < $slotEnd && $b->end_time > $slotStart
            );

            $labels[] = sprintf('%02d:00', $h);
            $total[] = $inSlot->where('status', 'pending_payment')->count();
            $cancelled[] = $inSlot->whereIn('status', ['cancelled', 'rejected'])->count();
            $approved[] = $inSlot->where('status', 'approved')->count();
            $rejected[] = $inSlot->where('status', 'rejected')->count();
        }

        return compact('labels', 'total', 'cancelled', 'approved', 'rejected');
    }

    /**
     * Cancellation Analysis data for a date range — pending (pending_payment
     * only), cancelled (cancelled+rejected), approved, and rejected counts.
     * Shared by index() and viewDateData().
     */
    private function buildCancelData(string $start, string $end): array
    {
        $booked = Booking::whereBetween('booking_date', [$start, $end])
            ->where('status', 'pending_payment')->count();
        $cancelled = Booking::whereBetween('booking_date', [$start, $end])
            ->whereIn('status', ['cancelled', 'rejected'])->count();
        $approved = Booking::whereBetween('booking_date', [$start, $end])
            ->where('status', 'approved')->count();
        $rejected = Booking::whereBetween('booking_date', [$start, $end])
            ->where('status', 'rejected')->count();

        return compact('booked', 'cancelled', 'approved', 'rejected');
    }

    /**
     * Booking Trend data, shaped according to view_type:
     *  - day:   last 30 days, one point per day
     *  - month: Jan-Dec of $chartYear, one point per month (replaces the
     *           old standalone "Monthly Booking Volume" chart)
     *  - year:  last 6 calendar years, one point per year
     * Every mode returns the same 4 series (pending/cancelled/approved/rejected)
     * so the chart shape never changes, only its granularity. "Pending"
     * (the 'total' key) reflects status = pending_payment only.
     */
    private function buildTrend(string $viewType, string $viewDate, string $chartMonth, int $chartYear, Carbon $now): array
    {
        if ($viewType === 'year') {
            $years = range($now->year - 5, $now->year);
            $labels = $keys = $total = $cancelled = $approved = $rejected = [];

            foreach ($years as $y) {
                $yStart = Carbon::create($y, 1, 1)->startOfDay()->toDateString();
                $yEnd = $y === $now->year ? $now->toDateString() : Carbon::create($y, 12, 31)->toDateString();
                $rows = Booking::whereBetween('booking_date', [$yStart, $yEnd])
                    ->whereIn('status', ['approved', 'pending_payment', 'cancelled', 'rejected'])
                    ->get(['status']);

                $labels[] = (string) $y;
                $keys[] = (string) $y;
                $total[] = $rows->where('status', 'pending_payment')->count();
                $cancelled[] = $rows->whereIn('status', ['cancelled', 'rejected'])->count();
                $approved[] = $rows->where('status', 'approved')->count();
                $rejected[] = $rows->where('status', 'rejected')->count();
            }

            return [
                'labels' => $labels, 'keys' => $keys, 'currentKey' => (string) $now->year,
                'total' => $total, 'cancelled' => $cancelled, 'approved' => $approved, 'rejected' => $rejected,
            ];
        }

        if ($viewType === 'month') {
            $labels = $keys = $total = $cancelled = $approved = $rejected = [];

            for ($m = 1; $m <= 12; $m++) {
                $mStart = Carbon::create($chartYear, $m, 1)->startOfMonth();
                $labels[] = $mStart->format('M');
                $keys[] = $mStart->format('Y-m');

                if ($mStart->gt($now)) {
                    $total[] = 0;
                    $cancelled[] = 0;
                    $approved[] = 0;
                    $rejected[] = 0;
                    continue;
                }

                $mEnd = ($chartYear === $now->year && $m === $now->month) ? $now->copy() : $mStart->copy()->endOfMonth();
                $rows = Booking::whereBetween('booking_date', [$mStart->toDateString(), $mEnd->toDateString()])
                    ->whereIn('status', ['approved', 'pending_payment', 'cancelled', 'rejected'])
                    ->get(['status']);

                $total[] = $rows->where('status', 'pending_payment')->count();
                $cancelled[] = $rows->whereIn('status', ['cancelled', 'rejected'])->count();
                $approved[] = $rows->where('status', 'approved')->count();
                $rejected[] = $rows->where('status', 'rejected')->count();
            }

            return [
                'labels' => $labels, 'keys' => $keys, 'currentKey' => $now->format('Y-m'),
                'total' => $total, 'cancelled' => $cancelled, 'approved' => $approved, 'rejected' => $rejected,
            ];
        }

        // day: last 30 days
        $since = $now->copy()->subDays(29)->toDateString();

        $byDate = Booking::where('booking_date', '>=', $since)->where('status', 'pending_payment')
            ->get(['booking_date'])->groupBy(fn($b) => $b->booking_date->toDateString())->map->count();
        $cancelledByDate = Booking::where('booking_date', '>=', $since)->whereIn('status', ['cancelled', 'rejected'])
            ->get(['booking_date'])->groupBy(fn($b) => $b->booking_date->toDateString())->map->count();
        $approvedByDate = Booking::where('booking_date', '>=', $since)->where('status', 'approved')
            ->get(['booking_date'])->groupBy(fn($b) => $b->booking_date->toDateString())->map->count();
        $rejectedByDate = Booking::where('booking_date', '>=', $since)->where('status', 'rejected')
            ->get(['booking_date'])->groupBy(fn($b) => $b->booking_date->toDateString())->map->count();

        $labels = $keys = $total = $cancelled = $approved = $rejected = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i);
            $labels[] = $d->format('M j');
            $keys[] = $d->toDateString();
            $total[] = (int) ($byDate[$d->toDateString()] ?? 0);
            $cancelled[] = (int) ($cancelledByDate[$d->toDateString()] ?? 0);
            $approved[] = (int) ($approvedByDate[$d->toDateString()] ?? 0);
            $rejected[] = (int) ($rejectedByDate[$d->toDateString()] ?? 0);
        }

        return [
            'labels' => $labels, 'keys' => $keys, 'currentKey' => $now->toDateString(),
            'total' => $total, 'cancelled' => $cancelled, 'approved' => $approved, 'rejected' => $rejected,
        ];
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

    /** Members-created / site-visits trends using the global chart period. */
    private function buildStatsTrends(string $viewType, string $viewDate, string $chartMonth, int $chartYear, Carbon $now): array
    {
        if ($viewType === 'year') {
            $start = Carbon::create($now->year - 5, 1, 1)->startOfYear();
            $end = $now->copy()->endOfDay();
            $step = 'year';
        } elseif ($viewType === 'month') {
            $start = Carbon::create($now->year, 1, 1)->startOfYear();
            $end = $now->copy()->endOfDay();
            $step = 'month';
        } else {
            $start = $now->copy()->subDays(29)->startOfDay();
            $end = $now->copy()->startOfDay();
            $step = 'day';
        }

        $memberRows = User::whereBetween('created_at', [$start, $end->copy()->endOfDay()])->get(['created_at']);
        $visitRows = SiteVisit::whereBetween('created_at', [$start, $end->copy()->endOfDay()])->get(['created_at']);

        if ($step === 'year') {
            $memberByPeriod = $memberRows->groupBy(fn($u) => $u->created_at->format('Y'))->map->count();
            $visitByPeriod = $visitRows->groupBy(fn($v) => $v->created_at->format('Y'))->map->count();
        } elseif ($step === 'month') {
            $memberByPeriod = $memberRows->groupBy(fn($u) => $u->created_at->format('Y-m'))->map->count();
            $visitByPeriod = $visitRows->groupBy(fn($v) => $v->created_at->format('Y-m'))->map->count();
        } else {
            $memberByPeriod = $memberRows->groupBy(fn($u) => $u->created_at->toDateString())->map->count();
            $visitByPeriod = $visitRows->groupBy(fn($v) => $v->created_at->toDateString())->map->count();
        }

        $memberTrend = [];
        $visitTrend = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if ($step === 'year') {
                $periodKey = $cursor->format('Y');
                $label = $periodKey;
            } elseif ($step === 'month') {
                $periodKey = $cursor->format('Y-m');
                $label = $cursor->format('M');
            } else {
                $periodKey = $cursor->toDateString();
                $label = $cursor->format('j');
            }
            $memberTrend[] = ['label' => $label, 'count' => (int) ($memberByPeriod[$periodKey] ?? 0)];
            $visitTrend[] = ['label' => $label, 'count' => (int) ($visitByPeriod[$periodKey] ?? 0)];
            if ($step === 'year') {
                $cursor->addYear();
            } elseif ($step === 'month') {
                $cursor->addMonth();
            } else {
                $cursor->addDay();
            }
        }

        return [$memberTrend, $visitTrend];
    }

    public function index(Request $request)
    {
        $now = now();
        $todayStr = $now->toDateString();
        $currentMonthStr = $now->format('Y-m');
        $hoursPerDay = self::CLOSE_HOUR - self::OPEN_HOUR; // 14

        // ---------------------------------------------------------------
        // Global Day / Month / Year selector — drives Booking Trend,
        // Cancellation Analysis, Peak Booking Hours, Court Utilization,
        // and Occupancy Timeline. Defaults to "day". Changing it, its
        // month/year sub-picker, a "reset" button, or clicking a Booking
        // Trend point all re-fetch via ?view_type=... without a page
        // reload (see viewDateData()).
        // ---------------------------------------------------------------
        $viewType = $this->resolveViewType($request);
        $viewDate = $this->resolveViewDate($request, $todayStr);

        $availableYears = $this->availableBookingYears($now->year);
        $chartMonth = $this->resolveChartMonth($request, $currentMonthStr);
        $chartYear = $this->resolveChartYear($request, $now->year, $availableYears);
        $availableChartMonths = $this->availableMonths($now);

        [$periodStart, $periodEnd] = $this->periodRange($viewType, $viewDate, $chartMonth, $chartYear, $now);
        $periodLabel = $this->resolvePeriodLabel($viewType, $viewDate, $chartMonth, $chartYear, $now);
        $isCurrentPeriod = $this->isCurrentPeriod($viewType, $viewDate, $chartMonth, $chartYear, $now, $todayStr, $currentMonthStr);

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
        // Always reflects *today*, regardless of the Day/Month/Year selector.
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
            ->where('status', 'pending_payment')->count();

        $kpis = [
            'today_bookings' => ['value' => $todayBookings, 'trend' => $bookingsTrend, 'spark' => $spark],
            'utilization' => ['value' => $utilizationRate, 'trend' => $utilizationTrend],
            'booked_hours' => ['value' => $bookedHours, 'trend' => $bookedHoursTrend],
            'active_customers' => ['value' => $activeCustomers, 'trend' => $activeTrend],
            'pending' => ['value' => $pendingBookings],
        ];

        // ---------------------------------------------------------------
        // Booking Trend — shape depends on $viewType (day/month/year), see
        // buildTrend(). Each point carries its own key so the chart can be
        // clicked to change the selected day/month/year.
        // ---------------------------------------------------------------
        $trend = $this->buildTrend($viewType, $viewDate, $chartMonth, $chartYear, $now);
        $trendTotal = array_sum($trend['total']);
        $trendCancelledTotal = array_sum($trend['cancelled']);
        $trendApprovedTotal = array_sum($trend['approved']);
        $trendRejectedTotal = array_sum($trend['rejected']);

        // ---------------------------------------------------------------
        // Cancellation Analysis — pending / cancelled / approved / rejected,
        // for the selected period.
        // ---------------------------------------------------------------
        $cancelData = $this->buildCancelData($periodStart, $periodEnd);
        $cancel = [
            'Booked' => $cancelData['booked'],
            'Cancelled' => $cancelData['cancelled'],
            'Approved' => $cancelData['approved'],
            'Rejected' => $cancelData['rejected'],
        ];
        $cancelTotal = array_sum($cancel);

        // ---------------------------------------------------------------
        // Peak Booking Hours — pending / cancelled / approved / rejected
        // bookings per hour slot, for the selected period.
        // ---------------------------------------------------------------
        $peak = $this->buildPeakHours($periodStart, $periodEnd);

        // ---------------------------------------------------------------
        // Court Utilization — % of hours booked per court, for the
        // selected period.
        // ---------------------------------------------------------------
        $courtUtilization = $this->buildCourtUtilization($periodStart, $periodEnd, $courts);

        // ---------------------------------------------------------------
        // Occupancy Timeline — court x hour grid, for the selected period.
        // Single day -> discrete available/occupied/maintenance states.
        // Month/Year -> % of days that hour was occupied.
        // ---------------------------------------------------------------
        $occupancy = $this->buildOccupancyPayload($periodStart, $periodEnd, $courts);
        $occupancyHours = range(self::OPEN_HOUR, self::CLOSE_HOUR - 1);

        // ---------------------------------------------------------------
        // Top Customers — by hours booked in the selected period
        // ---------------------------------------------------------------
        $selectedApproved = Booking::whereBetween('booking_date', [$periodStart, $periodEnd])
            ->where('status', 'approved')
            ->get(['start_time', 'end_time', 'court_id', 'user_id']);
        $topCustomers = $this->buildTopCustomers($selectedApproved);

        // ---------------------------------------------------------------
        // Recent Activities — reconstructed feed (approx, no audit log)
        // ---------------------------------------------------------------
        $recentActivities = $this->buildRecentActivities();

        // ---------------------------------------------------------------
        // Membership & Visit stats follow the same global period selector.
        // ---------------------------------------------------------------
        [$memberTrend, $visitTrend] = $this->buildStatsTrends($viewType, $viewDate, $chartMonth, $chartYear, $now);

        // ---------------------------------------------------------------
        // Charts — built with Larapex Charts (data prepared as plain arrays)
        // ---------------------------------------------------------------
        $font = 'Kanit, sans-serif';

        // NOTE: Booking Trend, Cancellation Analysis (donut), Peak Booking
        // Hours (line), Court Utilization (bar), and Occupancy Timeline
        // (heatmap) are all hand-rolled directly in the Blade view with raw
        // ApexCharts, instead of via Larapex's ->script(), because that
        // helper can't expose JS click events or be updated in place with
        // updateSeries()/updateOptions() — which all five need for the AJAX
        // Day/Month/Year switching.

        // Members & Visits — line charts, daily trend over the selected
        // month. Still built via Larapex for first paint; the AJAX month
        // switch below updates them in place via ApexCharts.getChartByID().
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
            'viewType',
            'viewDate',
            'chartMonth',
            'chartYear',
            'availableYears',
            'availableChartMonths',
            'periodLabel',
            'isCurrentPeriod',
            'todayStr',
            'currentMonthStr',
            'trend',
            'trendTotal',
            'trendCancelledTotal',
            'trendApprovedTotal',
            'trendRejectedTotal',
            'cancel',
            'cancelTotal',
            'peak',
            'occupancy',
            'occupancyHours',
            'courtUtilization',
            'topCustomers',
            'recentActivities',
            'memberChart',
            'visitChart',
        ));
    }

    /**
     * AJAX endpoint: returns Booking Trend, Peak Booking Hours,
     * Cancellation Analysis, Occupancy Timeline, and Court Utilization
     * data for a given ?view_type=day|month|year (+ view_date / chart_month
     * / chart_year), without re-rendering the whole page. Used by the
     * Day/Month/Year toggle, its sub-picker, the reset button, and the
     * Booking Trend chart's click handler.
     */
    public function viewDateData(Request $request)
    {
        $now = now();
        $todayStr = $now->toDateString();
        $currentMonthStr = $now->format('Y-m');

        $viewType = $this->resolveViewType($request);
        $viewDate = $this->resolveViewDate($request, $todayStr);
        $availableYears = $this->availableBookingYears($now->year);
        $chartMonth = $this->resolveChartMonth($request, $currentMonthStr);
        $chartYear = $this->resolveChartYear($request, $now->year, $availableYears);

        [$start, $end] = $this->periodRange($viewType, $viewDate, $chartMonth, $chartYear, $now);
        $periodLabel = $this->resolvePeriodLabel($viewType, $viewDate, $chartMonth, $chartYear, $now);
        $isCurrentPeriod = $this->isCurrentPeriod($viewType, $viewDate, $chartMonth, $chartYear, $now, $todayStr, $currentMonthStr);

        $courts = Court::orderBy('id')->get();

        $trend = $this->buildTrend($viewType, $viewDate, $chartMonth, $chartYear, $now);
        $peak = $this->buildPeakHours($start, $end);
        $cancelData = $this->buildCancelData($start, $end);
        $occupancy = $this->buildOccupancyPayload($start, $end, $courts);
        $courtUtilization = $this->buildCourtUtilization($start, $end, $courts);
        [$memberTrend, $visitTrend] = $this->buildStatsTrends($viewType, $viewDate, $chartMonth, $chartYear, $now);
        $selectedApproved = Booking::whereBetween('booking_date', [$start, $end])
            ->where('status', 'approved')
            ->get(['start_time', 'end_time', 'court_id', 'user_id']);

        return response()->json([
            'viewType' => $viewType,
            'viewDate' => $viewDate,
            'chartMonth' => $chartMonth,
            'chartYear' => $chartYear,
            'periodLabel' => $periodLabel,
            'isCurrentPeriod' => $isCurrentPeriod,
            'trend' => $trend,
            'trendTotal' => array_sum($trend['total']),
            'trendCancelledTotal' => array_sum($trend['cancelled']),
            'trendApprovedTotal' => array_sum($trend['approved']),
            'trendRejectedTotal' => array_sum($trend['rejected']),
            'peakHours' => $peak,
            'cancel' => [
                'booked' => $cancelData['booked'],
                'cancelled' => $cancelData['cancelled'],
                'approved' => $cancelData['approved'],
                'rejected' => $cancelData['rejected'],
                'total' => array_sum($cancelData),
            ],
            'occupancy' => $occupancy,
            'courtUtilization' => $courtUtilization,
            'topCustomers' => $this->buildTopCustomers($selectedApproved),
            'memberLabels' => array_column($memberTrend, 'label'),
            'memberCounts' => array_column($memberTrend, 'count'),
            'visitLabels' => array_column($visitTrend, 'label'),
            'visitCounts' => array_column($visitTrend, 'count'),
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
        $todayStr = $now->toDateString();
        $currentMonthStr = $now->format('Y-m');
        $viewType = $this->resolveViewType($request);
        $viewDate = $this->resolveViewDate($request, $todayStr);
        $availableYears = $this->availableBookingYears($now->year);
        $chartMonth = $this->resolveChartMonth($request, $currentMonthStr);
        $chartYear = $this->resolveChartYear($request, $now->year, $availableYears);
        [$start, $end] = $this->periodRange($viewType, $viewDate, $chartMonth, $chartYear, $now);

        $selectedApproved = Booking::whereBetween('booking_date', [$start, $end])
            ->where('status', 'approved')
            ->get(['start_time', 'end_time', 'court_id', 'user_id']);

        return response()->json([
            'updatedAt' => $now->format('H:i:s'),
            'topCustomers' => $this->buildTopCustomers($selectedApproved),
            'recentActivities' => $this->buildRecentActivities(),
        ]);
    }

    /**
     * AJAX endpoint: returns Members-created / Site-visits daily trends for
     * a given ?view_month=Y-m, without re-rendering the whole page. Used by
     * the "สถิติสมาชิกและผู้เข้าชม" month picker.
     */
    public function statsData(Request $request)
    {
        $now = now();
        $todayStr = $now->toDateString();
        $currentMonthStr = $now->format('Y-m');
        $viewType = $this->resolveViewType($request);
        $viewDate = $this->resolveViewDate($request, $todayStr);
        $availableYears = $this->availableBookingYears($now->year);
        $chartMonth = $this->resolveChartMonth($request, $currentMonthStr);
        $chartYear = $this->resolveChartYear($request, $now->year, $availableYears);
        [$memberTrend, $visitTrend] = $this->buildStatsTrends($viewType, $viewDate, $chartMonth, $chartYear, $now);
        $periodLabel = $this->resolvePeriodLabel($viewType, $viewDate, $chartMonth, $chartYear, $now);

        return response()->json([
            'periodLabel' => $periodLabel,
            'memberLabels' => array_column($memberTrend, 'label'),
            'memberCounts' => array_column($memberTrend, 'count'),
            'visitLabels' => array_column($visitTrend, 'label'),
            'visitCounts' => array_column($visitTrend, 'count'),
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

        $status = $request->query('status', 'pending_payment');
        $court_id = $request->query('court_id');
        $date = $request->query('date');

        $bookings = Booking::with(['user', 'court'])
            //วันที่
            ->when($date, fn($q) => $q->whereDate('booking_date', $date))

            //สถานะ
            ->when($status === 'pending_payment', fn($q) => $q->whereDate('booking_date', '>=', now()->toDateString()))
            ->where('status', $status ? $status : 'pending_payment')

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
            'pending' => (clone $sideStatsQuery)->where('status', 'pending_payment')->count(),
            'approved' => (clone $sideStatsQuery)->where('status', 'approved')->count(),
            'cancelled' => (clone $sideStatsQuery)->whereIn('status', ['rejected', 'cancelled'])->count(),
        ];

        $courts = Court::all();

        return view('admin.bookings', compact('stats', 'bookings', 'status', 'date', 'court_id', 'sideStats', 'courts', 'range'));
    }
}
