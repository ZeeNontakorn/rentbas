<?php

namespace App\Http\Controllers;

use App\Models\GroupRound;
use App\Models\GroupRoundSignup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GroupRoundSignupController extends Controller
{
    /** เครดิตในระบบเก็บในคอลัมน์ credit_balance (หน่วยสตางค์) */
    private const CREDIT_COLUMN = 'credit_balance';

    /**
     * ให้ลูกค้าลงชื่อเข้ารอบและตัดเครดิตทันที
     */
    public function store(GroupRound $round): RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()
                ->route('login')
                ->with('error', 'กรุณาเข้าสู่ระบบก่อนลงชื่อจอง');
        }

        return DB::transaction(function () use ($round, $user) {
            // ล็อกแถวรอบไว้ เพื่อไม่ให้มีผู้ลงชื่อเกินจำนวนที่รับ
            $round = GroupRound::query()->lockForUpdate()->findOrFail($round->id);

            if ($round->status !== 'open') {
                return back()->withErrors(['round' => 'รอบนี้ปิดรับสมัครแล้ว']);
            }

            if ($round->play_date->isBefore(today())) {
                return back()->withErrors(['round' => 'รอบนี้ผ่านไปแล้ว']);
            }

            if ($round->confirmedCount() >= $round->max_players) {
                return back()->withErrors(['round' => 'รอบนี้เต็มแล้ว']);
            }

            $alreadyIn = GroupRoundSignup::query()
                ->where('group_round_id', $round->id)
                ->where('user_id', $user->id)
                ->where('status', 'confirmed')
                ->exists();

            if ($alreadyIn) {
                return back()->withErrors(['round' => 'คุณลงชื่อในรอบนี้ไว้แล้ว']);
            }

            // ล็อกเครดิตผู้ใช้ เพื่อไม่ให้ยอดคลาดเคลื่อนเมื่อมีการใช้เครดิตพร้อมกัน
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($lockedUser->{self::CREDIT_COLUMN} < $round->credit_cost) {
                return back()->withErrors([
                    'round' => 'เครดิตของคุณไม่พอ (มี '.$lockedUser->{self::CREDIT_COLUMN}.', ต้องใช้ '.$round->credit_cost.')',
                ]);
            }

            $lockedUser->decrement(self::CREDIT_COLUMN, $round->credit_cost);

            $nextOrder = ((int) GroupRoundSignup::query()
                ->where('group_round_id', $round->id)
                ->max('order_number')) + 1;

            GroupRoundSignup::create([
                'group_round_id' => $round->id,
                'user_id' => $lockedUser->id,
                'order_number' => $nextOrder,
                'credit_used' => $round->credit_cost,
                'status' => 'confirmed',
                'signed_up_at' => now(),
                'added_by' => null,
            ]);

            return back()->with('success', 'ลงชื่อจองรอบเรียบร้อยแล้ว! คุณอยู่ลำดับที่ '.$nextOrder);
        });
    }
}
