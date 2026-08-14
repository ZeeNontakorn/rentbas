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
    /** เครดิตในระบบเก็บในคอลัมน์ credit_balance (หน่วยสตางค์) ส่วน credit_cost ของรอบเป็นหน่วยบาท */
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

        // เช็คว่ารอบนี้หมดเวลาสละสิทธิ์แล้วหรือยัง ถ้าใช่ให้คืนเครดิตสำรองที่เหลือก่อน (ทำแบบ lazy ไม่ต้องตั้ง cron)
        $round->processExpiredReserves();

        if ($round->status !== 'open' || $round->play_date->isBefore(today())) {
            return redirect()->route('home')->withErrors(['round' => 'รอบนี้ปิดรับสมัครแล้ว']);
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

        // ไม่บล็อกแม้ตัวจริงเต็มแล้ว — ให้ไปหน้า checkout ได้ปกติ แต่จะลงชื่อเป็น "สำรอง" แทน
        $willBeReserve = $round->isFull();

        return view('checkout.group-round', compact('round', 'user', 'willBeReserve'));
    }

    /**
     * ยืนยันการชำระเครดิต แล้วจึงลงชื่อเข้ารอบ (เป็นตัวจริงหรือสำรอง ขึ้นอยู่กับว่าตัวจริงเต็มหรือยัง)
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
            // ล็อกแถวรอบไว้ เพื่อคำนวณว่าเป็นตัวจริงหรือสำรองให้ถูกต้องแม้มีคนกดพร้อมกัน
            $round = GroupRound::query()->lockForUpdate()->findOrFail($round->id);

            if ($round->status !== 'open') {
                return back()->withErrors(['round' => 'รอบนี้ปิดรับสมัครแล้ว']);
            }

            if ($round->play_date->isBefore(today())) {
                return back()->withErrors(['round' => 'รอบนี้ผ่านไปแล้ว']);
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

            if ($lockedUser->{self::CREDIT_COLUMN} < $round->credit_cost * 100) {
                return back()->withErrors([
                    'round' => 'เครดิตของคุณไม่พอ (มี ฿'.number_format($lockedUser->{self::CREDIT_COLUMN} / 100, 2).', ต้องใช้ ฿'.number_format($round->credit_cost, 2).')',
                ]);
            }

            // ตัวจริงเต็มหรือยัง ณ ตอนนี้ (ล็อกแถวไว้แล้วจึงเชื่อถือได้แม้มีคนกดพร้อมกัน) — ถ้าเต็มแล้วให้ลงเป็นสำรอง
            $isReserve = $round->mainConfirmedCount() >= $round->max_players;

            $lockedUser->decrement(self::CREDIT_COLUMN, $round->credit_cost * 100);

            $nextOrder = ((int) GroupRoundSignup::query()
                ->where('group_round_id', $round->id)
                ->max('order_number')) + 1;

            GroupRoundSignup::updateOrCreate(
    [
        'group_round_id' => $round->id,
        'user_id' => $lockedUser->id,
    ],
    [
        'guest_name' => null,
        'order_number' => $nextOrder,
        'credit_used' => $round->credit_cost,
        'status' => 'confirmed',
        'is_reserve' => $isReserve,
        'signed_up_at' => now(),
        'added_by' => null,
    ]
);

            $message = $isReserve
                ? 'ชำระเงินสำเร็จ! ตัวจริงเต็มแล้ว คุณอยู่ในคิวสำรองลำดับที่ '.$nextOrder.' ถ้ามีคนสละสิทธิ์ก่อนเดดไลน์ คุณจะได้เลื่อนเป็นตัวจริงอัตโนมัติ'
                : 'ชำระเงินสำเร็จ! คุณลงชื่อจองรอบนี้เป็นลำดับที่ '.$nextOrder;

            return redirect()->route('home')->with('success', $message);
        });
    }

    /**
     * สมาชิกยกเลิกจองตัวเอง + คืนเครดิตอัตโนมัติ (ทำได้ก่อนถึงเดดไลน์สละสิทธิ์ของรอบเท่านั้น)
     * ถ้าคนที่ยกเลิกเป็น "ตัวจริง" ระบบจะเลื่อนคิวสำรองคนแรกขึ้นมาแทนที่ให้อัตโนมัติ
     */
    public function cancel(GroupRound $round): RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login')->with('error', 'กรุณาเข้าสู่ระบบก่อน');
        }

        return DB::transaction(function () use ($round, $user) {
            $round = GroupRound::query()->lockForUpdate()->findOrFail($round->id);

            if (! $round->canSelfCancel()) {
                return back()->withErrors(['round' => 'เลยเวลาที่สามารถยกเลิกจองเองได้แล้ว']);
            }

            $signup = GroupRoundSignup::query()
                ->where('group_round_id', $round->id)
                ->where('user_id', $user->id)
                ->where('status', 'confirmed')
                ->lockForUpdate()
                ->first();

            if (! $signup) {
                return back()->withErrors(['round' => 'ไม่พบรายการจองของคุณในรอบนี้']);
            }

            $wasMainSlot = ! $signup->is_reserve;

            if ($signup->credit_used > 0) {
                User::where('id', $user->id)->increment(self::CREDIT_COLUMN, $signup->credit_used * 100);
            }

            $signup->update(['status' => 'cancelled']);

            if ($wasMainSlot) {
                $round->promoteNextReserve();
            }

            return redirect()->route('home')->with(
                'success',
                'ยกเลิกจองสำเร็จ คืนเครดิต ฿'.number_format($signup->credit_used, 2).' ให้แล้ว'
            );
        });
    }
}