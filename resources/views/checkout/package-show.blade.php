@extends('layouts.app')

@section('title', 'ยืนยันการชำระเงิน')

@section('content')
<div class="bg-white min-h-screen text-[#111827]">
<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');
.co-main { font-family: 'Sarabun', 'Kanit', sans-serif; }
.co-main h1, .co-main h2, .co-main h3 { font-family: 'Kanit', sans-serif; }

.co-timer {
    background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px;
    padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.co-timer.danger { background: #fef2f2; border-color: #fecaca; }
.co-timer .clock { font-family: 'Kanit', sans-serif; font-weight: 800; font-size: 22px; color: #b45309; letter-spacing: 1px; }
.co-timer.danger .clock { color: #b91c1c; }

.co-card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 22px; }

.co-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f3f5; font-size: 14px; }
.co-row:last-child { border-bottom: none; }
.co-row .k { color: #6b7280; }
.co-row .v { font-weight: 700; color: #111827; text-align: right; }

.pay-option {
    border: 2px solid #e5e7eb; border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 10px;
    transition: all .15s;
}
.pay-option.active-option { border-color: #87D068; }
.pay-badge {
    display: inline-block; font-family: 'Kanit', sans-serif; font-size: 11px; font-weight: 700;
    padding: 2px 10px; border-radius: 999px;
}
.pay-badge.ready { background: #ecfdf3; color: #1c7a3d; }

.btn-pay {
    font-family: 'Kanit', sans-serif; font-weight: 700; font-size: 14px; border-radius: 10px; padding: 12px 20px;
    text-align: center; transition: all .15s; border: none; cursor: pointer; width: 100%;
}
.btn-pay.credit { background: #87D068; color: #fff; }
.btn-pay.credit:hover { background: #76bc5a; }
.btn-pay.credit:disabled { background: #e5e7eb; color: #9ca3af; cursor: not-allowed; }
</style>

<div class="co-main max-w-[880px] mx-auto px-4 py-10" data-aos="fade-up">

    <div class="mb-6">
        <h1 class="text-[28px] font-bold text-gray-900">ยืนยันการชำระเงิน</h1>
        <p class="text-gray-500 text-sm mt-1">ระบบล็อกราคาแพ็กเกจนี้ไว้ให้คุณชั่วคราว กรุณาชำระเงินให้เสร็จภายในเวลาที่กำหนด</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">
            @foreach ($errors->all() as $err)
                <div>• {{ $err }}</div>
            @endforeach
        </div>
    @endif



    <div class="grid md:grid-cols-5 gap-6">

        {{-- LEFT: Package summary --}}
        <div class="md:col-span-3 flex flex-col gap-6">
            <div class="co-card">
    <h2 class="text-[16px] font-bold text-gray-900 mb-4">รายละเอียดแพ็กเกจ</h2>
    <div class="co-row">
        <span class="k">แพ็กเกจ</span>
        <span class="v">{{ $purchase->package->name }}</span>
    </div>
    @if($purchase->package->description)
    <div class="co-row">
        <span class="k">รายละเอียด</span>
        <span class="v">{{ $purchase->package->description }}</span>
    </div>
    @endif
    <div class="co-row">
        <span class="k">จำนวนครั้งที่ใช้ได้</span>
        <span class="v">{{ $purchase->package->num_of_use }} ครั้ง</span>
    </div>
    <div class="co-row">
        <span class="k">หมายเลขรายการ</span>
        <span class="v">#{{ str_pad($purchase->id, 6, '0', STR_PAD_LEFT) }}</span>
    </div>
</div>

            <div class="co-card">
                <h2 class="text-[16px] font-bold text-gray-900 mb-4">รายละเอียดราคา</h2>
                <div class="co-row" style="border-top: 2px solid #111827; margin-top: 6px; padding-top: 14px;">
                    <span class="k font-bold text-gray-900" style="font-size:15px;">ยอดชำระทั้งหมด</span>
                    <span class="v" style="font-size:20px; color:#87D068;">฿{{ number_format($purchase->price / 100, 0) }}</span>
                </div>
            </div>
        </div>

        {{-- RIGHT: Payment --}}
        <div class="md:col-span-2 flex flex-col gap-5">
            @php
                $balance = (float) auth()->user()->credit_balance;
                $price = (float) $purchase->price;
                $sufficient = $balance >= $price;
            @endphp
            <div class="pay-option active-option">
                <div class="flex items-center justify-between">
                    <h3 class="text-[15px] font-bold text-gray-900">ชำระด้วยเครดิต</h3>
                    <span class="pay-badge ready">พร้อมใช้งาน</span>
                </div>
                <p class="text-xs text-gray-500">หักจากยอดเครดิตคงเหลือของคุณทันที และยืนยันการซื้อแพ็กเกจอัตโนมัติ</p>

                <div class="co-row" style="padding: 6px 0;">
                    <span class="k">ยอดเครดิตปัจจุบัน</span>
                    <span class="v">฿{{ number_format($balance / 100, 0) }}</span>
                </div>
                <div class="co-row" style="padding: 6px 0;">
                    <span class="k">ยอดชำระ</span>
                    <span class="v">฿-{{ number_format($price / 100, 0) }}</span>
                </div>
                <div class="co-row" style="padding: 6px 0;">
                    <span class="k">ยอดเครดิตคงเหลือ</span>
                    <span class="v">฿{{ number_format(($balance - $price) / 100, 0) }}</span>
                </div>

                @if(!$sufficient)
                    <div class="text-xs bg-red-50 text-red-600 border border-red-200 rounded-lg px-3 py-2">
                        เครดิตไม่เพียงพอ (ขาดอีก ฿{{ number_format(($price - $balance) / 100, 0) }}) กรุณาติดต่อแอดมินเพื่อเติมเครดิต
                    </div>
                @endif

                <form method="POST" action="{{ route('package-checkout.pay.credit', $purchase) }}">
                    @csrf
                    <button type="submit" class="btn-pay credit" {{ $sufficient ? '' : 'disabled' }}>
                        ชำระด้วยเครดิต ฿{{ number_format($price / 100, 0) }}
                    </button>
                </form>
            </div>

            <p class="text-xs text-gray-400 text-center px-2">
                หากไม่ชำระเงินภายในเวลาที่กำหนด ระบบจะยกเลิกรายการนี้อัตโนมัติ
            </p>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
(function () {
    const timerBox = document.getElementById('coTimer');
    const clockEl = document.getElementById('coClock');
    const lockedUntilStr = timerBox.dataset.lockedUntil;
    if (!lockedUntilStr) return;

    const lockedUntil = new Date(lockedUntilStr).getTime();

    function tick() {
        const remainMs = lockedUntil - Date.now();
        if (remainMs <= 0) {
            clockEl.textContent = '00:00';
            timerBox.classList.add('danger');
            clockEl.closest('.co-timer').querySelector('span').textContent = 'หมดเวลาแล้ว กำลังกลับไปหน้าแรก...';
            setTimeout(() => { window.location.href = "{{ route('home') }}"; }, 1500);
            clearInterval(interval);
            return;
        }

        const totalSec = Math.floor(remainMs / 1000);
        const m = Math.floor(totalSec / 60);
        const s = totalSec % 60;
        clockEl.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');

        if (totalSec <= 120) {
            timerBox.classList.add('danger');
        }
    }

    tick();
    const interval = setInterval(tick, 1000);
})();
</script>
@endpush
@endsection