<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CreditDeductedMail;
use App\Mail\CreditTopupReceiptMail;
use App\Models\Notification;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class CreditController extends Controller
{
    public function __construct(protected CreditService $creditService)
    {
    }

    /**
     * แสดงประวัติเครดิต/ยอดคงเหลือของผู้ใช้คนหนึ่ง (ใช้ในหน้า admin.users.show ได้เช่นกัน)
     */
    public function show(User $user)
    {
        $transactions = $user->creditTransactions()->latest()->paginate(20);

        return view('admin.credit.show', compact('user', 'transactions'));
    }

    /**
     * เติมเครดิตให้ผู้ใช้ที่ระบุ — amount รับเป็น "บาท" จากฟอร์ม แปลงเป็นสตางค์ก่อนบันทึก
     * ต้องระบุ expiry_days เสมอ (ระบบไม่มีแนวคิด "ไม่มีวันหมดอายุ" อีกต่อไป)
     */
    public function topup(Request $request, User $user)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'payment_method' => ['required', 'in:line,cash_counter'],
            'note' => ['nullable', 'string', 'max:255'],
            'expiry_days' => ['required', 'integer', 'min:1', 'max:365'],
        ],[
            'payment_method.required' => 'กรุณาเลือกรูปแบบการชำระเงินที่ลูกค้าใช้จ่ายจริง',
            'expiry_days.required' => 'กรุณาระบุจำนวนวันหมดอายุของเครดิต',
        ]);

        $processedByName = $data['processed_by_name'] ?? $request->user()->name;

        $amountSatang = (int) round($data['amount'] * 100);

        try {
            $tx = $this->creditService->topup($user, $amountSatang, $request->user(), $data['note'] ?? null, $data['payment_method'], $processedByName, $data['expiry_days']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        Notification::create([
            'user_id' => $user->id,
            'type' => 'credit_topped_up',
            'title' => 'เครดิตของคุณถูกเติมแล้ว',
            'message' => "แอดมินเติมเครดิตให้คุณ {$data['amount']} บาท ยอดคงเหลือปัจจุบัน " . number_format($user->fresh()->credit_balance / 100, 2) . ' บาท',
        ]);

        try {
            Mail::to($user->email)->send(new CreditTopupReceiptMail($tx));
        } catch (\Throwable $e) {
            Log::error("ส่งอีเมลใบเสร็จเติมเครดิตแบบ manual (user #{$user->id}) ไม่สำเร็จ: " . $e->getMessage());
        }

        return back()->with('success', "เติมเครดิตให้ {$user->us_name} จำนวน {$data['amount']} บาท สำเร็จ");
    }

    /**
     * แอดมินหักเครดิตของผู้ใช้ด้วยตนเอง (เช่น แก้ไขเติมผิด/เติมเกิน)
     */
    public function deduct(Request $request, User $user)
    {
        $data = $request->validate([
            'deduct_amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'deduct_note' => ['nullable', 'string', 'max:255'],
        ]);

        $amountSatang = (int) round($data['deduct_amount'] * 100);

        try {
            $tx = $this->creditService->manualDeduct($user, $amountSatang, $request->user(), $data['deduct_note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['deduct_amount' => $e->getMessage()]);
        }

        Notification::create([
            'user_id' => $user->id,
            'type' => 'credit_adjusted',
            'title' => 'เครดิตของคุณถูกปรับยอด',
            'message' => "แอดมินปรับยอดเครดิตของคุณ หักออก {$data['deduct_amount']} บาท ยอดคงเหลือปัจจุบัน " . number_format($user->fresh()->credit_balance / 100, 2) . ' บาท'
                . (! empty($data['deduct_note']) ? "|เหตุผล: {$data['deduct_note']}" : ''),
            'action_url' => route('credits.topup.index'),
        ]);

        try {
            Mail::to($user->email)->send(new CreditDeductedMail($tx));
        } catch (\Throwable $e) {
            Log::error("ส่งอีเมลแจ้งปรับยอดเครดิต (user #{$user->id}) ไม่สำเร็จ: " . $e->getMessage());
        }

        return back()->with('success', "หักเครดิตของ {$user->us_name} จำนวน {$data['deduct_amount']} บาท สำเร็จ");
    }
}
