<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Models\Notification;
use App\Models\PrivateTrainingBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrivateTrainingController extends Controller
{
    /** ช่วงเวลาเปิดให้จองต่อวัน (ตามตารางเวลาของโค้ช/ผู้ช่วยสนามฝั่งแอดมิน) */
    private const OPEN_HOUR = 8;
    private const CLOSE_HOUR = 22;

    /**
     * หน้ารายชื่อโค้ช/ผู้ช่วยสนาม ให้ user เลือกดูโปรไฟล์และตารางว่าง
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $coaches = User::where('role', 'staff')
            ->where('membership_type', 'coach')
            ->with('staffProfile')
            ->when($search, fn ($q) => $q->where(
                fn ($qq) => $qq->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->get();

        $myRequests = PrivateTrainingBooking::with('coach')
            ->where('user_id', $request->user()->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('private-training.index', compact('coaches', 'search', 'myRequests'));
    }

    /**
     * หน้าโปรไฟล์โค้ชคนเดียว + ตารางว่างวันนี้ (ดึงจากตาราง availabilities ที่แอดมินตั้งไว้ในเมนู "โค้ช และผู้ช่วย")
     */
    public function show(User $coach)
    {
        $this->assertIsCoach($coach);

        $today = now()->toDateString();
        $now = now();

        // สถานะที่แอดมินตั้งไว้วันนี้ (ทุกสนาม รวมกัน) — ถ้าช่วงไหนแอดมินมาร์คว่า "ไม่ว่าง" (booked) ที่สนามใดสนามหนึ่ง
        // แปลว่าโค้ชติดงานอยู่ช่วงนั้น จองเทรนส่วนตัวไม่ได้
        $busyRanges = $coach->availabilities()
            ->whereDate('date', $today)
            ->where('status', 'booked')
            ->get(['start_time', 'end_time']);

        // ช่วงที่ถูกจองเทรนส่วนตัวไปแล้ว (ของคนอื่นหรือของตัวเอง) รอ/อนุมัติแล้ว
        $reservedRanges = PrivateTrainingBooking::where('coach_id', $coach->id)
            ->whereDate('date', $today)
            ->whereIn('status', ['pending', 'approved'])
            ->get(['start_time', 'end_time', 'user_id']);

        $timeline = [];
        for ($h = self::OPEN_HOUR; $h < self::CLOSE_HOUR; $h++) {
            $start = sprintf('%02d:00:00', $h);
            $end = sprintf('%02d:00:00', $h + 1);
            $slotStart = Carbon::parse($today.' '.$start);

            $isPast = $slotStart->lte($now);

            $isBusy = $busyRanges->first(fn ($r) => $r->start_time < $end && $r->end_time > $start) !== null;
            $reserved = $reservedRanges->first(fn ($r) => $r->start_time < $end && $r->end_time > $start);

            $status = 'available';
            if ($isPast) {
                $status = 'past';
            } elseif ($isBusy) {
                $status = 'unavailable';
            } elseif ($reserved) {
                $status = $reserved->user_id === auth()->id() ? 'mine' : 'reserved';
            }

            $timeline[] = [
                'hour' => $h,
                'start' => sprintf('%02d:00', $h),
                'end' => sprintf('%02d:00', $h + 1),
                'status' => $status,
            ];
        }

        $myUpcoming = PrivateTrainingBooking::where('coach_id', $coach->id)
            ->where('user_id', auth()->id())
            ->whereDate('date', '>=', $today)
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('private-training.show', [
            'coach' => $coach,
            'staffProfile' => $coach->staffProfile,
            'today' => $today,
            'timeline' => $timeline,
            'myUpcoming' => $myUpcoming,
        ]);
    }

    /**
     * ส่งคำขอจองเทรนเนอร์ส่วนตัว (สถานะ pending รออนุมัติจากแอดมิน)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'coach_id' => ['required', 'integer', 'exists:users,id'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $coach = User::findOrFail($data['coach_id']);
        $this->assertIsCoach($coach);

        // MVP: จองได้เฉพาะ "วันนี้" เท่านั้น (ตามตารางเวลาที่แอดมินตั้งไว้)
        $date = now()->toDateString();

        $openTime = sprintf('%02d:00', self::OPEN_HOUR);
        $closeTime = sprintf('%02d:00', self::CLOSE_HOUR);

        if ($data['start_time'] < $openTime || $data['end_time'] > $closeTime) {
            return back()->withErrors(['start_time' => "สามารถจองได้เฉพาะช่วง {$openTime}–{$closeTime} น. เท่านั้น"]);
        }

        $startAt = Carbon::parse("{$date} {$data['start_time']}");
        if ($startAt->lte(now())) {
            return back()->withErrors(['start_time' => 'ไม่สามารถจองช่วงเวลาที่ผ่านมาแล้วได้']);
        }

        $startDb = $data['start_time'].':00';
        $endDb = $data['end_time'].':00';

        $result = DB::transaction(function () use ($coach, $date, $startDb, $endDb, $request, $data) {
            // เช็คว่าโค้ชติดงานที่แอดมินมาร์คไว้ (สถานะ "ไม่ว่าง") ในช่วงเวลานี้หรือไม่
            $isBusy = Availability::where('user_id', $coach->id)
                ->whereDate('date', $date)
                ->where('status', 'booked')
                ->where('start_time', '<', $endDb)
                ->where('end_time', '>', $startDb)
                ->exists();

            if ($isBusy) {
                return ['error' => 'ช่วงเวลานี้โค้ชติดภารกิจอื่นอยู่ ไม่สามารถจองได้'];
            }

            // เช็คว่ามีคนอื่นจองเทรนส่วนตัวช่วงนี้ไปก่อนแล้วหรือยัง (กันจองซ้ำ)
            $overlap = PrivateTrainingBooking::overlapping($coach->id, $date, $startDb, $endDb)
                ->lockForUpdate()
                ->exists();

            if ($overlap) {
                return ['error' => 'ช่วงเวลานี้มีการจองไปแล้ว กรุณาเลือกช่วงเวลาอื่น'];
            }

            $booking = PrivateTrainingBooking::create([
                'user_id' => $request->user()->id,
                'coach_id' => $coach->id,
                'date' => $date,
                'start_time' => $startDb,
                'end_time' => $endDb,
                'status' => 'pending',
                'note' => $data['note'] ?? null,
            ]);

            return ['booking' => $booking];
        });

        if (isset($result['error'])) {
            return back()->withErrors(['bookings' => $result['error']]);
        }

        $timeLabel = substr($data['start_time'], 0, 5).' - '.substr($data['end_time'], 0, 5);

        User::where('role', 'admin')->get()->each(function ($admin) use ($request, $coach, $date, $timeLabel) {
            Notification::create([
                'user_id' => $admin->id,
                'title' => 'คำขอจองเทรนเนอร์ส่วนตัวใหม่',
                'message' => "คุณ {$request->user()->name} ขอจองเทรนเนอร์ส่วนตัวกับโค้ช {$coach->name} |วันที่ {$date}\nเวลา {$timeLabel}",
            ]);
        });

        return back()->with('success', 'ส่งคำขอจองเทรนเนอร์ส่วนตัวเรียบร้อย รอแอดมินอนุมัติ');
    }

    /**
     * ยกเลิกคำขอจองเทรนเนอร์ส่วนตัว (เฉพาะรายการของตัวเอง และยังไม่ได้เริ่ม/ยังรออนุมัติ)
     */
    public function cancel(Request $request, PrivateTrainingBooking $privateTrainingBooking)
    {
        abort_unless($privateTrainingBooking->user_id === $request->user()->id, 403);

        if ($privateTrainingBooking->status !== 'pending') {
            return back()->withErrors(['status' => 'ไม่สามารถยกเลิกรายการนี้ได้ เนื่องจากได้รับการอนุมัติหรือดำเนินการไปแล้ว']);
        }

        if ($privateTrainingBooking->isStarted()) {
            return back()->withErrors(['status' => 'ไม่สามารถยกเลิกรายการที่ถึงเวลาแล้วได้']);
        }

        $privateTrainingBooking->update(['status' => 'cancelled']);

        return back()->with('success', 'ยกเลิกคำขอจองเรียบร้อย');
    }

    private function assertIsCoach(User $coach): void
    {
        abort_unless($coach->role === 'staff' && $coach->membership_type === 'coach', 404, 'ไม่พบข้อมูลโค้ช');
    }

    /* =====================================================================
     |  ส่วนของแอดมิน — จัดการอนุมัติ/ปฏิเสธคำขอจองเทรนเนอร์ส่วนตัว
     |  (รวมไว้ในคอนโทรลเลอร์เดียวกับฝั่ง user ตามแบบของ BookingController เดิม)
     ===================================================================== */

    /**
     * หน้าแอดมิน: รายการคำขอจองเทรนเนอร์ส่วนตัวทั้งหมด
     */
    public function adminIndex(Request $request)
    {
        $status = $request->query('status', 'pending');

        $bookings = PrivateTrainingBooking::with(['user', 'coach'])
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->paginate(15)
            ->withQueryString();

        return view('admin.private-training.index', compact('bookings', 'status'));
    }

    /**
     * แอดมิน: อนุมัติคำขอจอง
     */
    public function approve(PrivateTrainingBooking $privateTrainingBooking)
    {
        if ($privateTrainingBooking->status !== 'pending') {
            return back()->withErrors(['status' => 'รายการนี้ถูกดำเนินการไปแล้ว']);
        }

        $overlap = PrivateTrainingBooking::where('coach_id', $privateTrainingBooking->coach_id)
            ->whereDate('date', $privateTrainingBooking->date)
            ->where('status', 'approved')
            ->where('id', '!=', $privateTrainingBooking->id)
            ->where(function ($q) use ($privateTrainingBooking) {
                $q->where('start_time', '<', $privateTrainingBooking->end_time)
                    ->where('end_time', '>', $privateTrainingBooking->start_time);
            })
            ->exists();

        if ($overlap) {
            return back()->withErrors(['status' => 'ไม่สามารถอนุมัติได้ เนื่องจากมีรายการอื่นที่อนุมัติแล้วทับซ้อนช่วงเวลานี้']);
        }

        $privateTrainingBooking->update(['status' => 'approved']);

        $date = Carbon::parse($privateTrainingBooking->date)->toDateString();
        Notification::create([
            'user_id' => $privateTrainingBooking->user_id,
            'title' => 'การจองเทรนเนอร์ส่วนตัวได้รับการอนุมัติ',
            'message' => "การจองกับโค้ช {$privateTrainingBooking->coach->name} วันที่ {$date}\nเวลา ".substr($privateTrainingBooking->start_time, 0, 5).'-'.substr($privateTrainingBooking->end_time, 0, 5)."\nได้รับการอนุมัติแล้ว",
        ]);

        return back()->with('success', 'อนุมัติคำขอจองเรียบร้อย');
    }

    /**
     * แอดมิน: ปฏิเสธคำขอจอง
     */
    public function reject(Request $request, PrivateTrainingBooking $privateTrainingBooking)
    {
        $data = $request->validate([
            'reject_reason' => ['required', 'string', 'max:500'],
        ]);

        if ($privateTrainingBooking->status !== 'pending') {
            return back()->withErrors(['status' => 'รายการนี้ถูกดำเนินการไปแล้ว']);
        }

        $privateTrainingBooking->update([
            'status' => 'rejected',
            'reject_reason' => $data['reject_reason'],
        ]);

        $date = Carbon::parse($privateTrainingBooking->date)->toDateString();
        Notification::create([
            'user_id' => $privateTrainingBooking->user_id,
            'title' => 'การจองเทรนเนอร์ส่วนตัวถูกปฏิเสธ',
            'message' => "การจองกับโค้ช {$privateTrainingBooking->coach->name} |วันที่ {$date}\nเวลา ".substr($privateTrainingBooking->start_time, 0, 5).'-'.substr($privateTrainingBooking->end_time, 0, 5)."\nถูกปฏิเสธ — เหตุผล: {$data['reject_reason']}",
        ]);

        return back()->with('success', 'ปฏิเสธคำขอจองเรียบร้อย');
    }
}
