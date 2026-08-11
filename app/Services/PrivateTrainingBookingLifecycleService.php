<?php

namespace App\Services;

use App\Models\PackagePurchase;
use App\Models\PrivateTrainingBooking;
use Illuminate\Support\Facades\DB;

class PrivateTrainingBookingLifecycleService
{
    /**
     * ปิดคำขอที่เลยเวลาเริ่มแล้วและคืนสิทธิ์แพ็กเกจให้ลูกค้าเพียงครั้งเดียว
     */
    public function expireUnprocessedBookings(): int
    {
        $today = now()->toDateString();
        $currentTime = now()->toTimeString();
        $expiredCount = 0;

        PrivateTrainingBooking::query()
            ->select(['id', 'package_purchase_id'])
            ->whereIn('status', ['pending', 'awaiting_court'])
            ->where(function ($query) use ($today, $currentTime): void {
                $query->whereDate('date', '<', $today)
                    ->orWhere(function ($sameDay) use ($today, $currentTime): void {
                        $sameDay->whereDate('date', $today)
                            ->where('start_time', '<=', $currentTime);
                    });
            })
            ->chunkById(100, function ($candidates) use (&$expiredCount): void {
                $candidates->each(function (PrivateTrainingBooking $candidate) use (&$expiredCount): void {
                    $expired = DB::transaction(function () use ($candidate): bool {
                        // ล็อกแพ็กเกจก่อนรายการจองให้เป็นลำดับเดียวกับขั้นตอนสร้างคำขอ
                        // เพื่อลดโอกาส deadlock ระหว่างการใช้สิทธิ์กับการคืนสิทธิ์
                        if ($candidate->package_purchase_id) {
                            PackagePurchase::whereKey($candidate->package_purchase_id)
                                ->lockForUpdate()
                                ->first();
                        }

                        $booking = PrivateTrainingBooking::whereKey($candidate->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $booking
                            || ! in_array($booking->status, ['pending', 'awaiting_court'], true)
                            || ! $booking->isStarted()) {
                            return false;
                        }

                        $this->restorePackageUse($booking);
                        $booking->update(['status' => 'expired']);

                        return true;
                    }, 3);

                    if ($expired) {
                        $expiredCount++;
                    }
                });
            });

        return $expiredCount;
    }

    /**
     * ต้องเรียกภายใน transaction หลังล็อก PrivateTrainingBooking แล้วเท่านั้น
     */
    public function restorePackageUse(PrivateTrainingBooking $booking): void
    {
        if (! $booking->package_purchase_id) {
            return;
        }

        PackagePurchase::whereKey($booking->package_purchase_id)
            ->increment('remaining_use');
    }
}
