<?php

namespace App\Http\Controllers;

use App\Models\GroupRound;
use App\Models\GroupRoundSignup;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\CreditService;


class GroupRoundSignupController extends Controller
{
    /** เครดิตในระบบเก็บในคอลัมน์ credit_balance (หน่วยสตางค์) ส่วน credit_cost ของรอบเป็นหน่วยบาท */
    private const CREDIT_COLUMN = 'credit_balance';
    public function __construct(protected CreditService $creditService)
    {
    }

    /**
     * แสดงรอบกลุ่มเล่นบาสทั้งหมดที่ผู้ใช้จองไว้ (รวมที่จองแทนเพื่อน) จัดกลุ่มตามรอบ
     */
    public function myBookings(Request $request)
    {
        $userId = $request->user()->id;

        $mine = GroupRoundSignup::query()
            ->with('round.court')
            ->where('status', 'confirmed')
            ->where(function ($q) use ($userId) {
                $q->where('booked_by', $userId)->orWhere('user_id', $userId);
            })
            ->orderBy('signed_up_at')
            ->get()
            ->groupBy('group_round_id');

        $bookings = $mine->map(function ($seats) {
            $round = $seats->first()->round;
            $round->load(['confirmedSignups.user', 'confirmedSignups.bookedBy']);

            return [
                'round' => $round,
                'my_seats' => $seats,
            ];
        })->sortByDesc(fn ($b) => $b['round']->play_date)->values();

        return view('group-rounds.my-bookings', compact('bookings'));
    }

