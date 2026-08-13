<?php

namespace App\Http\Controllers;

use App\Models\GroupRound;
use App\Models\GroupRoundSignup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GroupRoundSignupController extends Controller
{
    /** เครดิตในระบบเก็บในคอลัมน์ credit_balance (หน่วยสตางค์) */
    private const CREDIT_COLUMN = 'credit_balance';

    /** แสดงรอบกลุ่มเล่นบาสที่ผู้ใช้ลงชื่อไว้ พร้อมรายชื่อผู้เล่นในแต่ละรอบ */
    public function myBookings(Request $request)
    {
        $signups = GroupRoundSignup::query()
            ->with([
                'round.court',
                'round.confirmedSignups.user:id,name',
            ])
            ->where('user_id', $request->user()->id)
            ->where('status', 'confirmed')
            ->latest('signed_up_at')
            ->get();

        return view('group-rounds.my-bookings', compact('signups'));
    }

    /**
     * หน้าแสดงรายละเอียดและยืนยันการชำระเครดิตก่อนลงชื่อเข้ารอบ
     */
    public function checkout(GroupRound $round, Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()
                ->route('login')
                ->with('error', 'กรุณาเข้าสู่ระบบก่อนลงชื่อจอง');
        }

        if ($round->status !== 'open' || $round->play_date->isBefore(today())) {
            return redirect()->route('home')->withErrors(['round' => 'รอบนี้ปิดรับสมัครแล้ว']);
        }

        if ($round->isFull()) {
            return redirect()->route('home')->withErrors(['round' => 'รอบนี้เต็มแล้ว']);
        }

        $alreadyIn = GroupRoundSignup::query()
            ->where('group_round_id', $round->id)
            ->where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->first();

        if ($alreadyIn) {
            return redirect()->route('home')->with(
                'error',
                'คุณได้จองรอบนี้ไปแล้ว (ลำดับที่ '.$alreadyIn->order_number.')'
            );
        }

        return view('checkout.group-round', compact('round', 'user'));
    }

    /**
     * ยืนยันการชำระเครดิต แล้วจึงลงชื่อเข้ารอบ
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
                ->first();

            if ($alreadyIn) {
                // กรณี browser ส่งคำขอยืนยันซ้ำ: request แรกลงชื่อสำเร็จแล้ว
                // ให้ตอบผลสำเร็จเดิมแทนการแสดง error และไม่ตัดเครดิตซ้ำ
                return redirect()->route('home')->with(
                    'success',
                    'ชำระเงินสำเร็จ! คุณลงชื่อจองรอบนี้เป็นลำดับที่ '.$alreadyIn->order_number.' แล้ว'
                );
            }

            // ล็อกเครดิตผู้ใช้ เพื่อไม่ให้ยอดคลาดเคลื่อนเมื่อมีการใช้เครดิตพร้อมกัน
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($lockedUser->{self::CREDIT_COLUMN} < $round->credit_cost) {
                return back()->withErrors([
                    'round' => 'เครดิตของคุณไม่พอ (มี ฿'.number_format($lockedUser->{self::CREDIT_COLUMN} / 100, 2).', ต้องใช้ ฿'.number_format($round->credit_cost / 100, 2).')',
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

            return redirect()->route('home')->with(
                'success',
                'ชำระเงินสำเร็จ! คุณลงชื่อจองรอบนี้เป็นลำดับที่ '.$nextOrder
            );
        });
    }
}
