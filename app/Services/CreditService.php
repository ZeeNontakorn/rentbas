<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CreditTransaction;
use App\Models\PrivateTrainingBooking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreditService
{
    /**
     * แอดมินเติมเครดิตให้ผู้ใช้ (Admin Top-up)
     */
    public function topup(User $user, int $amountSatang, User $admin, ?string $note = null): CreditTransaction
    {
        if ($amountSatang <= 0) {
            throw new RuntimeException('จำนวนเงินต้องมากกว่า 0');
        }

        return DB::transaction(function () use ($user, $amountSatang, $admin, $note) {
            // lockForUpdate กันแอดมิน 2 คนเติมพร้อมกันแล้วยอดเพี้ยน (race condition)
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();
            $lockedUser->increment('credit_balance', $amountSatang);

            return CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'topup',
                'amount' => $amountSatang,
                'balance_after' => $lockedUser->fresh()->credit_balance,
                'admin_id' => $admin->id,
                'note' => $note,
            ]);
        });
    }

    /**
     * หักเครดิตเพื่อจ่ายค่าจอง — ต้องเรียกภายใน DB::transaction ของ flow checkout อยู่แล้ว
     * (ดู BookingCheckoutService::payWithCredit) เพื่อให้การล็อกแถว user และการอัปเดต booking
     * อยู่ใน transaction เดียวกัน ป้องกันเงินถูกหักแต่การจองไม่สำเร็จ
     */
    public function deductForBooking(User $user, Booking $booking): CreditTransaction
    {
        // lockForUpdate: ล็อกแถวผู้ใช้ไว้จนกว่า transaction จะจบ กันกรณีผู้ใช้เปิดหลายแท็บ
        // แล้วพยายามจ่ายด้วยเครดิตก้อนเดียวกันพร้อมกัน (double-spend)
        $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();

        if ($lockedUser->credit_balance < $booking->price) {
            throw new RuntimeException('ยอดเครดิตไม่เพียงพอ');
        }

        $lockedUser->decrement('credit_balance', $booking->price);

        return CreditTransaction::create([
            'user_id' => $lockedUser->id,
            'type' => 'deduct',
            'amount' => $booking->price,
            'balance_after' => $lockedUser->fresh()->credit_balance,
            'booking_id' => $booking->id,
            'note' => "ชำระค่าจอง #{$booking->id}",
        ]);
    }

    /**
     * คืนเครดิต กรณีต้องยกเลิก booking ที่หักเงินไปแล้ว (เช่น แอดมินยกเลิกย้อนหลัง)
     */
    public function refund(Booking $booking, ?string $note = null): CreditTransaction
    {
        return DB::transaction(function () use ($booking, $note) {
            $lockedUser = User::whereKey($booking->user_id)->lockForUpdate()->first();
            $lockedUser->increment('credit_balance', $booking->price);

            return CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'refund',
                'amount' => $booking->price,
                'balance_after' => $lockedUser->fresh()->credit_balance,
                'booking_id' => $booking->id,
                'note' => $note ?? "คืนเครดิตค่าจอง #{$booking->id}",
            ]);
        });
    }

    /**
     * หักเครดิตเพื่อจ่ายค่า Private Training — เรียกตอนแอดมิน "จัดสนามให้" (assignCourt)
     * เพราะเป็นจุดแรกที่รู้ court_type (เต็ม/ครึ่งสนาม) แล้วคำนวณราคาได้จริง ต้องเรียก
     * ภายใน DB::transaction เดียวกับ flow assignCourt เพื่อกันเงินถูกหักแต่จัดสนามไม่สำเร็จ
     */
    public function deductForPrivateTraining(User $user, PrivateTrainingBooking $booking): CreditTransaction
    {
        $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();

        if ($lockedUser->credit_balance < $booking->price) {
            throw new RuntimeException('ยอดเครดิตของลูกค้าไม่เพียงพอสำหรับชำระค่า Private Training นี้');
        }

        $lockedUser->decrement('credit_balance', $booking->price);

        return CreditTransaction::create([
            'user_id' => $lockedUser->id,
            'type' => 'deduct',
            'amount' => $booking->price,
            'balance_after' => $lockedUser->fresh()->credit_balance,
            'private_training_booking_id' => $booking->id,
            'note' => "ชำระค่า Private Training #{$booking->id}",
        ]);
    }

    /**
     * คืนเครดิต กรณี Private Training ที่ชำระเงินแล้วถูกยกเลิก/ปฏิเสธย้อนหลัง
     */
    public function refundPrivateTraining(PrivateTrainingBooking $booking, ?string $note = null): CreditTransaction
    {
        return DB::transaction(function () use ($booking, $note) {
            $lockedUser = User::whereKey($booking->user_id)->lockForUpdate()->first();
            $lockedUser->increment('credit_balance', $booking->price);

            return CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'refund',
                'amount' => $booking->price,
                'balance_after' => $lockedUser->fresh()->credit_balance,
                'private_training_booking_id' => $booking->id,
                'note' => $note ?? "คืนเครดิตค่า Private Training #{$booking->id}",
            ]);
        });
    }
}
