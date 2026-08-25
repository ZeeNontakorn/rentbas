<?php

namespace App\Services;

use App\Mail\CreditExpiredMail;
use App\Mail\CreditExpiringSoonMail;
use App\Models\Booking;
use App\Models\CreditTopupRequest;
use App\Models\CreditTransaction;
use App\Models\Notification;
use App\Models\PrivateTrainingBooking;
use App\Models\PackagePurchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class CreditService
{
    /**
     * เพดานจำนวนวันหมดอายุสูงสุดของเครดิต — ไม่ว่าจะเติมกี่ครั้ง วันหมดอายุจะไม่ถูกขยับออกไปเกินจากวันนี้
     */
    const MAX_EXPIRY_DAYS = 365;

    /**
     * แอดมินอนุมัติคำขอเติมเครดิตที่ผู้ใช้ยื่นมาเอง (จากหน้า Top-up + แนบสลิป/แจ้งช่องทางชำระเงิน)
     * เติมเครดิตให้ผู้ใช้จริง พร้อมผูก transaction เข้ากับคำขอนี้ไว้เป็นหลักฐาน และบันทึกว่าใครเป็นผู้อนุมัติ
     * $expiryDays ต้องระบุเสมอ — ถ้าคำขอมี expiry_days snapshot ไว้จากแพ็กเกจแล้วให้ใช้ค่านั้น
     * ไม่งั้นแอดมินต้องกรอกตอนอนุมัติ (ดู CreditTopupController::approve)
     */
    public function approveTopupRequest(CreditTopupRequest $topupRequest, User $admin, ?string $note, int $expiryDays): CreditTransaction
    {
        if ($topupRequest->status !== 'pending') {
            throw new RuntimeException('คำขอนี้ถูกดำเนินการไปแล้ว');
        }

        return DB::transaction(function () use ($topupRequest, $admin, $note, $expiryDays) {
            $lockedUser = User::whereKey($topupRequest->user_id)->lockForUpdate()->first();
            $lockedUser->increment('credit_balance', $topupRequest->credit_satang);
            $this->extendExpiry($lockedUser, $expiryDays);

            $tx = CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'topup',
                'amount' => $topupRequest->credit_satang,
                'balance_after' => $lockedUser->fresh()->credit_balance,
                'admin_id' => $admin->id,
                'credit_topup_request_id' => $topupRequest->id,
                'payment_method' => $topupRequest->payment_method,
                'processed_by_name' => $admin->name,
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
     * แอดมินเติมเครดิตให้ผู้ใช้ (Admin Top-up) — ไม่ผ่านแพ็กเกจ จึงต้องระบุจำนวนวันหมดอายุเองเสมอ
     * (ระบบนี้ไม่มีแนวคิด "เครดิตไม่มีวันหมดอายุ" อีกต่อไป)
     */
    public function topup(
        User $user,
        int $amountSatang,
        User $admin,
        ?string $note,
        ?string $paymentMethod,
        ?string $processedByName,
        int $expiryDays,
    ): CreditTransaction {
        if ($amountSatang <= 0) {
            throw new RuntimeException('จำนวนเงินต้องมากกว่า 0');
        }

        return DB::transaction(function () use ($user, $amountSatang, $admin, $note, $paymentMethod, $processedByName, $expiryDays) {
            // lockForUpdate กันแอดมิน 2 คนเติมพร้อมกันแล้วยอดเพี้ยน (race condition)
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();
            $lockedUser->increment('credit_balance', $amountSatang);
            $this->extendExpiry($lockedUser, $expiryDays);

            return CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'topup',
                'amount' => $amountSatang,
                'balance_after' => $lockedUser->fresh()->credit_balance,
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
     * ไม่กระทบวันหมดอายุ (credit_expires_at) — แค่คืนยอดเงินที่เคยมีอยู่แล้วกลับเข้ากระเป๋าเดิม
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
    /**
     * หักเครดิตเพื่อจ่ายค่าซื้อแพ็กเกจ — ต้องเรียกภายใน DB::transaction ของ flow checkout
     * (ดู PackageCheckoutController::payWithCredit) แพทเทิร์นเดียวกับ deductForBooking
     */
    public function deductForPackage(User $user, PackagePurchase $purchase): CreditTransaction
    {
        $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();

        if ($lockedUser->credit_balance < $purchase->price) {
            throw new RuntimeException('ยอดเครดิตไม่เพียงพอ');
        }

        $lockedUser->decrement('credit_balance', $purchase->price);

        return CreditTransaction::create([
            'user_id' => $lockedUser->id,
            'type' => 'deduct',
            'amount' => $purchase->price,
            'balance_after' => $lockedUser->fresh()->credit_balance,
            'package_purchase_id' => $purchase->id,
            'note' => "ชำระค่าแพ็กเกจ #{$purchase->id}",
        ]);
    }

    /**
     * คืนเครดิต กรณีต้องยกเลิกการซื้อแพ็กเกจที่หักเงินไปแล้ว
     */
    public function refundPackage(PackagePurchase $purchase, ?string $note = null): CreditTransaction
    {
        return DB::transaction(function () use ($purchase, $note) {
            $lockedUser = User::whereKey($purchase->user_id)->lockForUpdate()->first();
            $lockedUser->increment('credit_balance', $purchase->price);

            return CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'refund',
                'amount' => $purchase->price,
                'balance_after' => $lockedUser->fresh()->credit_balance,
                'package_purchase_id' => $purchase->id,
                'note' => $note ?? "คืนเครดิตค่าแพ็กเกจ #{$purchase->id}",
            ]);
        });
    }

    /**
     * แอดมินหักเครดิตของผู้ใช้ด้วยตนเอง (เช่น แก้ไขเติมผิด/เติมเกิน) — หักยอดตรงๆ ไม่กระทบวันหมดอายุ
     */
    public function manualDeduct(User $user, int $amountSatang, User $admin, ?string $note = null): CreditTransaction
    {
        if ($amountSatang <= 0) {
            throw new RuntimeException('จำนวนเงินต้องมากกว่า 0');
        }

        return DB::transaction(function () use ($user, $amountSatang, $admin, $note) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();

            if ($lockedUser->credit_balance < $amountSatang) {
                throw new RuntimeException('ยอดเครดิตของผู้ใช้ไม่เพียงพอสำหรับหักตามจำนวนนี้');
            }

            $lockedUser->decrement('credit_balance', $amountSatang);

            return CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'deduct',
                'amount' => $amountSatang,
                'balance_after' => $lockedUser->fresh()->credit_balance,
                'admin_id' => $admin->id,
                'processed_by_name' => $admin->name,
                'note' => $note ?? 'แอดมินหักเครดิต (แก้ไข/ปรับยอด)',
            ]);
        });
    }

    /**
     * ขยายวันหมดอายุเครดิตของผู้ใช้ (credit_expires_at) — เติมเครดิตแต่ละครั้งจะขยับวันหมดอายุออกไป
     * เฉพาะกรณีที่ทำให้ "ช้าลง" กว่าเดิมเท่านั้น (max ของวันเดิมกับวันใหม่ที่คำนวณได้) และห้ามเกิน
     * MAX_EXPIRY_DAYS วันนับจากวันนี้ไม่ว่ากรณีใด ต้องเรียกด้วย $lockedUser ที่ lockForUpdate ไว้แล้ว
     * ภายใน transaction เดียวกับการ increment ยอดเครดิต
     */
    private function extendExpiry(User $lockedUser, int $days): void
    {
        $candidate = now()->addDays(min($days, self::MAX_EXPIRY_DAYS));

        $newExpiry = $lockedUser->credit_expires_at && $lockedUser->credit_expires_at->greaterThan($candidate)
            ? $lockedUser->credit_expires_at
            : $candidate;

        $lockedUser->forceFill(['credit_expires_at' => $newExpiry])->save();
    }

    /**
     * Scheduled job: หา user ที่เครดิตหมดอายุแล้ว (credit_expires_at ผ่านไปแล้ว และยังมียอดเหลือ)
     * แล้วเซ็ตยอดเป็น 0 จริง พร้อมบันทึกลง credit_transactions เป็นหลักฐาน แล้วแจ้งเตือนผู้ใช้ทันที
     * (กระดิ่ง + อีเมล) กันตกใจเห็นยอดหายโดยไม่รู้สาเหตุ — เรียกจาก routes/console.php
     */
    public function expireDueCredits(): int
    {
        $expiredCount = 0;

        User::query()
            ->select(['id', 'credit_balance', 'credit_expires_at', 'email'])
            ->whereNotNull('credit_expires_at')
            ->where('credit_expires_at', '<=', now())
            ->where('credit_balance', '>', 0)
            ->chunkById(100, function ($users) use (&$expiredCount): void {
                $users->each(function (User $user) use (&$expiredCount): void {
                    // ตัดยอดจริงอยู่ใน transaction เพื่อความถูกต้องของเงิน ส่วนแจ้งเตือน (เว็บ/เมล)
                    // เป็น side effect ภายนอกจึงทำ "หลัง" transaction ปิดสำเร็จแล้วเท่านั้น
                    $tx = DB::transaction(function () use ($user): ?CreditTransaction {
                        $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();

                        if (! $lockedUser->credit_expires_at || $lockedUser->credit_expires_at->isFuture() || $lockedUser->credit_balance <= 0) {
                            return null;
                        }

                        $amount = $lockedUser->credit_balance;

                        $tx = CreditTransaction::create([
                            'user_id' => $lockedUser->id,
                            'type' => 'expire',
                            'amount' => $amount,
                            'balance_after' => 0,
                            'note' => 'เครดิตหมดอายุอัตโนมัติ',
                        ]);

                        $lockedUser->forceFill([
                            'credit_balance' => 0,
                            'credit_expires_at' => null,
                            'credit_expiry_notified_for' => null,
                        ])->save();

                        return $tx;
                    });

                    if ($tx === null) {
                        return;
                    }

                    $expiredCount++;

                    try {
                        // type = credit_expired ให้ frontend (navbar) เช็คแล้วเด้ง SweetAlert บังคับกดรับทราบ
                        Notification::create([
                            'user_id' => $user->id,
                            'type' => 'credit_expired',
                            'title' => 'เครดิตของคุณหมดอายุแล้ว',
                            'message' => 'เครดิตจำนวน ' . number_format($tx->amount / 100, 2)
                                . ' บาท ถูกตัดออกจากบัญชีเนื่องจากครบกำหนดวันหมดอายุ|ยอดคงเหลือปัจจุบัน 0.00 บาท',
                            'action_url' => route('credits.topup.index'),
                        ]);
                    } catch (\Throwable $e) {
                        Log::error("สร้างแจ้งเตือนในเว็บ (เครดิตหมดอายุ) ไม่สำเร็จ สำหรับ user_id={$user->id}: " . $e->getMessage());
                    }

                    try {
                        Mail::to($user->email)->send(new CreditExpiredMail($tx));
                    } catch (\Throwable $e) {
                        Log::error("ส่งอีเมลแจ้งเตือนเครดิตหมดอายุ (user #{$user->id}) ไม่สำเร็จ: " . $e->getMessage());
                    }
                });
            });

        return $expiredCount;
    }

    /**
     * Scheduled job: แจ้งเตือนผู้ใช้ที่เครดิตใกล้หมดอายุ (ภายใน 7 วัน) ทั้งขึ้นกระดิ่งแจ้งเตือนในเว็บ
     * และส่งอีเมล — แจ้ง "ทุกวัน" ตั้งแต่เหลือ 7 วันจนถึงวันสุดท้ายก่อนหมดอายุ (ไม่ใช่แค่ครั้งเดียว)
     * credit_expiry_notified_for ใช้เก็บ "วันที่ล่าสุดที่แจ้งไปแล้ว" กันแจ้งซ้ำมากกว่า 1 ครั้งต่อวัน
     */
    public function notifyExpiringSoonCredits(): int
    {
        $notifiedCount = 0;
        $today = now()->toDateString();

        User::query()
            ->whereNotNull('credit_expires_at')
            ->where('credit_expires_at', '>', now())
            ->where('credit_expires_at', '<=', now()->addDays(7))
            ->where('credit_balance', '>', 0)
            ->where(function ($query) use ($today) {
                $query->whereNull('credit_expiry_notified_for')
                    ->orWhereDate('credit_expiry_notified_for', '<>', $today);
            })
            ->chunkById(100, function ($users) use (&$notifiedCount): void {
                $users->each(function (User $user) use (&$notifiedCount): void {
                    try {
                        Notification::create([
                            'user_id' => $user->id,
                            'type' => 'credit_expiring_soon',
                            'title' => 'เครดิตของคุณใกล้หมดอายุ',
                            'message' => 'เครดิตคงเหลือ ' . number_format($user->credit_balance / 100, 2)
                                . ' บาท จะหมดอายุวันที่ ' . $user->credit_expires_at->format('d/m/Y')
                                . ' กรุณาใช้เครดิตก่อนหมดอายุ',
                            'action_url' => route('credits.topup.index'),
                        ]);
                    } catch (\Throwable $e) {
                        Log::error("สร้างแจ้งเตือนในเว็บ (เครดิตใกล้หมดอายุ) ไม่สำเร็จ สำหรับ user_id={$user->id}: " . $e->getMessage());
                    }

                    try {
                        Mail::to($user->email)->send(new CreditExpiringSoonMail($user));
                    } catch (\Throwable $e) {
                        Log::error("ส่งอีเมลแจ้งเตือนเครดิตใกล้หมดอายุ (user #{$user->id}) ไม่สำเร็จ: " . $e->getMessage());
                    }

                    $user->forceFill(['credit_expiry_notified_for' => now()])->save();
                    $notifiedCount++;
                });
            });

        return $notifiedCount;
    }
}
