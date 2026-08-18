<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Credit;
use App\Models\CreditTopupRequest;
use App\Models\CreditTransaction;
use App\Models\PrivateTrainingBooking;
use App\Models\PackagePurchase;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreditService
{
    /**
     * แอดมินอนุมัติคำขอเติมเครดิตที่ผู้ใช้ยื่นมาเอง (จากหน้า Top-up + แนบสลิป/แจ้งช่องทางชำระเงิน)
     * เติมเครดิตให้ผู้ใช้จริง พร้อมผูก transaction เข้ากับคำขอนี้ไว้เป็นหลักฐาน และบันทึกว่าใครเป็นผู้อนุมัติ
     */
    public function approveTopupRequest(CreditTopupRequest $topupRequest, User $admin, ?string $note = null): CreditTransaction
    {
        if ($topupRequest->status !== 'pending') {
            throw new RuntimeException('คำขอนี้ถูกดำเนินการไปแล้ว');
        }

        return DB::transaction(function () use ($topupRequest, $admin, $note) {
            $expiresAt = $topupRequest->expiry_days ? now()->addDays($topupRequest->expiry_days) : null;

            $this->grantLot($topupRequest->user_id, $topupRequest->credit_satang, $expiresAt, 'topup_request', [
                'credit_topup_request_id' => $topupRequest->id,
            ]);

            $tx = CreditTransaction::create([
                'user_id' => $topupRequest->user_id,
                'type' => 'topup',
                'amount' => $topupRequest->credit_satang,
                'balance_after' => $this->currentBalance($topupRequest->user_id),
                'admin_id' => $admin->id,
                'credit_topup_request_id' => $topupRequest->id,
                'payment_method' => $topupRequest->payment_method,
                'note' => $note ?? "อนุมัติคำขอเติมเครดิต #{$topupRequest->id} ({$topupRequest->payment_method_label})",
            ]);

            $topupRequest->update([
                'status' => 'approved',
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            return $tx;
        });
    }

    /**
     * แอดมินปฏิเสธคำขอเติมเครดิต (เช่น สลิปไม่ถูกต้อง/ยอดไม่ตรง) — ไม่มีการเติมเครดิตใดๆ
     */
    public function rejectTopupRequest(CreditTopupRequest $topupRequest, User $admin, string $reason): CreditTopupRequest
    {
        if ($topupRequest->status !== 'pending') {
            throw new RuntimeException('คำขอนี้ถูกดำเนินการไปแล้ว');
        }

        $topupRequest->update([
            'status' => 'rejected',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'rejected_reason' => $reason,
        ]);

        return $topupRequest;
    }

    /**
     * แอดมินเติมเครดิตให้ผู้ใช้ (Admin Top-up) — ไม่ผ่านแพ็กเกจ จึงให้แอดมินกำหนดจำนวนวันหมดอายุเอง
     * ($expiryDays = null คือไม่มีวันหมดอายุ)
     */
    public function topup(
        User $user,
        int $amountSatang,
        User $admin,
        ?string $note = null ,
        ?string $paymentMethod = null,
        ?string $processedByName = null,
        ?int $expiryDays = null,
    ): CreditTransaction{
        if ($amountSatang <= 0) {
            throw new RuntimeException('จำนวนเงินต้องมากกว่า 0');
        }

        return DB::transaction(function () use ($user, $amountSatang, $admin, $note, $paymentMethod, $processedByName, $expiryDays) {
            $expiresAt = $expiryDays ? now()->addDays($expiryDays) : null;

            $this->grantLot($user->id, $amountSatang, $expiresAt, 'admin_manual', [
                'admin_id' => $admin->id,
            ]);

            return CreditTransaction::create([
                'user_id' => $user->id,
                'type' => 'topup',
                'amount' => $amountSatang,
                'balance_after' => $this->currentBalance($user->id),
                'admin_id' => $admin->id,
                'note' => $note,
                'payment_method' => $paymentMethod,
                'processed_by_name' => $processedByName,
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
        $tx = $this->drawDown($user, $booking->price, 'deduct', "ชำระค่าจอง #{$booking->id}");
        $tx->update(['booking_id' => $booking->id]);

        return $tx;
    }

    /**
     * คืนเครดิต กรณีต้องยกเลิก booking ที่หักเงินไปแล้ว (เช่น แอดมินยกเลิกย้อนหลัง)
     * เครดิตที่คืนจะเป็นก้อนใหม่ไม่มีวันหมดอายุเสมอ (การหักครั้งหนึ่งอาจดึงมาจากหลายก้อน
     * จึงไม่พยายามคืนกลับก้อนเดิมที่ถูกหักไป)
     */
    public function refund(Booking $booking, ?string $note = null): CreditTransaction
    {
        return DB::transaction(function () use ($booking, $note) {
            $this->grantLot($booking->user_id, $booking->price, null, 'refund');

            return CreditTransaction::create([
                'user_id' => $booking->user_id,
                'type' => 'refund',
                'amount' => $booking->price,
                'balance_after' => $this->currentBalance($booking->user_id),
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
        $tx = $this->drawDown(
            $user,
            $booking->price,
            'deduct',
            "ชำระค่า Private Training #{$booking->id}",
            insufficientMessage: 'ยอดเครดิตของลูกค้าไม่เพียงพอสำหรับชำระค่า Private Training นี้',
        );
        $tx->update(['private_training_booking_id' => $booking->id]);

        return $tx;
    }

    /**
     * คืนเครดิต กรณี Private Training ที่ชำระเงินแล้วถูกยกเลิก/ปฏิเสธย้อนหลัง
     */
    public function refundPrivateTraining(PrivateTrainingBooking $booking, ?string $note = null): CreditTransaction
    {
        return DB::transaction(function () use ($booking, $note) {
            $this->grantLot($booking->user_id, $booking->price, null, 'refund');

            return CreditTransaction::create([
                'user_id' => $booking->user_id,
                'type' => 'refund',
                'amount' => $booking->price,
                'balance_after' => $this->currentBalance($booking->user_id),
                'private_training_booking_id' => $booking->id,
                'note' => $note ?? "คืนเครดิตค่า Private Training #{$booking->id}",
            ]);
        });
    }
    /**
     * หักเครดิตเพื่อจ่ายค่าซื้อแพ็กเกจ — ต้องเรียกภายใน DB::transaction ของ flow checkout
     * (ดู PackageCheckoutController::payWithCredit) แพทเทิร์นเดียวกับ deductForBooking
     */
    public function deductForPackage(User $user, PackagePurchase $purchase): CreditTransaction
    {
        $tx = $this->drawDown($user, $purchase->price, 'deduct', "ชำระค่าแพ็กเกจ #{$purchase->id}");
        $tx->update(['package_purchase_id' => $purchase->id]);

        return $tx;
    }

    /**
     * คืนเครดิต กรณีต้องยกเลิกการซื้อแพ็กเกจที่หักเงินไปแล้ว
     */
    public function refundPackage(PackagePurchase $purchase, ?string $note = null): CreditTransaction
    {
        return DB::transaction(function () use ($purchase, $note) {
            $this->grantLot($purchase->user_id, $purchase->price, null, 'refund');

            return CreditTransaction::create([
                'user_id' => $purchase->user_id,
                'type' => 'refund',
                'amount' => $purchase->price,
                'balance_after' => $this->currentBalance($purchase->user_id),
                'package_purchase_id' => $purchase->id,
                'note' => $note ?? "คืนเครดิตค่าแพ็กเกจ #{$purchase->id}",
            ]);
        });
    }

    /**
     * แอดมินหักเครดิตของผู้ใช้ด้วยตนเอง (เช่น เติมผิด/เติมเกิน) — หักจากก้อนที่ "เติมล่าสุดก่อน"
     * (LIFO by created_at) ต่างจากการหักตอนจ่ายค่าจอง/แพ็กเกจที่หักตามวันหมดอายุ (FIFO by expiry)
     * เพราะเป้าหมายคือแก้ไขรายการที่เพิ่งเกิดขึ้นผิดพลาด ไม่ใช่ใช้เครดิตของลูกค้าตามลำดับหมดอายุ
     */
    public function manualDeduct(User $user, int $amountSatang, User $admin, ?string $note = null): CreditTransaction
    {
        if ($amountSatang <= 0) {
            throw new RuntimeException('จำนวนเงินต้องมากกว่า 0');
        }

        return DB::transaction(function () use ($user, $amountSatang, $admin, $note) {
            $tx = $this->drawDown(
                $user,
                $amountSatang,
                'deduct',
                $note ?? 'แอดมินหักเครดิต (แก้ไข/ปรับยอด)',
                insufficientMessage: 'ยอดเครดิตของผู้ใช้ไม่เพียงพอสำหรับหักตามจำนวนนี้',
                order: 'latest',
            );
            $tx->update(['admin_id' => $admin->id]);

            return $tx;
        });
    }

    /**
     * แอดมินยกเลิก/ปรับยอดก้อนเครดิตก้อนใดก้อนหนึ่งโดยเจาะจง (เช่น เติมผิดก้อน ต้องการหักคืนเฉพาะก้อนนั้น)
     * ต่างจาก manualDeduct ตรงที่ไม่กระจายการหักไปก้อนอื่นเลย
     */
    public function voidLot(Credit $lot, int $amountSatang, User $admin, ?string $note = null): CreditTransaction
    {
        if ($amountSatang <= 0) {
            throw new RuntimeException('จำนวนเงินต้องมากกว่า 0');
        }

        return DB::transaction(function () use ($lot, $amountSatang, $admin, $note) {
            $lockedLot = Credit::whereKey($lot->id)->lockForUpdate()->first();

            if ($lockedLot->remaining_satang < $amountSatang) {
                throw new RuntimeException('ยอดคงเหลือของก้อนนี้ไม่พอสำหรับจำนวนที่ต้องการหัก');
            }

            $lockedLot->decrement('remaining_satang', $amountSatang);

            $tx = CreditTransaction::create([
                'user_id' => $lockedLot->user_id,
                'type' => 'deduct',
                'amount' => $amountSatang,
                'balance_after' => $this->currentBalance($lockedLot->user_id),
                'admin_id' => $admin->id,
                'note' => $note ?? "แอดมินยกเลิก/แก้ไขก้อนเครดิต #{$lockedLot->id}",
            ]);

            $tx->lots()->create([
                'credit_id' => $lockedLot->id,
                'amount_satang' => $amountSatang,
            ]);

            return $tx;
        });
    }

    /**
     * สร้างก้อนเครดิตใหม่ (credits row) ให้ผู้ใช้
     */
    private function grantLot(int $userId, int $amountSatang, ?Carbon $expiresAt, string $source, array $extra = []): Credit
    {
        return Credit::create(array_merge([
            'user_id' => $userId,
            'amount_satang' => $amountSatang,
            'remaining_satang' => $amountSatang,
            'expires_at' => $expiresAt,
            'source' => $source,
        ], $extra));
    }

    /**
     * หักเครดิต $amountSatang จากหลายก้อนตามลำดับที่กำหนด อาจดึงจากหลายก้อน
     * ต้องเรียกภายใน DB::transaction ของ flow ที่เรียกใช้ เพื่อให้การล็อกแถวและอัปเดต booking/purchase
     * อยู่ใน transaction เดียวกัน — ล็อกทั้งแถว user (จุดล็อกเดิมที่ flow อื่นอ้างอิงอยู่) และแถว
     * credits ที่เกี่ยวข้อง กันสองคำขอพร้อมกันหักซ้ำก้อนเดียวกัน (double-spend)
     *
     * $order: 'expiry' = ก้อนที่ใกล้หมดอายุที่สุดก่อน (FIFO by expiry, ใช้ตอนจ่ายค่าจอง/แพ็กเกจ)
     *         'latest' = ก้อนที่เติมล่าสุดก่อน (LIFO by created_at, ใช้ตอนแอดมินหักแก้ไขข้อผิดพลาด)
     */
    private function drawDown(User $user, int $amountSatang, string $type, string $note, ?string $insufficientMessage = null, string $order = 'expiry'): CreditTransaction
    {
        $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();

        $lotsQuery = Credit::where('user_id', $lockedUser->id)->valid();

        match ($order) {
            'latest' => $lotsQuery->orderByDesc('created_at')->orderByDesc('id'),
            default => $lotsQuery->expiryOrder(),
        };

        $lots = $lotsQuery->lockForUpdate()->get();

        $available = (int) $lots->sum('remaining_satang');

        if ($available < $amountSatang) {
            throw new RuntimeException($insufficientMessage ?? 'ยอดเครดิตไม่เพียงพอ');
        }

        $tx = CreditTransaction::create([
            'user_id' => $lockedUser->id,
            'type' => $type,
            'amount' => $amountSatang,
            'balance_after' => $available - $amountSatang,
            'note' => $note,
        ]);

        $remaining = $amountSatang;
        foreach ($lots as $lot) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($lot->remaining_satang, $remaining);
            $lot->decrement('remaining_satang', $take);
            $remaining -= $take;

            $tx->lots()->create([
                'credit_id' => $lot->id,
                'amount_satang' => $take,
            ]);
        }

        return $tx;
    }

    /**
     * ยอดเครดิตคงเหลือปัจจุบันของผู้ใช้ (หน่วยสตางค์) ใช้บันทึก balance_after บน transaction
     */
    private function currentBalance(int $userId): int
    {
        return (int) Credit::where('user_id', $userId)->valid()->sum('remaining_satang');
    }
}
