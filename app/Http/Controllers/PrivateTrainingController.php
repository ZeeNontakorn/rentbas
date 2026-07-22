<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Models\Court;
use App\Models\Notification;
use App\Models\PrivateTrainingBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrivateTrainingController extends Controller
{
    /** ช่วงเวลาเปิดให้จองต่อวัน */
    private const OPEN_HOUR = 8;
    private const CLOSE_HOUR = 22;

    public function index(Request $request)
    {
        $search = $request->query('search');

        $coaches = User::where('role', 'staff')
            ->where('membership_type', 'coach')
            ->with('staffProfile')
            // ใช้ nested closure (fn($q) และ fn($qq)) เพื่อสร้างวงเล็บคลุมเงื่อนไข OR ป้องกันการคลาดเคลื่อนกับเงื่อนไขหลักด้านบน
            ->when($search, fn($q) => $q->where(
                fn($qq) => $qq->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->get();

        $myRequests = PrivateTrainingBooking::with('coach')
            ->where('user_id', $request->user()->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->orderByDesc('created_at')
            ->get();

        return view('private-training.index', compact('coaches', 'search', 'myRequests'));
    }

    public function show(User $coach)
    {
        $this->assertIsCoach($coach);

        $today = now()->toDateString();
        $now = now();

        $busyRanges = $coach->availabilities()
            ->whereDate('date', $today)
            ->where('status', 'booked')
            ->get(['start_time', 'end_time']);

        $reservedRanges = PrivateTrainingBooking::where('coach_id', $coach->id)
            ->whereDate('date', $today)
            ->whereIn('status', ['pending', 'approved'])
            ->get(['start_time', 'end_time', 'user_id']);

        $timeline = [];
        for ($h = self::OPEN_HOUR; $h < self::CLOSE_HOUR; $h++) {
            // sprintf('%02d') ใช้ฟอร์แมตตัวเลขให้มี 0 นำหน้าถ้าเป็นเลขหลักเดียว (เช่น 8 กลายเป็น '08')
            $start = sprintf('%02d:00:00', $h);
            $end = sprintf('%02d:00:00', $h + 1);
            $isPast = Carbon::parse("$today $start")->lte($now);

            // ใช้ collection methods:
            // ->first() คืนค่า object แรกที่ตรงเงื่อนไข (เพื่อเอาค่า user_id มาเช็คต่อ)
            // ->contains() คืนค่า boolean (true/false) ว่ามีข้อมูลที่ตรงเงื่อนไขใน collection หรือไม่
            $reserved = $reservedRanges->first(fn($r) => $r->start_time < $end && $r->end_time > $start);
            $isBusy = $busyRanges->contains(fn($r) => $r->start_time < $end && $r->end_time > $start);

            $status = 'available';
            if ($isPast) {
                $status = 'past';
            } elseif ($reserved) {
                $status = $reserved->user_id === auth()->id() ? 'mine' : 'reserved';
            } elseif ($isBusy) {
                $status = 'unavailable';
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

        // ใช้เครื่องหมาย + (Array Union) เพื่อนำ array ธรรมดาไปต่อท้ายผลลัพธ์ที่ได้จากฟังก์ชัน compact()
        return view('private-training.show', compact('coach', 'today', 'timeline', 'myUpcoming') + [
            'staffProfile' => $coach->staffProfile,
        ]);
    }

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

        $date = now()->toDateString();
        $openTime = sprintf('%02d:00', self::OPEN_HOUR);
        $closeTime = sprintf('%02d:00', self::CLOSE_HOUR);

        if ($data['start_time'] < $openTime || $data['end_time'] > $closeTime) {
            return back()->withErrors(['start_time' => "สามารถจองได้เฉพาะช่วง {$openTime}–{$closeTime} น. เท่านั้น"]);
        }

        if (Carbon::parse("{$date} {$data['start_time']}")->lte(now())) {
            return back()->withErrors(['start_time' => 'ไม่สามารถจองช่วงเวลาที่ผ่านมาแล้วได้']);
        }

        $startDb = $data['start_time'] . ':00';
        $endDb = $data['end_time'] . ':00';

        // ใช้ DB::transaction เพื่อทำ Atomic Operation ถ้ามีข้อผิดพลาดระบบจะ Rollback สิ่งที่ query ไว้ทั้งหมด ป้องกันข้อมูลขยะ
        $error = DB::transaction(function () use ($coach, $date, $startDb, $endDb, $request, $data) {
            $isBusy = Availability::where('user_id', $coach->id)
                ->whereDate('date', $date)
                ->where('status', 'booked')
                ->where('start_time', '<', $endDb)
                ->where('end_time', '>', $startDb)
                ->exists();

            if ($isBusy)
                return 'ช่วงเวลานี้โค้ชติดภารกิจอื่นอยู่ ไม่สามารถจองได้';

            // lockForUpdate() คือ Pessimistic Locking ล็อก row ในฐานข้อมูลจนกว่า Transaction จะจบ
            // ป้องกัน Race Condition กรณีที่มีผู้ใช้ 2 คนกดจองเวลาเดียวกันเป๊ะในระดับเสี้ยววินาที
            $overlap = PrivateTrainingBooking::overlapping($coach->id, $date, $startDb, $endDb)
                ->lockForUpdate()
                ->exists();

            if ($overlap)
                return 'ช่วงเวลานี้มีการจองไปแล้ว กรุณาเลือกช่วงเวลาอื่น';

            // array_merge รวม array ก้อนที่ส่งมาจาก Request เข้ากับข้อมูลที่ถูกบังคับใส่ (ป้องกันการถูก Inject ข้อมูลผิดๆ ผ่านฟอร์ม)
            PrivateTrainingBooking::create(array_merge($data, [
                'user_id' => $request->user()->id,
                'date' => $date,
                'start_time' => $startDb,
                'end_time' => $endDb,
                'status' => 'pending',
            ]));

            return null;
        });

        if ($error)
            return back()->withErrors(['bookings' => $error]);

        $timeLabel = $this->formatTimeRange($data['start_time'], $data['end_time']);
        $this->notifyAdmins('คำขอจองเทรนเนอร์ส่วนตัวใหม่', "คุณ {$request->user()->name} ขอจองเทรนเนอร์ส่วนตัวกับโค้ช {$coach->name} |วันที่ {$date}\nเวลา {$timeLabel}");

        return back()->with('success', 'ส่งคำขอจองเทรนเนอร์ส่วนตัวเรียบร้อย รอแอดมินอนุมัติ');
    }

    public function cancel(Request $request, PrivateTrainingBooking $privateTrainingBooking)
    {
        abort_unless($privateTrainingBooking->user_id === $request->user()->id, 403);

        if ($error = $this->checkIfNotPending($privateTrainingBooking))
            return $error;

        if ($privateTrainingBooking->isStarted()) {
            return back()->withErrors(['status' => 'ไม่สามารถยกเลิกรายการที่ถึงเวลาแล้วได้']);
        }

        $privateTrainingBooking->update(['status' => 'cancelled']);
        return back()->with('success', 'ยกเลิกคำขอจองเรียบร้อย');
    }

    /* =====================================================================
     |  ADMIN SECTION
     ===================================================================== */
    public function adminIndex(Request $request)
    {
        $status = $request->query('status', 'pending');

        $bookings = PrivateTrainingBooking::with(['user', 'coach'])
            ->when($status && $status !== 'all', fn($q) => $q->where('status', $status))
            ->oldest()// oldest() มีค่าเท่ากับ orderBy('created_at', 'asc') คือคิวที่มาก่อนจะอยู่บนสุด (First In, First Out)
            ->paginate(15)
            // withQueryString() ทำให้การกดเปลี่ยนหน้า (pagination) ยังจำ parameter เดิมใน URL ไว้ (เช่น ค่า search, status)
            ->withQueryString();

        return view('admin.private-training.index', compact('bookings', 'status'));
    }
    public function approve(PrivateTrainingBooking $privateTrainingBooking)
    {
        if ($error = $this->checkIfNotPending($privateTrainingBooking))
            return $error;

        // overlapping() เป็น Local Scope ที่นิยามไว้ในโมเดล ช่วยลดความซ้ำซ้อนของการเขียน Query หาเวลาที่ทับซ้อน
        $overlap = PrivateTrainingBooking::overlapping(
            $privateTrainingBooking->coach_id,
            $privateTrainingBooking->date,
            $privateTrainingBooking->start_time,
            $privateTrainingBooking->end_time
        )
            ->where('status', 'approved')
            ->where('id', '!=', $privateTrainingBooking->id)
            ->exists();

        if ($overlap) {
            return back()->withErrors(['status' => 'ไม่สามารถอนุมัติได้ เนื่องจากมีรายการอื่นที่อนุมัติแล้วทับซ้อนช่วงเวลานี้']);
        }

        $privateTrainingBooking->update(['status' => 'approved']);
        $this->markCoachAvailabilityAsBooked($privateTrainingBooking);

        $timeLabel = $this->formatTimeRange($privateTrainingBooking->start_time, $privateTrainingBooking->end_time);
        $this->notifyUser(
            $privateTrainingBooking->user_id,
            'การจองเทรนเนอร์ส่วนตัวได้รับการอนุมัติ',
            "การจองกับโค้ช {$privateTrainingBooking->coach->name} วันที่ {$privateTrainingBooking->date}\nเวลา {$timeLabel}\nได้รับการอนุมัติแล้ว"
        );

        return back()->with('success', 'อนุมัติคำขอจองเรียบร้อย');
    }

    public function reject(Request $request, PrivateTrainingBooking $privateTrainingBooking)
    {
        if ($error = $this->checkIfNotPending($privateTrainingBooking))
            return $error;

        $data = $request->validate(['reject_reason' => ['required', 'string', 'max:500']]);

        $privateTrainingBooking->update([
            'status' => 'rejected',
            'reject_reason' => $data['reject_reason'],
        ]);

        $timeLabel = $this->formatTimeRange($privateTrainingBooking->start_time, $privateTrainingBooking->end_time);
        $this->notifyUser(
            $privateTrainingBooking->user_id,
            'การจองเทรนเนอร์ส่วนตัวถูกปฏิเสธ',
            "การจองกับโค้ช {$privateTrainingBooking->coach->name} |วันที่ {$privateTrainingBooking->date}\nเวลา {$timeLabel}\nถูกปฏิเสธ — เหตุผล: {$data['reject_reason']}"
        );

        return back()->with('success', 'ปฏิเสธคำขอจองเรียบร้อย');
    }

    /* =====================================================================
     |  PRIVATE HELPERS
     ===================================================================== */

    private function markCoachAvailabilityAsBooked(PrivateTrainingBooking $booking): void
    {
        $startHour = Carbon::parse($booking->start_time)->hour;
        $endHour = Carbon::parse($booking->end_time)->hour;
        $detail = 'เทรนเนอร์ส่วนตัว: ' . ($booking->user->name ?? 'ลูกค้า');
        $courts = Court::all();

        for ($h = $startHour; $h < $endHour; $h++) {
            $slotStart = sprintf('%02d:00:00', $h);
            $slotEnd = sprintf('%02d:00:00', $h + 1);

            foreach ($courts as $court) {
                // updateOrCreate(array $attributes, array $values) 
                // ค้นหา record ด้วย $attributes (ก้อนแรก) ถ้าเจอจะอัปเดตด้วย $values (ก้อนสอง) ถ้าไม่เจอจะ insert ใหม่
                Availability::updateOrCreate(
                    [
                        'user_id' => $booking->coach_id,
                        'date' => $booking->date,
                        'start_time' => $slotStart,
                        'end_time' => $slotEnd,
                        'court_id' => $court->id,
                    ],
                    ['status' => 'booked', 'detail' => $detail]
                );
            }
        }
    }

    private function assertIsCoach(User $coach): void
    {
        abort_unless($coach->role === 'staff' && $coach->membership_type === 'coach', 404, 'ไม่พบข้อมูลโค้ช');
    }

    private function checkIfNotPending(PrivateTrainingBooking $booking)
    {
        return $booking->status !== 'pending'
            ? back()->withErrors(['status' => 'รายการนี้ถูกดำเนินการไปแล้ว หรือไม่สามารถแก้ไขได้'])
            : null;
    }

    private function formatTimeRange(string $start, string $end): string
    {
        // ตัดข้อความตำแหน่งที่ 0 จำนวน 5 ตัวอักษร เพื่อเอาแค่วินาทีออก (จาก '08:00:00' เป็น '08:00')
        return substr($start, 0, 5) . '-' . substr($end, 0, 5);
    }

    private function notifyUser(int $userId, string $title, string $message): void
    {
        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
        ]);
    }

    private function notifyAdmins(string $title, string $message): void
    {
        // pluck('id') จะดึงมาเฉพาะค่า id ในรูปแบบ Flat Array (เช่น [1, 2, 3]) ซึ่งประหยัดหน่วยความจำกว่าการโหลดมาทั้ง Model
        $admins = User::where('role', 'admin')->pluck('id');
        foreach ($admins as $adminId) {
            $this->notifyUser($adminId, $title, $message);
        }
    }
}