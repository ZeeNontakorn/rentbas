<?php

namespace App\Http\Controllers;

use App\Mail\BookingApprovedMail;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class BookingController extends Controller
{
    /**
     * Landing page — เลือกสนามและเวลา
     */
    public function index(Request $request)
    {
        $courts = Court::orderBy('name')->get();
        $dateParam = $request->query('date', now()->toDateString());

        try {
            $parsedDate = Carbon::parse($dateParam);
            $maxDate = now()->addMonth();
            $minDate = now()->startOfDay();

            if ($parsedDate->startOfDay()->gt($maxDate->startOfDay())) {
                $date = $maxDate->toDateString();
            } elseif ($parsedDate->startOfDay()->lt($minDate)) {
                $date = now()->toDateString();
            } else {
                $date = $parsedDate->toDateString();
            }
        } catch (\Exception $e) {
            $date = now()->toDateString();
        }

        $courtId = $request->query('court_id', $courts->first()?->id);
        $selectedCourt = $courts->firstWhere('id', $courtId);

        $slots = [];
        if ($selectedCourt) {
            $dateCarbon = Carbon::parse($date);
            for ($h = 6; $h < 22; $h++) {
                $start = sprintf('%02d:00:00', $h);
                $end = sprintf('%02d:00:00', $h + 1);

                $booking = Booking::where('court_id', $selectedCourt->id)
                    ->whereDate('booking_date', $date)
                    ->whereIn('status', ['pending', 'approved'])
                    ->where('start_time', $start)
                    ->where('end_time', $end)
                    ->first();

                $slotStart = $dateCarbon->copy()->setTimeFromTimeString($start);
                $slotEnd = $dateCarbon->copy()->setTimeFromTimeString($end);

                $isClosed = $selectedCourt->isClosedAt($slotStart, $slotEnd);
                $isPast = $slotStart->lte(now());

                $status = 'available';
                if ($isClosed) {
                    $status = 'closed';
                } elseif ($booking) {
                    $status = $booking->status; // pending | approved
                } elseif ($isPast) {
                    $status = 'past';
                }

                $slots[] = [
                    'label' => sprintf('%02d:00 - %02d:00', $h, $h + 1),
                    'start' => $start,
                    'end' => $end,
                    'status' => $status,
                    'booking' => $booking,
                ];
            }
        }

        return view('booking.index', compact('courts', 'date', 'selectedCourt', 'slots'));
    }

    /**
     * Show hourly slots for a court (kept for backwards compatibility if needed, but not used in new flow)
     */
    public function show(Court $court, Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        return redirect()->route('booking.index', ['court_id' => $court->id, 'date' => $date]);
    }

    /**
     * Store a new booking
     */
    public function store(Request $request)
    {
        $maxDate = now()->addMonth()->toDateString();

        $data = $request->validate([
            'court_id' => ['required', 'integer', 'exists:courts,id'],
            'booking_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . $maxDate],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        if ($data['start_time'] < '06:00' || $data['end_time'] > '22:00') {
            return back()->withErrors(['start_time' => 'สามารถจองได้เฉพาะช่วง 06:00–22:00 เท่านั้น']);
        }

        $court = Court::findOrFail($data['court_id']);
        $startAt = Carbon::parse("{$data['booking_date']} {$data['start_time']}");
        $endAt = Carbon::parse("{$data['booking_date']} {$data['end_time']}");

        if ($startAt->lte(now())) {
            return back()->withErrors(['start_time' => 'ไม่สามารถจองช่วงเวลาที่ผ่านมาแล้วได้']);
        }

        if ($court->isClosedAt($startAt, $endAt)) {
            return back()->withErrors(['court_id' => 'สนามนี้ปิดให้บริการในช่วงเวลาที่เลือก']);
        }

        $startDb = $data['start_time'] . ':00';
        $endDb = $data['end_time'] . ':00';

        $booking = DB::transaction(function () use ($data, $startDb, $endDb, $court, $request) {
            $overlap = Booking::where('court_id', $data['court_id'])
                ->whereDate('booking_date', $data['booking_date'])
                ->whereIn('status', ['pending', 'approved'])
                ->where(function ($q) use ($startDb, $endDb) {
                    $q->where('start_time', '<', $endDb)
                        ->where('end_time', '>', $startDb);
                })
                ->lockForUpdate()
                ->exists();

            if ($overlap)
                return null;

            $booking = Booking::create([
                'user_id' => $request->user()->id,
                'court_id' => $data['court_id'],
                'booking_date' => $data['booking_date'],
                'start_time' => $startDb,
                'end_time' => $endDb,
                'status' => 'pending',
            ]);

            // Notify admins
            User::where('role', 'admin')->get()->each(function ($admin) use ($booking, $request, $court, $data) {
                // ส่งข้อความแจ้งเตือนพร้อมรายละเอียดการจอง โดยใช้ '|' เป็นตัวแบ่งระหว่างข้อความหลักกับรายละเอียดเพิ่มเติม
                Notification::create([
                    'user_id' => $admin->id,
                    'title' => 'คำขอจองใหม่',
                    'message' => "คุณ {$request->user()->name} ขอจอง {$court->name} |วันที่ {$data['booking_date']} เวลา {$data['start_time']}-{$data['end_time']}",
                ]);
            });

            return $booking;
        });

        if (!$booking) {
            return back()->withErrors(['start_time' => 'ช่วงเวลาที่เลือกมีการจองอยู่แล้ว กรุณาเลือกเวลาอื่น']);
        }

        return back()->with('success_booking', [
            'court_name' => $court->name,
            'date' => $data['booking_date'],
            'time' => substr($data['start_time'], 0, 5) . ' - ' . substr($data['end_time'], 0, 5),
            'status' => 'รออนุมัติ'
        ]);
    }

    /**
     * Cancel a booking (user)
     */
    public function cancel(Request $request, Booking $booking)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        if ($booking->status !== 'pending') {
            return back()->withErrors(['status' => 'ไม่สามารถยกเลิกรายการนี้ได้ เนื่องจากได้รับการอนุมัติหรือดำเนินการไปแล้ว']);
        }

        if ($booking->isStarted()) {
            return back()->withErrors(['status' => 'ไม่สามารถยกเลิกรายการที่ถึงเวลาเล่นแล้วได้']);
        }

        $booking->update(['status' => 'cancelled']);

        User::where('role', 'admin')->get()->each(function ($admin) use ($booking) {
            $bDate = Carbon::parse($booking->booking_date)->toDateString();
            Notification::create([
                'user_id' => $admin->id,
                'title' => 'ผู้ใช้ยกเลิกการจอง',
                'message' => "คุณ {$booking->user->name} ยกเลิกการจอง {$booking->court->name} วันที่ {$bDate}",
            ]);
        });

        return back()->with('success', 'ยกเลิกการจองเรียบร้อย');
    }

    public function approve(Booking $booking)
    {
        if ($booking->status !== 'pending') {
            return back()->withErrors(['status' => 'รายการนี้ถูกดำเนินการไปแล้ว']);
        }

        $bDate = Carbon::parse($booking->booking_date)->toDateString();
        $startAt = Carbon::parse("{$bDate} {$booking->start_time}");
        $endAt = Carbon::parse("{$bDate} {$booking->end_time}");

        if ($booking->court->isClosedAt($startAt, $endAt)) {
            return back()->withErrors(['status' => 'ไม่สามารถอนุมัติได้เนื่องจากสนามปิดให้บริการในช่วงเวลานี้']);
        }

        $overlap = Booking::where('court_id', $booking->court_id)
            ->whereDate('booking_date', $bDate)
            ->where('status', 'approved')
            ->where('id', '!=', $booking->id)
            ->where(function ($q) use ($booking) {
                $q->where('start_time', '<', $booking->end_time)
                    ->where('end_time', '>', $booking->start_time);
            })
            ->exists();

        if ($overlap) {
            return back()->withErrors(['status' => 'ไม่สามารถอนุมัติได้เนื่องจากมีรายการจองอื่นที่อนุมัติแล้วในช่วงเวลานี้ (ทับซ้อนกัน)']);
        }

        $booking->update(['status' => 'approved']);

        Notification::create([
            'user_id' => $booking->user_id,
            'title' => 'การจองได้รับการอนุมัติ',
            'message' => "การจอง {$booking->court->name} วันที่ {$bDate} เวลา {$booking->start_time}-{$booking->end_time} ได้รับการอนุมัติแล้ว",
        ]);

        if ($booking->user?->email) {
            Mail::to($booking->user->email)
                ->send(new BookingApprovedMail($booking));
        }

        return back()->with('success', 'อนุมัติการจองเรียบร้อย');
    }

    /**
     * Reject booking (admin)
     */
    public function reject(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'reject_reason' => ['required', 'string', 'max:500'],
        ]);

        if ($booking->status !== 'pending') {
            return back()->withErrors(['status' => 'รายการนี้ถูกดำเนินการไปแล้ว']);
        }

        $booking->update([
            'status' => 'rejected',
            'reject_reason' => $data['reject_reason'],
        ]);

        $bDate = Carbon::parse($booking->booking_date)->toDateString();
        Notification::create([
            'user_id' => $booking->user_id,
            'title' => 'การจองถูกปฏิเสธ',
            'message' => "การจอง {$booking->court->name} วันที่ {$bDate} ถูกปฏิเสธ — เหตุผล: {$data['reject_reason']}",
        ]);

        return back()->with('success', 'ปฏิเสธการจองเรียบร้อย');
    }

    /**
     * Booking history (current/past)
     */
    public function history(Request $request)
    {
        $userId = $request->user()->id;
        $today = now()->toDateString();

        $current = Booking::with('court')
            ->where('user_id', $userId)
            ->where(function ($q) use ($today) {
                $q->whereIn('status', ['pending', 'approved'])
                    ->whereDate('booking_date', '>=', $today);
            })
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();

        $past = Booking::with('court')
            ->where('user_id', $userId)
            ->where(function ($q) use ($today) {
                $q->whereIn('status', ['rejected', 'cancelled'])
                    ->orWhereDate('booking_date', '<', $today);
            })
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->get();

        return view('booking.history', compact('current', 'past'));
    }
}