    /**
     * หน้าแสดงรายละเอียดรอบ + ฟอร์มกรอกชื่อผู้เล่น (จองแทนเพื่อนได้ สูงสุด 5 คนต่อรอบ) ก่อนชำระเครดิต
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

        $bookedCount = $round->bookedSeatsFor($user->id);
        $remaining = $round->remainingSeatsFor($user->id);

        if ($remaining <= 0) {
            return redirect()->route('group-rounds.my-bookings')->with(
                'error',
                'คุณจองครบจำนวนสูงสุดแล้วในรอบนี้ ('.GroupRound::MAX_SEATS_PER_USER.' คน)'
            );
        }

        // ไม่บล็อกแม้ตัวจริงเต็มแล้ว — แค่เตือนไว้ล่วงหน้าว่าจะได้คิวสำรอง (เช็คจริงตอน submit อีกที)
        $willBeReserve = $round->isFull();

        return view('checkout.group-round', compact('round', 'user', 'willBeReserve', 'bookedCount', 'remaining'));
    }

    /**
     * ยืนยันการชำระเครดิต แล้วลงชื่อเข้ารอบทีเดียวหลายที่นั่ง (ตัวเอง + จองแทนเพื่อนได้ ตามชื่อที่กรอก)
     * แต่ละที่นั่งเป็นตัวจริงหรือสำรอง ขึ้นอยู่กับว่าตัวจริงเต็มไปถึงไหนแล้ว ณ ขณะจอง
     */
    public function store(Request $request, GroupRound $round): RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()
                ->route('login')
                ->with('error', 'กรุณาเข้าสู่ระบบก่อนลงชื่อจอง');
        }

        $data = $request->validate([
            'names' => ['required', 'array', 'min:1'],
            'names.*' => ['nullable', 'string', 'max:255'],
        ]);

        $names = array_values(array_filter(
            array_map('trim', $data['names']),
            fn ($name) => $name !== ''
        ));

        if (empty($names)) {
            return back()->withErrors(['names' => 'กรุณากรอกชื่อผู้เล่นอย่างน้อย 1 คน'])->withInput();
        }

        return DB::transaction(function () use ($round, $user, $names) {
            // ล็อกแถวรอบไว้ เพื่อคำนวณโควตา/ตัวจริง-สำรองให้ถูกต้องแม้มีคนกดพร้อมกัน
            $round = GroupRound::query()->lockForUpdate()->findOrFail($round->id);

            if ($round->status !== 'open') {
                return back()->withErrors(['round' => 'รอบนี้ปิดรับสมัครแล้ว']);
            }

            if ($round->play_date->isBefore(today())) {
                return back()->withErrors(['round' => 'รอบนี้ผ่านไปแล้ว']);
            }

            $remaining = $round->remainingSeatsFor($user->id);

            if (count($names) > $remaining) {
                return back()->withErrors([
                    'names' => $remaining > 0
                        ? "จองได้อีกแค่ {$remaining} ที่ (สูงสุด ".GroupRound::MAX_SEATS_PER_USER." คนต่อผู้ใช้ต่อรอบ)"
                        : 'คุณจองครบจำนวนสูงสุดแล้วในรอบนี้ ('.GroupRound::MAX_SEATS_PER_USER.' คน)',
                ])->withInput();
            }

            $totalCredit = $round->credit_cost * count($names);

            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($lockedUser->{self::CREDIT_COLUMN} < $totalCredit * 100) {
                return back()->withErrors([
                    'round' => 'เครดิตของคุณไม่พอ (มี ฿'.number_format($lockedUser->{self::CREDIT_COLUMN} / 100, 2).', ต้องใช้ ฿'.number_format($totalCredit, 2).')',
                ])->withInput();
            }

            $nextOrder = ((int) GroupRoundSignup::query()
    ->where('group_round_id', $round->id)
    ->max('order_number')) + 1;

$mainCount = $round->mainConfirmedCount();
$reserveCount = 0;

foreach ($names as $i => $name) {
    $isReserve = $mainCount >= $round->max_players;

    $signup = GroupRoundSignup::create([
        'group_round_id' => $round->id,
        'user_id' => null,
        'guest_name' => $name,
        'order_number' => $nextOrder + $i,
        'credit_used' => $round->credit_cost,
        'status' => 'confirmed',
        'is_reserve' => $isReserve,
        'signed_up_at' => now(),
        'added_by' => null,
        'booked_by' => $user->id,
    ]);

    // หักเครดิตผ่าน CreditService ทีละที่นั่ง เพื่อให้มีบันทึกใน credit_transactions
    // ผูกกับ signup แต่ละที่นั่งจริง (เดิมหักรวมก้อนเดียวตรงๆ ไม่มีประวัติเลย)
    $this->creditService->deductForGroupRound($lockedUser, $signup);

    if ($isReserve) {
        $reserveCount++;
    } else {
        $mainCount++;
    }
}

            // แจ้งเตือนผู้จอง (ตัวเอง) ว่าชำระเงินสำเร็จ
            Notification::create([
                'user_id' => $user->id,
                'title' => 'ชำระเงินจองกลุ่มเล่นบาสสำเร็จ',
                'message' => 'รอบ "'.$round->title.'" จองได้ '.count($names).' ที่'
                    .($reserveCount > 0
                        ? ' (ตัวจริง '.(count($names) - $reserveCount).' ที่ · คิวสำรอง '.$reserveCount.' ที่)'
                        : ' เป็นตัวจริงทั้งหมด')
                    .' ยอดชำระ ฿'.number_format($totalCredit, 2),
                'action_url' => route('group-rounds.my-bookings'),
                'is_read' => false,
            ]);

            // แจ้งเตือนแอดมินทุกคนว่ามีคนจองใหม่เข้ามา
            foreach (User::where('role', 'admin')->pluck('id') as $adminId) {
                Notification::create([
                    'user_id' => $adminId,
                    'title' => 'มีคนจองกลุ่มเล่นบาสใหม่',
                    'message' => $user->name.' จองรอบ "'.$round->title.'" จำนวน '.count($names).' ที่ ยอดชำระ ฿'.number_format($totalCredit, 2),
                    'action_url' => route('admin.group-sessions.rounds.show', $round->id),
                    'is_read' => false,
                ]);
            }

            $message = $reserveCount > 0
                ? 'ชำระเงินสำเร็จ! จองได้ '.count($names).' ที่ (เป็นคิวสำรอง '.$reserveCount.' ที่ ที่เหลือเป็นตัวจริง)'
                : 'ชำระเงินสำเร็จ! จองได้ '.count($names).' ที่เรียบร้อยแล้ว';

            return redirect()->route('group-rounds.my-bookings')->with('success', $message);
        });
    }

    /**
     * ยกเลิกที่นั่งใดที่นั่งหนึ่งของตัวเอง (รวมที่นั่งที่จองแทนเพื่อน) + คืนเครดิตอัตโนมัติ
     * ทำได้ก่อนถึงเดดไลน์สละสิทธิ์ของรอบเท่านั้น ถ้าที่นั่งที่ยกเลิกเป็น "ตัวจริง"
     * ระบบจะเลื่อนคิวสำรองคนแรกขึ้นมาแทนที่ให้อัตโนมัติ
     */
    public function cancel(GroupRound $round, GroupRoundSignup $signup): RedirectResponse
{
    $user = Auth::user();

    if (! $user) {
        return redirect()->route('login')->with('error', 'กรุณาเข้าสู่ระบบก่อน');
    }

    if ($signup->group_round_id !== $round->id) {
        abort(404);
    }

    // ยกเลิกได้เฉพาะที่นั่งที่ตัวเองเป็นคนจอง (บัญชีตัวเอง หรือที่นั่งที่ตัวเองจองแทนเพื่อน)
    $owns = $signup->booked_by === $user->id || $signup->user_id === $user->id;

    if (! $owns) {
        abort(403);
    }

    return DB::transaction(function () use ($round, $signup) {
        $round = GroupRound::query()->lockForUpdate()->findOrFail($round->id);

        if (! $round->canSelfCancel()) {
            return back()->withErrors(['round' => 'เลยเวลาที่สามารถยกเลิกจองเองได้แล้ว']);
        }

        $signup = GroupRoundSignup::query()->lockForUpdate()->findOrFail($signup->id);

        if ($signup->status !== 'confirmed') {
            return back()->withErrors(['round' => 'ที่นั่งนี้ถูกยกเลิกไปแล้ว']);
        }

        $wasMainSlot = ! $signup->is_reserve;
        $seatName = $signup->displayName();
        $removedOrder = $signup->order_number;

        $payerId = $signup->booked_by ?? $signup->user_id;

        if ($payerId && $signup->credit_used > 0) {
    // คืนเครดิตผ่าน CreditService เพื่อให้มีบันทึกใน credit_transactions
    $this->creditService->refundForGroupRound(
        $payerId,
        $signup,
        "ยกเลิกที่นั่งของ \"{$seatName}\" เอง ในรอบ \"{$round->title}\""
    );
}

        $signup->update(['status' => 'cancelled']);

        // เลื่อนลำดับคนที่อยู่หลังคนที่ยกเลิก ขึ้นมาแทนที่ทันที ไม่ให้เลขกระโดดข้าม
        GroupRoundSignup::where('group_round_id', $round->id)
            ->where('status', 'confirmed')
            ->where('order_number', '>', $removedOrder)
            ->decrement('order_number');

        if ($payerId) {
            Notification::create([
                'user_id' => $payerId,
                'title' => 'ยกเลิกการจองกลุ่มเล่นบาสสำเร็จ',
                'message' => 'ยกเลิกที่นั่งของ "'.$seatName.'" ในรอบ "'.$round->title.'" แล้ว คืนเครดิต ฿'.number_format($signup->credit_used, 2).' ให้เรียบร้อยแล้ว',
                'action_url' => route('group-rounds.my-bookings'),
                'is_read' => false,
            ]);
        }

        if ($wasMainSlot) {
            $round->promoteNextReserve();
        }

        return redirect()->route('group-rounds.my-bookings')->with(
            'success',
            'ยกเลิกที่นั่งของ '.$seatName.' สำเร็จ คืนเครดิต ฿'.number_format($signup->credit_used, 2).' ให้แล้ว'
        );
    });
}
}