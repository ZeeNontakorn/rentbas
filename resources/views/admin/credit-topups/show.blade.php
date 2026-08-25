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
    <div class="container mx-auto px-4 sm:px-6 max-w-7xl">

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
                    <p class="font-medium text-gray-800">{{ $topupRequest->user->us_name }}</p>
                    <p class="text-xs text-gray-400">{{ $topupRequest->user->email }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">เติมโดย</p>
                    <p class="font-medium text-gray-800">{{ $topupRequest->user->name }}</p>
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
                        <p class="font-medium text-gray-800">{{ $topupRequest->approver->us_name }} · {{ $topupRequest->approved_at?->format('d/m/Y H:i') }}</p>
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
                        @if ($topupRequest->expiry_days)
                            <p class="text-xs text-gray-400 mb-2">เครดิตนี้จะหมดอายุใน {{ $topupRequest->expiry_days }} วัน (ตามแพ็กเกจที่เลือก)</p>
                        @else
                            <div class="mb-3">
                                <label class="block text-xs font-medium text-gray-500 mb-1">
                                    คำขอนี้ไม่ได้ผูกกับแพ็กเกจที่มีวันหมดอายุ — กรุณาระบุจำนวนวันหมดอายุของเครดิต
                                </label>
                                <input type="number" step="1" min="1" max="365" name="expiry_days" required placeholder="เช่น 365"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
                            </div>
                        @endif
                        <button type="submit" class="w-full text-sm font-medium text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg px-5 py-2.5 cursor-pointer transition">
                            อนุมัติและเติมเครดิต
                        </button>
                    </form>

                    <button type="button" onclick="openRejectTopupModal()" class="flex-1 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg px-5 py-2.5 cursor-pointer transition">
                            ปฏิเสธคำขอ
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<div id="rejectTopupModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-lg overflow-hidden rounded-3xl border border-red-100 bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-red-50 bg-gradient-to-r from-red-50 to-white px-6 py-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-red-400">Credit topup rejection</p>
                <h3 class="mt-1 text-lg font-bold text-gray-900">ปฏิเสธคำขอเติมเครดิต #{{ $topupRequest->id }}</h3>
                <p class="mt-1 text-sm text-gray-500">ระบุเหตุผลสั้นๆ เพื่อส่งแจ้งลูกค้าและบันทึกไว้ในระบบ</p>
            </div>
            <button type="button" onclick="closeRejectTopupModal()" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="ปิด modal">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.credit-topups.reject', $topupRequest) }}" class="p-6" onsubmit="return submitRejectTopup(this)">
            @csrf
            <input type="hidden" name="reason" class="reject-reason-input">

            <div class="rounded-2xl border border-gray-100 bg-slate-50 p-4">
                <p class="text-xs font-semibold text-gray-400">ข้อมูลคำขอ</p>
                <div class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-gray-400">ผู้ใช้</p>
                        <p class="font-medium text-gray-800">{{ $topupRequest->user->us_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">ยอดชำระ</p>
                        <p class="font-medium text-gray-800">฿{{ number_format($topupRequest->price_satang / 100, 2) }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <label for="rejectTopupReason" class="mb-1.5 block text-sm font-semibold text-gray-800">เหตุผลที่ปฏิเสธ <span class="text-red-500">*</span></label>
                <textarea id="rejectTopupReason" rows="4" maxlength="500" required
                    placeholder="เช่น ยอดเงินไม่ตรงกับสลิป / ไม่พบรายการโอน / ชื่อผู้โอนไม่ตรงกับคำขอ"
                    class="w-full resize-none rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-red-400 focus:ring-4 focus:ring-red-100"></textarea>
                <p class="mt-2 text-xs text-gray-400">ระบบจะส่งเหตุผลนี้ไปยังอีเมลของลูกค้า และเก็บไว้เป็นบันทึกคำขอ</p>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" onclick="closeRejectTopupModal()" class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50 cursor-pointer">
                    ยกเลิก
                </button>
                <button type="submit" class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 cursor-pointer">
                    ปฏิเสธและส่งอีเมลแจ้งลูกค้า
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const rejectTopupModal = document.getElementById('rejectTopupModal');
const rejectTopupReason = document.getElementById('rejectTopupReason');

function openRejectTopupModal() {
    if (!rejectTopupModal || !rejectTopupReason) return;
    rejectTopupModal.classList.remove('hidden');
    rejectTopupModal.classList.add('flex');
    rejectTopupReason.value = '';
    window.requestAnimationFrame(() => rejectTopupReason.focus());
}

function closeRejectTopupModal() {
    if (!rejectTopupModal || !rejectTopupReason) return;
    rejectTopupModal.classList.add('hidden');
    rejectTopupModal.classList.remove('flex');
    rejectTopupReason.value = '';
}

function submitRejectTopup(form) {
    const reasonValue = rejectTopupReason ? rejectTopupReason.value.trim() : '';
    if (!reasonValue) {
        rejectTopupReason?.focus();
        rejectTopupReason?.setCustomValidity('กรุณาระบุเหตุผล');
        rejectTopupReason?.reportValidity();
        rejectTopupReason?.setCustomValidity('');
        return false;
    }

    form.querySelector('.reject-reason-input').value = reasonValue;
    showMailLoadingOverlay('กำลังปฏิเสธคำขอและส่งอีเมลแจ้งลูกค้า...');
    form.querySelector('button[type="submit"]').disabled = true;
    return true;
}

document.addEventListener('click', (event) => {
    if (!rejectTopupModal || !event.target) return;
    if (event.target === rejectTopupModal) {
        closeRejectTopupModal();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && rejectTopupModal && !rejectTopupModal.classList.contains('hidden')) {
        closeRejectTopupModal();
    }
});
</script>
@endpush
@endsection
