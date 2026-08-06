<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CreditTopupReceiptMail;
use App\Mail\CreditTopupRejectedMail;
use App\Models\CreditTopupRequest;
use App\Models\Notification;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class CreditTopupController extends Controller
{
    public function __construct(protected CreditService $creditService)
    {
    }

    /**
     * รายการคำขอเติมเครดิตทั้งหมด — pending ขึ้นก่อนเสมอ กรองตามสถานะได้
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $requests = CreditTopupRequest::with(['user', 'package', 'approver'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByRaw("field(status, 'pending', 'approved', 'rejected')")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $pendingCount = CreditTopupRequest::where('status', 'pending')->count();

        return view('admin.credit-topups.index', compact('requests', 'status', 'pendingCount'));
    }

    public function show(CreditTopupRequest $creditTopupRequest)
    {
        $creditTopupRequest->load(['user', 'package', 'approver']);

        return view('admin.credit-topups.show', ['topupRequest' => $creditTopupRequest]);
    }

    public function approve(Request $request, CreditTopupRequest $creditTopupRequest)
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $tx = $this->creditService->approveTopupRequest($creditTopupRequest, $request->user(), $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['approve' => $e->getMessage()]);
        }

        Notification::create([
            'user_id' => $creditTopupRequest->user_id,
            'title' => 'เติมเครดิตสำเร็จ',
            'message' => "คำขอเติมเครดิต #{$creditTopupRequest->id} ของคุณได้รับการอนุมัติแล้ว จำนวน "
                . number_format($creditTopupRequest->credit_satang / 100, 2) . ' บาท',
        ]);

        // ส่งใบเสร็จทางอีเมล — เครดิตเข้าบัญชีลูกค้าจริงแล้ว (commit ไปก่อนหน้านี้แล้ว) เมล์ล่มไม่ควร
        // ทำให้แอดมินเข้าใจผิดว่าการอนุมัติล้มเหลว จึงครอบ try/catch แล้ว log ไว้แทน
        try {
            Mail::to($creditTopupRequest->user->email)->send(new CreditTopupReceiptMail($tx));
        } catch (\Throwable $e) {
            Log::error("ส่งอีเมลใบเสร็จเติมเครดิต (คำขอ #{$creditTopupRequest->id}) ไม่สำเร็จ: " . $e->getMessage());
        }

        return redirect()->route('admin.credit-topups.index')
            ->with('success', "อนุมัติคำขอเติมเครดิต #{$creditTopupRequest->id} เรียบร้อยแล้ว");
    }

    public function reject(Request $request, CreditTopupRequest $creditTopupRequest)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->creditService->rejectTopupRequest($creditTopupRequest, $request->user(), $data['reason']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['reject' => $e->getMessage()]);
        }

        Notification::create([
            'user_id' => $creditTopupRequest->user_id,
            'title' => 'คำขอเติมเครดิตถูกปฏิเสธ',
            'message' => "คำขอเติมเครดิต #{$creditTopupRequest->id} ถูกปฏิเสธ เหตุผล: {$data['reason']} กรุณาติดต่อแอดมินหากมีข้อสงสัย",
        ]);

        try {
            Mail::to($creditTopupRequest->user->email)->send(new CreditTopupRejectedMail($creditTopupRequest));
        } catch (\Throwable $e) {
            Log::error("ส่งอีเมลแจ้งปฏิเสธคำขอเติมเครดิต #{$creditTopupRequest->id} ไม่สำเร็จ: " . $e->getMessage());
        }

        return redirect()->route('admin.credit-topups.index')
            ->with('success', "ปฏิเสธคำขอเติมเครดิต #{$creditTopupRequest->id} แล้ว");
    }
}
