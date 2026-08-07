@extends('layouts.app')

@section('title', 'คำขอเติมเครดิต #' . $topupRequest->id)

@php
    $statusMeta = [
        'pending' => ['label' => 'รอตรวจสอบ', 'bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
        'approved' => ['label' => 'อนุมัติแล้ว', 'bg' => 'bg-emerald-100', 'text' => 'text-emerald-700'],
        'rejected' => ['label' => 'ปฏิเสธแล้ว', 'bg' => 'bg-red-100', 'text' => 'text-red-600'],
    ];
    $meta = $statusMeta[$topupRequest->status];
@endphp

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-6 max-w-3xl">

        @include('components.mail-loading-overlay')

        <a href="{{ route('admin.credit-topups.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-orange-500 mb-6 transition font-medium group">
            <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            กลับไปหน้ารายการคำขอ
        </a>

        @if (session('success'))
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">
                @foreach ($errors->all() as $err)<div>• {{ $err }}</div>@endforeach
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-5">
                <h1 class="font-semibold text-gray-800 text-lg">คำขอเติมเครดิต #{{ $topupRequest->id }}</h1>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $meta['bg'] }} {{ $meta['text'] }}">{{ $meta['label'] }}</span>
            </div>

            <div class="grid sm:grid-cols-2 gap-x-6 gap-y-4 text-sm mb-6">
                <div>
                    <p class="text-xs text-gray-400 mb-1">ผู้ใช้</p>
                    <p class="font-medium text-gray-800">{{ $topupRequest->user->name }}</p>
                    <p class="text-xs text-gray-400">{{ $topupRequest->user->email }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">เติมโดย (ชื่อ-นามสกุลที่แจ้ง)</p>
                    <p class="font-medium text-gray-800">{{ $topupRequest->topper_name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">ยอดชำระ / เครดิตที่ได้รับ</p>
                    <p class="font-medium text-gray-800">฿{{ number_format($topupRequest->price_satang / 100, 2) }} → <span class="text-emerald-600">฿{{ number_format($topupRequest->credit_satang / 100, 2) }}</span></p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">ช่องทางการชำระเงิน</p>
                    <p class="font-medium text-gray-800">{{ $topupRequest->payment_method_label }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">วันที่ยื่นคำขอ</p>
                    <p class="font-medium text-gray-800">{{ $topupRequest->created_at->format('d/m/Y H:i') }}</p>
                </div>
                @if($topupRequest->approver)
                    <div>
                        <p class="text-xs text-gray-400 mb-1">ดำเนินการโดย</p>
                        <p class="font-medium text-gray-800">{{ $topupRequest->approver->name }} · {{ $topupRequest->approved_at?->format('d/m/Y H:i') }}</p>
                    </div>
                @endif
            </div>

            @if($topupRequest->status === 'rejected' && $topupRequest->rejected_reason)
                <div class="mb-6 p-3 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">เหตุผลที่ปฏิเสธ: {{ $topupRequest->rejected_reason }}</div>
            @endif

            {{-- สลิป --}}
            <div class="mb-6">
                <p class="text-xs text-gray-400 mb-2">สลิปการโอนเงิน</p>
                @if($topupRequest->slip_url)
                    <a href="{{ $topupRequest->slip_url }}" target="_blank" rel="noopener">
                        <img src="{{ $topupRequest->slip_url }}" alt="สลิป" class="max-w-[280px] rounded-lg border border-gray-200">
                    </a>
                @else
                    <p class="text-sm text-gray-400">ไม่มีสลิปแนบมา (ช่องทาง {{ $topupRequest->payment_method_label }})</p>
                @endif
            </div>

            @if($topupRequest->status === 'pending')
                <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-100">
                    <form method="POST" action="{{ route('admin.credit-topups.approve', $topupRequest) }}" class="flex-1"
                          onsubmit="showMailLoadingOverlay('กำลังอนุมัติและส่งอีเมลใบเสร็จให้ลูกค้า...'); this.querySelector('button').disabled = true;">
                        @csrf
                        <button type="submit" class="w-full text-sm font-medium text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg px-5 py-2.5 transition">
                            ✓ อนุมัติและเติมเครดิต
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.credit-topups.reject', $topupRequest) }}" class="flex-1 flex gap-2" onsubmit="return promptRejectReason(this)">
                        @csrf
                        <input type="hidden" name="reason" class="reject-reason-input">
                        <button type="submit" class="w-full text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg px-5 py-2.5 transition">
                            ✕ ปฏิเสธคำขอ
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal ปฏิเสธคำขอเติมเครดิต --}}
<div id="creditRejectModal" class="fixed inset-0 z-[70] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="creditRejectModalTitle">
    <button type="button" class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" aria-label="ปิดหน้าต่าง" onclick="closeCreditRejectModal()"></button>

    <div class="relative w-full max-w-md overflow-hidden rounded-2xl border border-white/70 bg-white shadow-2xl">
        <div class="flex items-start gap-4 border-b border-red-100 bg-gradient-to-br from-red-50 to-orange-50 px-6 py-5">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h2 id="creditRejectModalTitle" class="text-lg font-bold text-gray-900">ปฏิเสธคำขอเติมเครดิต</h2>
                <p class="mt-1 text-sm leading-6 text-gray-500">ระบุเหตุผลเพื่อแจ้งให้ลูกค้าทราบก่อนยืนยันการปฏิเสธ</p>
            </div>
            <button type="button" onclick="closeCreditRejectModal()" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-white hover:text-gray-700" aria-label="ปิดหน้าต่าง">
                <span class="text-2xl font-light leading-none">×</span>
            </button>
        </div>

        <form class="space-y-5 px-6 py-6" onsubmit="submitCreditRejection(event)">
            <div>
                <label for="creditRejectReason" class="mb-2 block text-sm font-semibold text-gray-700">เหตุผลที่ปฏิเสธ <span class="text-red-500">*</span></label>
                <textarea id="creditRejectReason" rows="4" maxlength="255" required
                    class="w-full resize-none rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 text-sm leading-6 text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-red-400 focus:bg-white focus:ring-4 focus:ring-red-100"
                    placeholder="เช่น ยอดเงินไม่ตรงกับคำขอ หรือไม่พบรายการโอนเงิน"></textarea>
                <div class="mt-2 flex items-center justify-between gap-3">
                    <p id="creditRejectError" class="hidden text-xs font-medium text-red-600">กรุณาระบุเหตุผลที่ปฏิเสธ</p>
                    <p class="ml-auto text-xs text-gray-400"><span id="creditRejectCount">0</span>/255</p>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" onclick="closeCreditRejectModal()" class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">
                    ยกเลิก
                </button>
                <button type="submit" class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-red-200 transition hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-100">
                    ยืนยันการปฏิเสธ
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let pendingCreditRejectForm = null;

function promptRejectReason(form) {
    pendingCreditRejectForm = form;

    const modal = document.getElementById('creditRejectModal');
    const reasonInput = document.getElementById('creditRejectReason');
    reasonInput.value = '';
    updateCreditRejectCount();
    document.getElementById('creditRejectError').classList.add('hidden');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
    window.setTimeout(() => reasonInput.focus(), 50);

    return false;
}

function closeCreditRejectModal() {
    const modal = document.getElementById('creditRejectModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
    pendingCreditRejectForm = null;
}

function submitCreditRejection(event) {
    event.preventDefault();

    const reasonInput = document.getElementById('creditRejectReason');
    const reason = reasonInput.value.trim();
    if (!reason || !pendingCreditRejectForm) {
        document.getElementById('creditRejectError').classList.remove('hidden');
        reasonInput.focus();
        return;
    }

    const form = pendingCreditRejectForm;
    form.querySelector('.reject-reason-input').value = reason;
    showMailLoadingOverlay('กำลังปฏิเสธคำขอและส่งอีเมลแจ้งลูกค้า...');
    form.querySelector('button').disabled = true;
    form.submit();
}

function updateCreditRejectCount() {
    const reasonInput = document.getElementById('creditRejectReason');
    document.getElementById('creditRejectCount').textContent = reasonInput.value.length;
    if (reasonInput.value.trim()) {
        document.getElementById('creditRejectError').classList.add('hidden');
    }
}

document.getElementById('creditRejectReason').addEventListener('input', updateCreditRejectCount);
document.addEventListener('keydown', event => {
    const modal = document.getElementById('creditRejectModal');
    if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
        closeCreditRejectModal();
    }
});
</script>
@endpush
@endsection
