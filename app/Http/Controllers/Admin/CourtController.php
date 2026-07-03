<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClosure;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CourtController extends Controller
{
    public function index(Request $request)
    {
        $courts = Court::orderBy('id')->get();
        $dateParam = $request->query('date', now()->toDateString());
        try {
            $date = Carbon::parse($dateParam)->toDateString();
        } catch (\Exception $e) {
            $date = now()->toDateString();
        }
        $courtId = $request->query('court_id', $courts->first()?->id);
        $selectedCourt = $courts->firstWhere('id', $courtId);

        $slots = [];
        if ($selectedCourt) {
            $dateCarbon = Carbon::parse($date);
            
            // Check if court is globally closed
            $isGloballyClosed = ($selectedCourt->court_status === 'closed');

            for ($h = 6; $h < 22; $h++) {
                $start = sprintf('%02d:00:00', $h);
                $end = sprintf('%02d:00:00', $h + 1);

                $booking = Booking::where('court_id', $selectedCourt->id)
                    ->whereDate('booking_date', $date)
                    ->whereIn('status', ['pending', 'approved'])
                    ->where('start_time', $start)
                    ->where('end_time', $end)
                    ->first();

                $closure = CourtClosure::where('court_id', $selectedCourt->id)
                    ->whereDate('date', $date)
                    ->where('start_time', '<', $end)
                    ->where('end_time', '>', $start)
                    ->first();

                $status = 'available';
                
                if ($isGloballyClosed) {
                    $status = 'unavailable'; // Or a specific 'closed' status
                } elseif ($closure) {
                    $status = $closure->type; // 'maintenance' or 'unavailable'
                } elseif ($booking) {
                    $status = 'booking_' . $booking->status; // 'booking_pending' or 'booking_approved'
                }

                $slots[] = [
                    'label' => sprintf('%02d:00 - %02d:00', $h, $h + 1),
                    'start' => $start,
                    'end'   => $end,
                    'status'=> $status,
                ];
            }
        }

        return view('admin.courts', compact('courts', 'date', 'selectedCourt', 'slots'));
    }

    public function updateStatus(Request $request, Court $court)
    {
        $data = $request->validate([
            'court_status' => 'required|in:open,closed',
        ]);

        $court->update([
            'court_status' => $data['court_status']
        ]);

        return back()->with('success', 'อัปเดตสถานะสนามเรียบร้อยแล้ว');
    }

    public function updateSlot(Request $request)
    {
        $data = $request->validate([
            'court_id' => ['required', 'exists:courts,id'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['required', 'date_format:H:i:s'],
            'status' => ['required', 'in:available,unavailable,maintenance'],
        ]);

        // Delete existing closure if any
        CourtClosure::where('court_id', $data['court_id'])
            ->where('date', $data['date'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->delete();

        if ($data['status'] !== 'available') {
            CourtClosure::create([
                'court_id' => $data['court_id'],
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'type' => $data['status'],
            ]);
        }

        return back()->with('success', 'อัปเดตสถานะช่วงเวลาเรียบร้อยแล้ว');
    }
}
