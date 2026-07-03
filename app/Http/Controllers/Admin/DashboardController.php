<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
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

    public function index()
    {
        $stats = $this->getCommonStats();

        // Get courts with booking counts for today
        $today = now()->toDateString();
        $courts = \App\Models\Court::withCount([
            'bookings as pending_count' => fn($q) => $q->whereDate('booking_date', $today)->where('status', 'pending'),
            'bookings as approved_count' => fn($q) => $q->whereDate('booking_date', $today)->where('status', 'approved'),
            'bookings as cancelled_count' => fn($q) => $q->whereDate('booking_date', $today)->whereIn('status', ['rejected', 'cancelled']),
        ])->get();

        // Check if court is active right now
        $nowTime = now()->format('H:i:s');
        foreach($courts as $court) {
            $activeNow = \App\Models\Booking::where('court_id', $court->id)
                ->whereDate('booking_date', $today)
                ->where('status', 'approved')
                ->where('start_time', '<=', $nowTime)
                ->where('end_time', '>', $nowTime)
                ->count();
            $court->active_now = $activeNow;
            $court->total_today = $court->pending_count + $court->approved_count + $court->cancelled_count;
        }

        $usersMonth = \App\Models\User::where('created_at', '>=', now()->startOfMonth())->count();
        $usersWeek = \App\Models\User::where('created_at', '>=', now()->startOfWeek())->count();

        $visitsMonth = \App\Models\SiteVisit::where('created_at', '>=', now()->startOfMonth())->count();
        $visitsWeek = \App\Models\SiteVisit::where('created_at', '>=', now()->startOfWeek())->count();

        return view('admin.dashboard', compact('stats', 'courts', 'usersMonth', 'usersWeek', 'visitsMonth', 'visitsWeek'));
    }

    public function bookings(Request $request)
    {
        $stats = $this->getCommonStats();

        $status = $request->query('status');
        $court_id = $request->query('court_id');
        $date = $request->query('date', now()->toDateString()); // Default today

        $bookings = Booking::with(['user', 'court'])
            ->whereDate('booking_date', $date)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($court_id, fn ($q) => $q->where('court_id', $court_id))
            ->orderByDesc('start_time')
            ->paginate(20)
            ->withQueryString();

        $range = $request->query('range', 7);
        $startDate = now()->subDays((int)$range);

        $sideStatsQuery = Booking::where('created_at', '>=', $startDate);
        
        $sideStats = [
            'total' => (clone $sideStatsQuery)->count(),
            'pending' => (clone $sideStatsQuery)->where('status', 'pending')->count(),
            'approved' => (clone $sideStatsQuery)->where('status', 'approved')->count(),
            'cancelled' => (clone $sideStatsQuery)->whereIn('status', ['rejected', 'cancelled'])->count(),
        ];

        $courts = \App\Models\Court::all();

        return view('admin.bookings', compact('stats', 'bookings', 'status', 'date', 'court_id', 'sideStats', 'courts', 'range'));
    }
}
