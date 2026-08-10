<?php

namespace App\Http\Controllers;

use App\Mail\CreditTopupRequestedMail;
use App\Models\CreditTopupPackage;
use App\Models\CreditTopupRequest;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CreditTopupController extends Controller
{
    /**
     * Step 1 — เลือกแพ็กเกจเติมเครดิต (250/500/800/1600) หรือกรอกจำนวนเงินเอง
     * มีปุ่มลัด "เติมผ่าน LINE ไวกว่า" ที่พาไปหน้าแอด LINE OA ตรงๆ โดยไม่ต้องกรอกฟอร์ม
     */
    public function index(Request $request)
    {
        $packages = CreditTopupPackage::active()
            ->orderBy('sort_order')
            ->orderBy('price_satang')
            ->get();

        $lineUrl = Setting::getVal('line_topup_url');
        $promptpayNumber = Setting::getVal('promptpay_number');
        $promptpayName = Setting::getVal('promptpay_name');

        $myRequests = $request->user()->creditTopupRequests()
            ->latest()
            ->take(5)
            ->get();

        return view('credits.topup.index', compact('packages', 'lineUrl', 'promptpayNumber', 'promptpayName', 'myRequests'));
    }

    /**
     * Step 2 — สรุปยอด + mockup QR PromptPay / แจ้งช่องทางชำระเงิน + แนบสลิป (GET แสดงฟอร์ม)
     */
    public function checkout(Request $request)
    {
        $data = $request->validate([
            'package_id' => ['nullable', 'exists:credit_topup_packages,id'],
            'custom_amount' => ['nullable', 'numeric', 'min:20', 'max:100000'],
        ]);

        $package = null;

        if (! empty($data['package_id'])) {
            $package = CreditTopupPackage::active()->findOrFail($data['package_id']);
            $priceSatang = $package->price_satang;
            $creditSatang = $package->credit_satang;
        } elseif (! empty($data['custom_amount'])) {
            $priceSatang = (int) round($data['custom_amount'] * 100);
            $creditSatang = $priceSatang;
        } else {
            return redirect()->route('credits.topup.index')
                ->withErrors(['package_id' => 'กรุณาเลือกแพ็กเกจ หรือระบุจำนวนเงินที่ต้องการเติม']);
        }

        return view('credits.topup.checkout', [
            'package' => $package,
            'priceSatang' => $priceSatang,
            'creditSatang' => $creditSatang,
            'lineUrl' => Setting::getVal('line_topup_url'),
            'promptpayNumber' => Setting::getVal('promptpay_number'),
            'promptpayName' => Setting::getVal('promptpay_name'),
        ]);
    }

    /**
     * Step 3 — ยื่นคำขอเติมเครดิต (สร้าง CreditTopupRequest สถานะ pending ให้แอดมินตรวจสอบ/อนุมัติ)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'package_id' => ['nullable', 'exists:credit_topup_packages,id'],
            'price_satang' => ['required', 'integer', 'min:2000'],
            'credit_satang' => ['required', 'integer', 'min:2000'],
            'slip' => ['required', 'image', 'max:5120'],
        ], [
            'slip.required' => 'กรุณาแนบสลิปการโอนเงินก่อนส่งคำขอ',
            'slip.image' => 'ไฟล์สลิปต้องเป็นรูปภาพเท่านั้น',
        ]);

        $slipPath = null;
        if ($request->hasFile('slip')) {
            $slipPath = $request->file('slip')->store('credit-topup-slips', 'public');
        }

        $topupRequest = CreditTopupRequest::create([
            'user_id' => $request->user()->id,
            'credit_topup_package_id' => $data['package_id'] ?? null,
            'price_satang' => $data['price_satang'],
            'credit_satang' => $data['credit_satang'],
            'payment_method' => 'promptpay',
            'slip_path' => $slipPath,
        ]);

        $this->notifyAdminsOfNewRequest($topupRequest);

        return redirect()->route('credits.topup.index')
            ->with('success', 'ส่งคำขอเติมเครดิตเรียบร้อยแล้ว กรุณารอแอดมินตรวจสอบและอนุมัติ ระบบจะแจ้งเตือนเมื่อดำเนินการเสร็จสิ้น');
    }

    /**
     * แจ้งเตือนแอดมินทุกคนว่ามีคำขอเติมเครดิตใหม่รอตรวจสอบ — ทั้งแจ้งเตือนในเว็บ (กระดิ่ง)
     * และอีเมล แยก try/catch กันคนละส่วน เพื่อไม่ให้ฝั่งหนึ่งพังแล้วอีกฝั่งไม่ทำงานไปด้วย
     * (อีเมลส่งแบบ synchronous เพราะไม่มี queue worker — ดูเหตุผลใน CreditTopupRequestedMail)
     */
    protected function notifyAdminsOfNewRequest(CreditTopupRequest $topupRequest): void
    {
        $admins = User::whereIn('role', ['admin', 'superadmin'])->get();

        $amountText = number_format($topupRequest->price_satang / 100, 2);
        $requesterName = $topupRequest->user->name ?? 'ผู้ใช้';

        // 1) แจ้งเตือนในเว็บ (ขึ้นกระดิ่ง) — ทำก่อนอีเมล และแยก try/catch ต่อแอดมินแต่ละคน
        foreach ($admins as $admin) {
            try {
                Notification::create([
                    'user_id' => $admin->id,
                    'title' => 'มีคำขอเติมเครดิตใหม่',
                    'message' => "ผู้ใช้ {$requesterName} ส่งคำขอเติมเครดิตจำนวน {$amountText} บาท|กดเพื่อตรวจสอบสลิปและอนุมัติ",
                    'is_read' => false,
                    'action_url' => route('admin.credit-topups.index'),
                ]);
            } catch (\Throwable $e) {
                Log::error('สร้างแจ้งเตือนในเว็บ (คำขอเติมเครดิตใหม่) ไม่สำเร็จ สำหรับแอดมิน user_id=' . $admin->id . ': ' . $e->getMessage());
            }
        }

        // 2) อีเมล — ครอบ try/catch กันเมลเซิร์ฟเวอร์ล่มแล้วทำให้คำขอที่บันทึกสำเร็จแล้วดูเหมือนพัง

        try {
            foreach ($admins->pluck('email')->filter() as $email) {
                Mail::to($email)->send(new CreditTopupRequestedMail($topupRequest));
            }
        } catch (\Throwable $e) {
            Log::error('ส่งอีเมลแจ้งเตือนแอดมิน (คำขอเติมเครดิตใหม่) ไม่สำเร็จ: ' . $e->getMessage());
        }
    }
}


