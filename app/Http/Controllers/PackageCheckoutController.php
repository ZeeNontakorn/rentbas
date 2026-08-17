<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\PackagePurchase;
use App\Models\Notification;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Checkout flow สำหรับ "ซื้อแพ็กเกจ" — คู่ขนานกับ CheckoutController (จองสนาม)
 * แยกเป็นไฟล์ต่างหากเพราะโมเดล/เงื่อนไขต่างกัน (ไม่มีเรื่องคอร์ต/เวลา/ทับซ้อนสล็อต)
 * แต่ flow การจ่ายเงิน (ล็อก 15 นาที / หักเครดิต / countdown) ใช้แพทเทิร์นเดียวกัน
 */
class PackageCheckoutController extends Controller
{
    public const LOCK_MINUTES = 15;

    public function __construct(protected CreditService $creditService)
    {
    }

    /**
     * กดปุ่ม "เลือกแพ็กเกจนี้" -> สร้างรายการซื้อ (ล็อกราคาไว้ 15 นาที) แล้วพาไปหน้าชำระเงิน
     */
    public function purchase(Package $package, Request $request)
    {
        // TODO ยืนยันหน่วยราคา: ถ้า $package->price เก็บเป็น "บาท" ให้คูณ 100 แปลงเป็นสตางค์
        // แต่ถ้าเก็บเป็นสตางค์อยู่แล้ว (เหมือน Booking->price) ให้ลบ * 100 ออก
        $priceSatang = (int) round($package->price * 100);

        $purchase = PackagePurchase::create([
            'user_id' => $request->user()->id,
            'package_id' => $package->id,
            'price' => $priceSatang,
            'status' => 'pending_payment',
            'remaining_use' => $package->num_of_use,
            'locked_until' => now()->addMinutes(self::LOCK_MINUTES),
        ]);

        return redirect()->route('package-checkout.show', $purchase);
    }

    /**
     * หน้าเลือกวิธีชำระเงิน (เหมือน CheckoutController::show)
     */
    public function show(PackagePurchase $purchase, Request $request)
    {
        abort_unless($purchase->user_id === $request->user()->id, 403);

        if ($purchase->status !== 'pending_payment' || ($purchase->locked_until && $purchase->locked_until->isPast())) {
            return redirect()->route('home')->withErrors(['purchase' => 'รายการนี้หมดเวลาแล้ว กรุณาเลือกแพ็กเกจใหม่']);
        }

        return view('checkout.package-show', compact('purchase'));
    }

    /**
     * ชำระด้วยเครดิต — หักเงิน + อนุมัติทันที (เหมือน CheckoutController::payWithCredit)
     */
    public function payWithCredit(PackagePurchase $purchase, Request $request)
    {
        try {
            $confirmed = DB::transaction(function () use ($purchase, $request) {
                $locked = PackagePurchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();

                abort_unless($locked->user_id === $request->user()->id, 403);

                if ($locked->status !== 'pending_payment') {
                    throw new RuntimeException('รายการนี้ไม่ได้อยู่ในสถานะรอชำระเงิน (อาจหมดเวลาไปแล้ว)');
                }

                if ($locked->locked_until && $locked->locked_until->isPast()) {
                    $locked->update(['status' => 'expired']);
                    throw new RuntimeException('หมดเวลาชำระเงิน (15 นาที) กรุณาเลือกแพ็กเกจใหม่');
                }

                $this->creditService->deductForPackage($request->user(), $locked);

                // 1. ค้นหาแพ็กเกจแบบเดียวกันของลูกค้ารายนี้ ที่สถานะอนุมัติแล้วและยังใช้งานได้
                $existing = PackagePurchase::where('user_id', $request->user()->id)
                    ->where('package_id', $locked->package_id)
                    ->where('status', 'approved')
                    ->where('remaining_use', '>', 0)
                    ->where(function ($q) {
                        $q->whereNull('expired_at')->orWhere('expired_at', '>', now());
                    })
                    ->lockForUpdate()
                    ->first();

                // 2. คำนวณวันหมดอายุใหม่
                $newExpiryDate = $locked->package->day ? now()->addDays($locked->package->day) : null;

                if ($existing) {
                    // 3A. กรณีมีแพ็กเกจเดิม: นำจำนวนครั้งไปบวกเพิ่ม และทับวันหมดอายุใหม่
                    $existing->remaining_use += $locked->package->num_of_use;
                    $existing->expired_at = $newExpiryDate;
                    $existing->save();

                    // อัปเดตรายการซื้อรอบนี้ให้สมบูรณ์ แต่ตั้ง remaining_use เป็น 0 (เพื่อเป็นแค่ใบเสร็จ)
                    $locked->update([
                        'status' => 'approved',
                        'booking_source' => 'credit',
                        'payment_method' => 'credit',
                        'payment_status' => 'paid',
                        'locked_until' => null,
                        'paid_at' => now(),
                        'remaining_use' => 0, // ป้องกันไม่ให้ไปแสดงซ้ำใน Dropdown
                        'expired_at' => $newExpiryDate,
                    ]);
                } else {
                    // 3B. กรณีไม่มีแพ็กเกจเดิม: ใช้รายการนี้เป็นตัวตั้งต้นเก็บโควต้าตามปกติ
                    $locked->update([
                        'status' => 'approved',
                        'booking_source' => 'credit',
                        'payment_method' => 'credit',
                        'payment_status' => 'paid',
                        'locked_until' => null,
                        'paid_at' => now(),
                        'expired_at' => $newExpiryDate,
                    ]);
                }

                return $locked->fresh();
            });
        } catch (RuntimeException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        $packageName = $confirmed->package->name;

        // แจ้งเตือนในกระดิ่ง (bell notification) ให้ผู้ใช้เห็นว่าซื้อแพ็กเกจสำเร็จ
        Notification::create([
            'user_id' => $confirmed->user_id,
            'title' => 'ยืนยันการซื้อแพ็กเกจ',
            'message' => "คุณได้ซื้อแพ็กเกจ \"{$packageName}\" สำเร็จแล้ว ใช้ได้ {$confirmed->package->num_of_use} ครั้ง",
        ]);

        // TODO: ถ้ามีอีเมลยืนยันซื้อแพ็กเกจ/แจ้งแอดมิน ให้เพิ่มตรงนี้เหมือน CheckoutController::payWithCredit

        return redirect()->route('private-training.index')
            ->with('success', "ยืนยันการซื้อแพ็กเกจ \"{$packageName}\" สำเร็จ!");
    }
}
