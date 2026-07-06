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
     * Landing page — เลือกสนาม (สนามเดียว) และเวลา (เลือกได้หลายช่วงเวลาพร้อมกัน)
     */
    public function index(Request $request)
    {
        $courts = Court::orderBy('name')->get();

        $dateParam = $request->query('date', now()->toDateString());
        try {
            $date = Carbon::parse($dateParam)->toDateString();
        } catch (\Exception $e) {
            $date = now()->toDateString();
        }

        $courtId = $request->query('court_id', $courts->first()?->id);
        $selectedCourt = $courts->firstWhere('id', $courtId);

        $slots = $selectedCourt ? $this->buildSlotsForCourt($selectedCourt, $date) : [];

        return view('booking.index', compact('courts', 'date', 'selectedCourt', 'slots'));
    }

    /**
     * สร้าง array ของ slot รายชั่วโมง (06:00–22:00) สำหรับสนามที่ระบุ ในวันที่ระบุ
     */
    protected function buildSlotsForCourt(Court $court, string $date): array
    {
        $slots = [];
        $dateCarbon = Carbon::parse($date);

        for ($h = 6; $h < 22; $h++) {
            $start = sprintf('%02d:00:00', $h);
            $end = sprintf('%02d:00:00', $h + 1);

            $booking = Booking::where('court_id', $court->id)
                ->whereDate('booking_date', $date)
                ->whereIn('status', ['pending', 'approved'])
                ->where('start_time', $start)
                ->where('end_time', $end)
                ->first();

            $slotStart = $dateCarbon->copy()->setTimeFromTimeString($start);
            $slotEnd = $dateCarbon->copy()->setTimeFromTimeString($end);
            $isClosed = $court->isClosedAt($slotStart, $slotEnd);
            $isPast = $slotStart->lte(now());

            $status = 'available';
            if ($isClosed) $status = 'closed';
            elseif ($booking) $status = $booking->status; // pending | approved
            elseif ($isPast) $status = 'past';

            $slots[] = [
                'label' => sprintf('%02d:00 - %02d:00', $h, $h + 1),
                'start' => $start,
                'end' => $end,
                'status' => $status,
                'booking' => $booking,
            ];
        }

        return $slots;
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
     * Store one or more bookings at once (เลือกได้หลายสนาม/หลายเวลาในคราวเดียว)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'bookings' => ['required', 'array', 'min:1'],
            'bookings.*.court_id' => ['required', 'integer', 'exists:courts,id'],
            'bookings.*.start_time' => ['required', 'date_format:H:i'],
            'bookings.*.end_time' => ['required', 'date_format:H:i', 'after:bookings.*.start_time'],
        ]);

        $bookingDate = $data['booking_date'];
        $items = $data['bookings'];

        foreach ($items as $item) {
            if ($item['start_time'] < '06:00' || $item['end_time'] > '22:00') {
                return back()->withErrors(['start_time' => 'สามารถจองได้เฉพาะช่วง 06:00–22:00 เท่านั้น']);
            }
        }

        $result = DB::transaction(function () use ($items, $bookingDate, $request) {
            $created = [];
            $failed = [];

            foreach ($items as $item) {
                $court = Court::findOrFail($item['court_id']);
                $startAt = Carbon::parse("{$bookingDate} {$item['start_time']}");
                $endAt = Carbon::parse("{$bookingDate} {$item['end_time']}");

                if ($startAt->lte(now())) {
                    $failed[] = "{$court->name} ({$item['start_time']}-{$item['end_time']}): ช่วงเวลาที่ผ่านมาแล้ว";
                    continue;
                }

                if ($court->isClosedAt($startAt, $endAt)) {
                    $failed[] = "{$court->name} ({$item['start_time']}-{$item['end_time']}): สนามปิดให้บริการ";
                    continue;
                }

                $startDb = $item['start_time'] . ':00';
                $endDb = $item['end_time'] . ':00';

                $overlap = Booking::where('court_id', $court->id)
                    ->whereDate('booking_date', $bookingDate)
                    ->whereIn('status', ['pending', 'approved'])
                    ->where(function ($q) use ($startDb, $endDb) {
                        $q->where('start_time', '<', $endDb)
                          ->where('end_time', '>', $startDb);
                    })
                    ->lockForUpdate()
                    ->exists();

                if ($overlap) {
                    $failed[] = "{$court->name} ({$item['start_time']}-{$item['end_time']}): มีการจองอยู่แล้ว";
                    continue;
                }

                $booking = Booking::create([
                    'user_id' => $request->user()->id,
                    'court_id' => $court->id,
                    'booking_date' => $bookingDate,
                    'start_time' => $startDb,
                    'end_time' => $endDb,
                    'status' => 'pending',
                ]);

                // Notify admins
                User::where('role', 'admin')->get()->each(function ($admin) use ($booking, $request, $court, $bookingDate, $item) {
                    Notification::create([
                        'user_id' => $admin->id,
                        'title' => 'คำขอจองใหม่',
                        'message' => "คุณ {$request->user()->name} ขอจอง {$court->name} |วันที่ {$bookingDate} เวลา {$item['start_time']}-{$item['end_time']}",
                    ]);
                });

                $created[] = [
                    'court_name' => $court->name,
                    'date' => $bookingDate,
                    'time' => substr($item['start_time'], 0, 5) . ' - ' . substr($item['end_time'], 0, 5),
                    'status' => 'รออนุมัติ',
                ];
            }

            return ['created' => $created, 'failed' => $failed];
        });

        if (empty($result['created'])) {
            return back()->withErrors(['bookings' => 'ไม่สามารถทำการจองได้: ' . implode(', ', $result['failed'])]);
        }

        $response = back()->with('success_booking', $result['created']);

        if (!empty($result['failed'])) {
            $response->withErrors(['bookings' => 'บางรายการจองไม่สำเร็จ: ' . implode(', ', $result['failed'])]);
        }

        return $response;
    }

    /**
     * Cancel a booking (user)
     */
    public function cancel(Request $request, Booking $booking)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        if (!in_array($booking->status,['pending','approved'])) {
            return back()->withErrors(['status'=>'ไม่สามารถยกเลิกรายการนี้ได้']);
        }

        if ($booking->isStarted()) {
            return back()->withErrors(['status'=>'ไม่สามารถยกเลิกรายการที่ถึงเวลาเล่นแล้วได้']);
        }

        $booking->update(['status'=>'cancelled']);

        User::where('role','admin')->get()->each(function($admin) use ($booking) {
            $bDate = Carbon::parse($booking->booking_date)->toDateString();
            Notification::create([
                'user_id'=>$admin->id,
                'title'=>'ผู้ใช้ยกเลิกการจอง',
                'message'=>"คุณ {$booking->user->name} ยกเลิกการจอง {$booking->court->name} วันที่ {$bDate}",
            ]);
        });

        return back()->with('success','ยกเลิกการจองเรียบร้อย');
    }

    public function approve(Booking $booking)
    {
        if ($booking->status !== 'pending') {
            return back()->withErrors(['status'=>'รายการนี้ถูกดำเนินการไปแล้ว']);
        }

        $bDate = Carbon::parse($booking->booking_date)->toDateString();
        $startAt = Carbon::parse("{$bDate} {$booking->start_time}");
        $endAt = Carbon::parse("{$bDate} {$booking->end_time}");

        if ($booking->court->isClosedAt($startAt, $endAt)) {
            return back()->withErrors(['status'=>'ไม่สามารถอนุมัติได้เนื่องจากสนามปิดให้บริการในช่วงเวลานี้']);
        }

        $overlap = Booking::where('court_id', $booking->court_id)
            ->whereDate('booking_date', $bDate)
            ->where('status', 'approved')
            ->where('id', '!=', $booking->id)
            ->where(function($q) use ($booking) {
                $q->where('start_time','<',$booking->end_time)
                  ->where('end_time','>',$booking->start_time);
            })
            ->exists();

        if ($overlap) {
            return back()->withErrors(['status'=>'ไม่สามารถอนุมัติได้เนื่องจากมีรายการจองอื่นที่อนุมัติแล้วในช่วงเวลานี้ (ทับซ้อนกัน)']);
        }

        $booking->update(['status'=>'approved']);

        Notification::create([
            'user_id'=>$booking->user_id,
            'title'=>'การจองได้รับการอนุมัติ',
            'message'=>"การจอง {$booking->court->name} วันที่ {$bDate} เวลา {$booking->start_time}-{$booking->end_time} ได้รับการอนุมัติแล้ว",
        ]);

        if ($booking->user?->email) {
            Mail::to($booking->user->email)
                ->send(new BookingApprovedMail($booking));
        }

        return back()->with('success','อนุมัติการจองเรียบร้อย');
    }

    /**
     * Reject booking (admin)
     */
    public function reject(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'reject_reason'=>['required','string','max:500'],
        ]);

        if ($booking->status !== 'pending') {
            return back()->withErrors(['status'=>'รายการนี้ถูกดำเนินการไปแล้ว']);
        }

        $booking->update([
            'status'=>'rejected',
            'reject_reason'=>$data['reject_reason'],
        ]);

        $bDate = Carbon::parse($booking->booking_date)->toDateString();
        Notification::create([
            'user_id'=>$booking->user_id,
            'title'=>'การจองถูกปฏิเสธ',
            'message'=>"การจอง {$booking->court->name} วันที่ {$bDate} ถูกปฏิเสธ — เหตุผล: {$data['reject_reason']}",
        ]);

        return back()->with('success','ปฏิเสธการจองเรียบร้อย');
    }

    /**
     * Bulk approve bookings (admin)
     */
    public function bulkApprove(Request $request)
    {
        $data = $request->validate([
            'booking_ids' => ['required', 'array', 'min:1'],
            'booking_ids.*' => ['integer', 'exists:bookings,id'],
        ]);

        $bookings = Booking::whereIn('id', $data['booking_ids'])
            ->where('status', 'pending')
            ->get();

        $approvedCount = 0;

        foreach ($bookings as $booking) {
            $bDate = Carbon::parse($booking->booking_date)->toDateString();
            $startAt = Carbon::parse("{$bDate} {$booking->start_time}");
            $endAt = Carbon::parse("{$bDate} {$booking->end_time}");

            if ($booking->court->isClosedAt($startAt, $endAt)) {
                continue;
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
                continue;
            }

            $booking->update(['status' => 'approved']);
            $approvedCount++;

            Notification::create([
                'user_id' => $booking->user_id,
                'title' => 'การจองได้รับการอนุมัติ',
                'message' => "การจอง {$booking->court->name} วันที่ {$bDate} เวลา {$booking->start_time}-{$booking->end_time} ได้รับการอนุมัติแล้ว",
            ]);

            if ($booking->user?->email) {
                Mail::to($booking->user->email)->send(new BookingApprovedMail($booking));
            }
        }

        return back()->with('success', "อนุมัติสำเร็จ {$approvedCount} จาก " . $bookings->count() . ' รายการ');
    }

    /**
     * Bulk reject bookings (admin)
     */
    public function bulkReject(Request $request)
    {
        $data = $request->validate([
            'booking_ids' => ['required', 'array', 'min:1'],
            'booking_ids.*' => ['integer', 'exists:bookings,id'],
        ]);

        $bookings = Booking::whereIn('id', $data['booking_ids'])
            ->where('status', 'pending')
            ->get();

        foreach ($bookings as $booking) {
            $booking->update([
                'status' => 'rejected',
                'reject_reason' => 'Bulk rejected by admin',
            ]);

            $bDate = Carbon::parse($booking->booking_date)->toDateString();
            Notification::create([
                'user_id' => $booking->user_id,
                'title' => 'การจองถูกปฏิเสธ',
                'message' => "การจอง {$booking->court->name} วันที่ {$bDate} ถูกปฏิเสธ — เหตุผล: ปฏิเสธพร้อมกันหลายรายการโดยแอดมิน",
            ]);
        }

        return back()->with('success', 'ปฏิเสธ ' . $bookings->count() . ' รายการเรียบร้อยแล้ว');
    }

    /**
     * Booking history (current/past)
     */
    public function history(Request $request)
    {
        $userId = $request->user()->id;
        $today = now()->toDateString();

        $current = Booking::with('court')
            ->where('user_id',$userId)
            ->where(function($q) use ($today){
                $q->whereIn('status',['pending','approved'])
                  ->whereDate('booking_date','>=',$today);
            })
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();

        $past = Booking::with('court')
            ->where('user_id',$userId)
            ->where(function($q) use ($today){
                $q->whereIn('status',['rejected','cancelled'])
                  ->orWhereDate('booking_date','<',$today);
            })
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->get();

        return view('booking.history', compact('current','past'));
    }
}