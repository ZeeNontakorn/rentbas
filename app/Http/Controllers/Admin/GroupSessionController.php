<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupSession;
use App\Models\GroupRound;
use App\Models\GroupRoundSignup;
use App\Models\Notification;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GroupSessionController extends Controller
{
    /**
     * เครดิตของระบบเก็บใน users.credit_balance (หน่วยสตางค์)
     */
    private const CREDIT_COLUMN = 'credit_balance';

    public function __construct(protected CreditService $creditService)
    {
    }

    /**
     * หน้า Admin หลัก: แสดงเทมเพลตรอบประจำ + รายการรอบที่เปิดอยู่/จะถึง
     */
    public function index()
    {
        $sessions = GroupSession::with('court')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $upcomingRounds = GroupRound::with(['court', 'session'])
            ->withCount(['confirmedSignups as players_count'])
            ->whereIn('status', ['open', 'closed'])
            ->where('play_date', '>=', Carbon::today())
            ->orderBy('play_date')
            ->orderBy('start_time')
            ->get();

        $courts = \App\Models\Court::orderBy('name')->get();

        return view('admin.group-sessions.index', compact('sessions', 'upcomingRounds', 'courts'));
    }

    /**
     * สร้างเทมเพลตรอบประจำใหม่ เช่น "กลุ่มเล่นบาสค่ำ ทุกวันอังคาร 18:00-20:00"
     */
    public function storeSession(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'court_id' => ['nullable', 'exists:courts,id'],
            'max_players' => ['required', 'integer', 'min:1', 'max:100'],
            'credit_cost' => ['required', 'integer', 'min:0'],
        ], [
            'end_time.after' => 'เวลาเลิกต้องอยู่หลังเวลาเริ่ม',
        ]);

        $data['created_by'] = auth()->id();

        GroupSession::create($data);

        return back()->with('success', 'สร้างเทมเพลตรอบประจำเรียบร้อยแล้ว');
    }

    /**
     * แก้ไขเทมเพลตรอบประจำที่มีอยู่แล้ว
     */
    public function updateSession(Request $request, GroupSession $session): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'court_id' => ['nullable', 'exists:courts,id'],
            'max_players' => ['required', 'integer', 'min:1', 'max:100'],
            'credit_cost' => ['required', 'integer', 'min:0'],
        ], [
            'end_time.after' => 'เวลาเลิกต้องอยู่หลังเวลาเริ่ม',
        ]);

        $session->update($data);

        return back()->with('success', 'แก้ไขเทมเพลตรอบประจำเรียบร้อยแล้ว');
    }

    /**
     * ลบเทมเพลตรอบประจำทิ้ง (รอบที่เคยเปิดจากเทมเพลตนี้จะไม่หายไปด้วย)
     */
    public function destroySession(GroupSession $session): RedirectResponse
    {
        $session->delete();

        return back()->with('success', 'ลบเทมเพลตรอบประจำเรียบร้อยแล้ว');
    }

    /**
     * เปิดรอบจริงจากเทมเพลต (เลือกวันที่ที่จะเล่น) หรือเปิดรอบแบบ one-time โดยไม่ผูกเทมเพลตก็ได้
     */
    public function openRound(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'group_session_id' => ['nullable', 'exists:group_sessions,id'],
            'title' => ['required', 'string', 'max:255'],
            'play_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'court_id' => ['nullable', 'exists:courts,id'],
            'max_players' => ['required', 'integer', 'min:1', 'max:100'],
            'credit_cost' => ['required', 'integer', 'min:0'],
            'cancel_deadline' => ['nullable', 'date'],
        ], [
            'end_time.after' => 'เวลาเลิกต้องอยู่หลังเวลาเริ่ม',
            'play_date.after_or_equal' => 'วันที่เล่นต้องไม่ใช่วันที่ผ่านมาแล้ว',
        ]);

        // กันเปิดรอบซ้ำ วัน+เวลา+สนามเดียวกัน
        $duplicate = GroupRound::where('play_date', $data['play_date'])
            ->where('start_time', $data['start_time'])
            ->where('court_id', $data['court_id'] ?? null)
            ->whereIn('status', ['open', 'closed'])
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['play_date' => 'มีรอบที่เปิดอยู่แล้วในวัน เวลา และสนามนี้'])->withInput();
        }

        $data['created_by'] = auth()->id();
        $data['status'] = 'open';

        $round = GroupRound::create($data);

        return redirect()
            ->route('admin.group-sessions.rounds.show', $round)
            ->with('success', 'เปิดรอบเรียบร้อยแล้ว');
    }

    /**
     * แสดงรายละเอียดรอบ + รายชื่อคนลงเล่น เรียงลำดับ 1-25 ตามเวลาจริง
     */
    public function showRound(GroupRound $round)
    {
        // เช็คว่ารอบนี้หมดเวลาสละสิทธิ์แล้วหรือยัง ถ้าใช่ให้คืนเครดิตสำรองที่เหลือก่อนแสดงผล
        $round->processExpiredReserves();

        $round->load(['court', 'session', 'signups.user', 'signups.addedBy']);

        // รายชื่อสมาชิกที่ยังไม่มีรายการ "ที่ยังไม่ถูกยกเลิก" ในรอบนี้ — ใช้ status='confirmed' เท่านั้น
        // (เดิมใช้ $round->signups->pluck('user_id') ซึ่งดึงมาทุกสถานะ ทำให้คนที่เคยถูก "นำออก"
        // ยังคงถูกกันออกจากรายชื่อค้นหาต่อไปเรื่อยๆ ทั้งที่จริงๆ ไม่ได้อยู่ในรอบแล้ว)
        $members = User::query()
            ->whereNotIn(
                'id',
                $round->signups()->where('status', 'confirmed')->pluck('user_id')->filter()
            )
            ->orderBy('us_name')
            ->get(['id', 'us_name', 'email', 'phone']);

        return view('admin.group-sessions.round', compact('round', 'members'));
    }

    /**
     * แอดมินเพิ่มคนเข้ารอบด้วยตัวเอง (กรณีลูกค้ายังโอนเงิน/แจ้งผ่านไลน์อยู่)
     * ตัดเครดิตทันทีและลงลำดับตามเวลาที่กดตอนนี้ ไม่บล็อกแม้ตัวจริงเต็มแล้ว — เพิ่มเป็นคิวสำรองแทน
     * ยกเว้นกรณี "เต็มจำนวนตัวจริง + หมดเวลาสละสิทธิ์แล้ว" ซึ่งจะปิดรับเพิ่มคนโดยสิ้นเชิง
     * (กฎเดียวกับฝั่งสมาชิกจองเอง ดู GroupRoundSignupController::store/checkout)
     */
    public function addPlayer(Request $request, GroupRound $round): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id', 'required_without:guest_name'],
            'guest_name' => ['nullable', 'string', 'max:255', 'required_without:user_id'],
        ]);

        return DB::transaction(function () use ($data, $round) {
            // ล็อกแถวรอบไว้กันสองคนกดเพิ่มพร้อมกันแล้วลำดับ/จำนวนชนกัน
            $round = GroupRound::where('id', $round->id)->lockForUpdate()->firstOrFail();

            if ($round->status !== 'open') {
                return back()->withErrors(['round' => 'รอบนี้ปิดรับสมัครแล้ว']);
            }

            // เต็มจำนวนตัวจริง + หมดเวลาสละสิทธิ์แล้ว -> ปิดรับเพิ่มคนโดยสิ้นเชิง (แม้แต่แอดมินก็เพิ่มไม่ได้)
            if ($round->reservationsClosed() && $round->isFull()) {
            return back()->withErrors([
                'round' => 'รอบนี้เต็มจำนวนตัวจริงแล้ว และหมดเวลารับคิวสำรองแล้ว (รอบเล่นจบแล้ว/หมดเวลายกเลิก) ไม่สามารถเพิ่มคนเข้ารอบได้',
            ]);
        }

            // ไม่บล็อกแม้ตัวจริงเต็มแล้ว — ให้เพิ่มเป็นคิวสำรองแทน
            $isReserve = $round->mainConfirmedCount() >= $round->max_players;

            $user = null;
            $guestName = null;
            $creditUsed = 0;

            if (! empty($data['user_id'])) {
                $alreadyIn = GroupRoundSignup::where('group_round_id', $round->id)
                    ->where('user_id', $data['user_id'])
                    ->where('status', 'confirmed')
                    ->exists();

                if ($alreadyIn) {
                    return back()->withErrors(['user_id' => 'สมาชิกคนนี้ลงชื่อในรอบนี้อยู่แล้ว']);
                }

                $user = User::lockForUpdate()->findOrFail($data['user_id']);

                if ($user->{self::CREDIT_COLUMN} < $round->credit_cost * 100) {
                    return back()->withErrors(['user_id' => "เครดิตของ {$user->us_name} ไม่พอ (มี ฿".number_format($user->{self::CREDIT_COLUMN} / 100, 2).", ต้องใช้ ฿".number_format($round->credit_cost, 2).")"]);
                }

                // หมายเหตุ: ยังไม่หักเครดิตตรงนี้ — จะหักผ่าน CreditService หลังสร้าง signup สำเร็จ
                // ด้านล่าง เพื่อให้มี $signup->id ผูกกับ credit_transactions ไว้เป็นหลักฐาน
                $creditUsed = $round->credit_cost;
            } else {
                $guestName = $data['guest_name'];
            }

            $nextOrder = ((int) GroupRoundSignup::where('group_round_id', $round->id)->max('order_number')) + 1;

            if ($user) {
                // เดิมจุดนี้ไม่ได้เซ็ต is_reserve เลย ทำให้เพิ่มสมาชิกตอนตัวจริงเต็มแล้วผิดเป็นตัวจริงเสมอ — แก้แล้ว
                $signup = GroupRoundSignup::updateOrCreate(
                    [
                        'group_round_id' => $round->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'guest_name' => null,
                        'order_number' => $nextOrder,
                        'credit_used' => $creditUsed,
                        'status' => 'confirmed',
                        'is_reserve' => $isReserve,
                        'signed_up_at' => now(),
                        'added_by' => auth()->id(),
                    ]
                );

                // หักเครดิตผ่าน CreditService เพื่อให้มีบันทึกใน credit_transactions
                // (เดิมใช้ $user->decrement() ตรงๆ ทำให้ไม่มีประวัติธุรกรรมเลย)
                if ($creditUsed > 0) {
                    $this->creditService->deductForGroupRound($user, $signup);
                }
            } else {
                GroupRoundSignup::create([
                    'group_round_id' => $round->id,
                    'user_id' => null,
                    'guest_name' => $guestName,
                    'order_number' => $nextOrder,
                    'credit_used' => $creditUsed,
                    'status' => 'confirmed',
                    'is_reserve' => $isReserve,
                    'signed_up_at' => now(),
                    'added_by' => auth()->id(),
                ]);
            }

            return back()->with('success', 'เพิ่ม '.($user?->us_name ?? $guestName)." เป็นลำดับที่ {$nextOrder} แล้ว".($isReserve ? ' (คิวสำรอง)' : ''));
        });
    }

    /**
     * เอาคนออกจากรอบ + คืนเครดิตให้อัตโนมัติ + แจ้งเตือนเจ้าของที่นั่ง
     * (ลำดับของคนที่เหลือจะไม่ถูกไล่ใหม่ เพื่อรักษาลำดับตามเวลาลงชื่อจริง)
     */
    public function removePlayer(GroupRound $round, GroupRoundSignup $signup)
    {
        return DB::transaction(function () use ($round, $signup) {
            $round = GroupRound::query()->lockForUpdate()->findOrFail($round->id);
            $signup = GroupRoundSignup::query()->lockForUpdate()->findOrFail($signup->id);

            if ($signup->status !== 'confirmed') {
                return back()->withErrors(['round' => 'ที่นั่งนี้ถูกยกเลิกไปแล้ว']);
            }

            $wasMainSlot = ! $signup->is_reserve;
            $removedOrder = $signup->order_number;
            $seatName = $signup->displayName();

            // ผู้จ่ายเงินจริง: booked_by (จองแทนเพื่อนแบบ self-service, user_id ของที่นั่งจะเป็น null เสมอ)
            // หรือ user_id (แอดมินเพิ่มโดยผูกบัญชีสมาชิกโดยตรง) — เดิมจุดนี้ไม่มีโค้ดคืนเครดิตเลย มีแค่คอมเมนต์
            // ค้างไว้ ทำให้หักเงินตอนเพิ่ม/ลงชื่อไปแล้ว แต่พอแอดมินเอาออกไม่มีการคืนเงินและไม่มี transaction คืนเงิน
            // payerId() ใช้ is_null() เช็คแทน ?? หรือ truthy ตรงๆ เพราะระบบนี้มี user id=0 จริง (ดูรายละเอียด
            // ในคอมเมนต์ของ GroupRoundSignup::payerId())
            $payerId = $signup->payerId();
            $didRefund = false;

            if ($payerId !== null && $signup->credit_used > 0) {
                // คืนเครดิตผ่าน CreditService เพื่อให้มีบันทึกใน credit_transactions
                $this->creditService->refundForGroupRound(
                    $payerId,
                    $signup,
                    "ถูกนำออกจากรอบ \"{$round->title}\" โดยแอดมิน"
                );
                $didRefund = true;
            }

            $signup->update(['status' => 'cancelled']);

            if ($payerId !== null) {
                Notification::create([
                    'user_id' => $payerId,
                    'title' => 'ถูกนำออกจากรอบกลุ่มเล่นบาส',
                    'message' => 'ที่นั่งของ "'.$seatName.'" ในรอบ "'.$round->title.'" ถูกนำออกโดยแอดมิน'
                        .($didRefund ? ' ระบบคืนเครดิต ฿'.number_format($signup->credit_used, 2).' ให้เรียบร้อยแล้ว' : ''),
                    'action_url' => route('group-rounds.my-bookings'),
                    'is_read' => false,
                ]);
            }

            // เพิ่มส่วนนี้เข้าไป — เลื่อนลำดับคนที่อยู่หลังขึ้นมาแทนที่ ไม่ให้เลขกระโดดข้าม
            GroupRoundSignup::where('group_round_id', $round->id)
                ->where('status', 'confirmed')
                ->where('order_number', '>', $removedOrder)
                ->decrement('order_number');

            if ($wasMainSlot) {
                $round->promoteNextReserve();
            }

            return back()->with('success', 'นำผู้เล่นออกจากรอบเรียบร้อยแล้ว');
        });
    }

    /**
     * ปิดรับสมัครรอบ (ยังเล่นได้ตามปกติ แค่ไม่รับลงชื่อเพิ่ม)
     */
    public function closeRound(GroupRound $round): RedirectResponse
    {
        $round->update(['status' => 'closed']);

        return back()->with('success', 'ปิดรับสมัครรอบนี้แล้ว');
    }

    public function reopenRound(GroupRound $round): RedirectResponse
    {
        $round->update(['status' => 'open']);

        return back()->with('success', 'เปิดรับสมัครรอบนี้อีกครั้งแล้ว');
    }

    /**
     * ยกเลิกรอบทั้งหมด + คืนเครดิตให้ทุกคนที่ลงชื่อไว้ + แจ้งเตือนทุกคน
     */
    public function cancelRound(GroupRound $round): RedirectResponse
    {
        DB::transaction(function () use ($round) {
            $signups = GroupRoundSignup::where('group_round_id', $round->id)
                ->where('status', 'confirmed')
                ->get();

            foreach ($signups as $signup) {
                // payerId() ใช้ is_null() เช็คแทน ?? หรือ truthy ตรงๆ เพราะระบบนี้มี user id=0 จริง
                // (ดูรายละเอียดในคอมเมนต์ของ GroupRoundSignup::payerId())
                $payerId = $signup->payerId();
                $didRefund = false;

                if ($payerId !== null && $signup->credit_used > 0) {
                    // คืนเครดิตผ่าน CreditService เพื่อให้มีบันทึกใน credit_transactions
                    $this->creditService->refundForGroupRound(
                        $payerId,
                        $signup,
                        "ยกเลิกทั้งรอบ \"{$round->title}\" โดยแอดมิน"
                    );
                    $didRefund = true;
                }

                $signup->update(['status' => 'cancelled']);

                // แจ้งเตือนผู้ใช้ (เดิมไม่มีการแจ้งเตือนเลยตอนยกเลิกทั้งรอบ)
                if ($payerId !== null) {
                    Notification::create([
                        'user_id' => $payerId,
                        'title' => 'รอบเล่นบาสถูกยกเลิก',
                        'message' => 'ที่นั่งของ "'.$signup->displayName().'" ในรอบ "'.$round->title.'" ถูกยกเลิกทั้งรอบโดยแอดมิน'
                            .($didRefund ? ' ระบบคืนเครดิต ฿'.number_format($signup->credit_used, 2).' ให้เรียบร้อยแล้ว' : ''),
                        'action_url' => route('group-rounds.my-bookings'),
                        'is_read' => false,
                    ]);
                }
            }

            $round->update(['status' => 'cancelled']);
        });

        return redirect()
            ->route('admin.group-sessions.index')
            ->with('success', 'ยกเลิกรอบและคืนเครดิตให้ทุกคนแล้ว');
    }

    /**
     * ประวัติรอบที่ผ่านมาแล้ว/ปิดรับสมัครแล้ว พร้อมค้นหาตามชื่อ/วันที่
     */
    public function history(Request $request)
    {
        $search = $request->input('search');
        $date = $request->input('date');

        $pastRounds = GroupRound::with('court')
            ->where(function ($q) {
                $q->where('play_date', '<', now()->toDateString())
                  ->orWhere('status', 'closed');
            })
            ->when($search, fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->when($date, fn ($q) => $q->whereDate('play_date', $date))
            ->withCount(['confirmedSignups as players_count'])
            ->orderByDesc('play_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.group-sessions.history', compact('pastRounds', 'search', 'date'));
    }

    public function showRoundHistory(GroupRound $round)
    {
        $round->load(['court', 'confirmedSignups.user', 'confirmedSignups.addedBy', 'confirmedSignups.bookedBy']);

        return view('admin.group-sessions.history-show', compact('round'));
    }
}