<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
     */
    public function topup(Request $request, User $user)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'payment_method' => ['required', 'in:line,cash_counter'],
            'processed_by_name' => [
                'required', 'string', 'max:100',
                'regex:/^[\p{Thai}]+(?:\s+[\p{Thai}]+)+$/u',
            ],
            'note' => ['nullable', 'string', 'max:255'],
        ],[
            'payment_method.required' => 'กรุณาเลือกรูปแบบการชำระเงินที่ลูกค้าใช้จ่ายจริง',
            'processed_by_name.required' => 'กรุณากรอกชื่อ-นามสกุลจริงของผู้ดำเนินการ (ภาษาไทย)',
            'processed_by_name.regex' => 'กรุณากรอกเป็นชื่อ-นามสกุลภาษาไทยเท่านั้น เช่น "สมชาย ใจดี"',
        ]);


        $amountSatang = (int) round($data['amount'] * 100);

        try {
            $tx = $this->creditService->topup($user, $amountSatang, $request->user(), $data['note'] ?? null, $data['payment_method'], $data['processed_by_name']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        Notification::create([
            'user_id' => $user->id,
            'title' => 'เครดิตของคุณถูกเติมแล้ว',
            'message' => "แอดมินเติมเครดิตให้คุณ {$data['amount']} บาท ยอดคงเหลือปัจจุบัน " . number_format($user->fresh()->credit_balance / 100, 2) . ' บาท',
        ]);

        try {
            Mail::to($user->email)->send(new CreditTopupReceiptMail($tx));
        } catch (\Throwable $e) {
            Log::error("ส่งอีเมลใบเสร็จเติมเครดิตแบบ manual (user #{$user->id}) ไม่สำเร็จ: " . $e->getMessage());
        }

        return back()->with('success', "เติมเครดิตให้ {$user->name} จำนวน {$data['amount']} บาท สำเร็จ");
    }
}
