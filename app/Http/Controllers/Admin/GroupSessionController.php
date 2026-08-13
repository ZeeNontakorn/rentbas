<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupSession;
use App\Models\GroupRound;
use App\Models\GroupRoundSignup;
use App\Models\User;
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
        $data['is_active'] = true;

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
     * เปิด/ปิดใช้งานเทมเพลต (ไม่ลบ เผื่อมีรอบเก่าที่ผูกอยู่)
     */
    public function toggleSession(GroupSession $session): RedirectResponse
    {
        $session->update(['is_active' => !$session->is_active]);

        return back()->with('success', $session->is_active ? 'เปิดใช้งานเทมเพลตแล้ว' : 'ปิดใช้งานเทมเพลตแล้ว');
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
        $round->load(['court', 'session', 'signups.user', 'signups.addedBy']);

        return view('admin.group-sessions.round', compact('round'));
    }

    /**
     * แอดมินเพิ่มคนเข้ารอบด้วยตัวเอง (กรณีลูกค้ายังโอนเงิน/แจ้งผ่านไลน์อยู่)
     * ตัดเครดิตทันทีและลงลำดับตามเวลาที่กดตอนนี้
     */
    public function addPlayer(Request $request, GroupRound $round): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        return DB::transaction(function () use ($data, $round) {
            // ล็อกแถวรอบไว้กันสองคนกดเพิ่มพร้อมกันแล้วลำดับ/จำนวนชนกัน
            $round = GroupRound::where('id', $round->id)->lockForUpdate()->firstOrFail();

            if ($round->status !== 'open') {
                return back()->withErrors(['round' => 'รอบนี้ปิดรับสมัครแล้ว']);
            }

            if ($round->confirmedCount() >= $round->max_players) {
                return back()->withErrors(['round' => 'รอบนี้เต็มแล้ว']);
            }

            $alreadyIn = GroupRoundSignup::where('group_round_id', $round->id)
                ->where('user_id', $data['user_id'])
                ->where('status', 'confirmed')
                ->exists();

            if ($alreadyIn) {
                return back()->withErrors(['user_id' => 'สมาชิกคนนี้ลงชื่อในรอบนี้อยู่แล้ว']);
            }

            $user = User::lockForUpdate()->findOrFail($data['user_id']);

            if ($user->{self::CREDIT_COLUMN} < $round->credit_cost) {
                return back()->withErrors(['user_id' => "เครดิตของ {$user->name} ไม่พอ (มี {$user->{self::CREDIT_COLUMN}}, ต้องใช้ {$round->credit_cost})"]);
            }

            $user->decrement(self::CREDIT_COLUMN, $round->credit_cost);

            $nextOrder = ((int) GroupRoundSignup::where('group_round_id', $round->id)->max('order_number')) + 1;

            GroupRoundSignup::create([
                'group_round_id' => $round->id,
                'user_id' => $user->id,
                'order_number' => $nextOrder,
                'credit_used' => $round->credit_cost,
                'status' => 'confirmed',
                'signed_up_at' => now(),
                'added_by' => auth()->id(),
            ]);

            return back()->with('success', "เพิ่ม {$user->name} เป็นลำดับที่ {$nextOrder} แล้ว");
        });
    }

    /**
     * เอาคนออกจากรอบ + คืนเครดิตให้อัตโนมัติ
     * (ลำดับของคนที่เหลือจะไม่ถูกไล่ใหม่ เพื่อรักษาลำดับตามเวลาลงชื่อจริง)
     */
    public function removePlayer(GroupRound $round, GroupRoundSignup $signup): RedirectResponse
    {
        if ($signup->group_round_id !== $round->id) {
            abort(404);
        }

        return DB::transaction(function () use ($round, $signup) {
            $user = User::lockForUpdate()->findOrFail($signup->user_id);
            $user->increment(self::CREDIT_COLUMN, $signup->credit_used);

            $signup->update(['status' => 'cancelled']);

            return back()->with('success', "นำ {$user->name} ออกจากรอบและคืนเครดิตแล้ว");
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
     * ยกเลิกรอบทั้งหมด + คืนเครดิตให้ทุกคนที่ลงชื่อไว้
     */
    public function cancelRound(GroupRound $round): RedirectResponse
    {
        DB::transaction(function () use ($round) {
            $signups = GroupRoundSignup::where('group_round_id', $round->id)
                ->where('status', 'confirmed')
                ->get();

            foreach ($signups as $signup) {
                User::where('id', $signup->user_id)->increment(self::CREDIT_COLUMN, $signup->credit_used);
                $signup->update(['status' => 'cancelled']);
            }

            $round->update(['status' => 'cancelled']);
        });

        return redirect()
            ->route('admin.group-sessions.index')
            ->with('success', 'ยกเลิกรอบและคืนเครดิตให้ทุกคนแล้ว');
    }
}
