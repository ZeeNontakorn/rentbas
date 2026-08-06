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
.pay-option.wip-option { opacity: 0.75; }
.pay-badge {
    display: inline-block; font-family: 'Kanit', sans-serif; font-size: 11px; font-weight: 700;
    padding: 2px 10px; border-radius: 999px;
}
.pay-badge.ready { background: #ecfdf3; color: #1c7a3d; }
.pay-badge.wip { background: #f3f4f6; color: #6b7280; }

.btn-pay {
    font-family: 'Kanit', sans-serif; font-weight: 700; font-size: 14px; border-radius: 10px; padding: 12px 20px;
    text-align: center; transition: all .15s; border: none; cursor: pointer; width: 100%;
}
.btn-pay.credit { background: #87D068; color: #fff; }
.btn-pay.credit:hover { background: #76bc5a; }
.btn-pay.credit:disabled { background: #e5e7eb; color: #9ca3af; cursor: not-allowed; }
.btn-pay.promptpay { background: #0b0b1a; color: #fff; }
.btn-pay.promptpay:hover { background: #1a1a2e; }
</style>

<div class="co-main max-w-[880px] mx-auto px-4 py-10" data-aos="fade-up">

    <div class="mb-6">
        <h1 class="text-[28px] font-bold text-gray-900">ยืนยันการชำระเงิน</h1>
        <p class="text-gray-500 text-sm mt-1">ระบบล็อกช่วงเวลานี้ไว้ให้คุณชั่วคราว กรุณาชำระเงินให้เสร็จภายในเวลาที่กำหนด</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">
            @foreach ($errors->all() as $err)
                <div>• {{ $err }}</div>
            @endforeach
        </div>
    @endif

    {{-- Countdown --}}
    <div id="coTimer" class="co-timer mb-6" data-locked-until="{{ $booking->locked_until?->toIso8601String() }}">
        <div class="flex items-center gap-2 text-sm font-medium text-orange-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>เหลือเวลาชำระเงินอีก</span>
        </div>
        <span class="clock" id="coClock">15:00</span>
    </div>

    <div class="grid md:grid-cols-5 gap-6">

        {{-- LEFT: Summary + Breakdown --}}
        <div class="md:col-span-3 flex flex-col gap-6">

            <div class="co-card">
                <h2 class="text-[16px] font-bold text-gray-900 mb-4">รายละเอียดการจอง</h2>
                @php
                    $thMonthsFull = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
                    $bd = $booking->booking_date;
                @endphp
                <div class="co-row">
                    <span class="k">สนาม</span>
                    <span class="v">
                        {{ $booking->court->name }}
                        @if($booking->courtSection && $booking->courtSection->code !== 'full')
                            <span class="text-gray-400 font-normal">({{ $booking->courtSection->name }})</span>
                        @endif
                    </span>
                </div>
                <div class="co-row">
                    <span class="k">วันที่</span>
                    <span class="v">{{ $bd->day }} {{ $thMonthsFull[$bd->month] }} {{ $bd->year + 543 }}</span>
                </div>
                <div class="co-row">
                    <span class="k">เวลา</span>
                    <span class="v">{{ substr($booking->start_time, 0, 5) }} - {{ substr($booking->end_time, 0, 5) }} น.</span>
                </div>
                @if($booking->promotionPackage)
                    <div class="co-row">
                        <span class="k">แพ็กเกจ</span>
                        <span class="v">{{ $booking->promotionPackage->label }}</span>
                    </div>
                @endif
                <div class="co-row">
                    <span class="k">หมายเลขการจอง</span>
                    <span class="v">#{{ str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}</span>
                </div>
            </div>

            <div class="co-card">
                <h2 class="text-[16px] font-bold text-gray-900 mb-4">รายละเอียดราคา</h2>

                @forelse ($priceResult['breakdown'] ?? [] as $item)
                    <div class="co-row">
                        <span class="k">
                            {{ $item['label'] }}
                            @if(!empty($item['minutes']))
                                <span class="text-gray-400">({{ (int) $item['minutes'] }} นาที)</span>
                            @endif
                        </span>
                        <span class="v">฿{{ number_format($item['price'] / 100, 0) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">ไม่มีรายละเอียดย่อย</p>
                @endforelse

                <div class="co-row" style="border-top: 2px solid #111827; margin-top: 6px; padding-top: 14px;">
                    <span class="k font-bold text-gray-900" style="font-size:15px;">ยอดชำระทั้งหมด</span>
                    <span class="v" style="font-size:20px; color:#87D068;">฿{{ number_format($booking->price / 100, 0) }}</span>
                </div>
            </div>
        </div>

        {{-- RIGHT: Payment options --}}
        <div class="md:col-span-2 flex flex-col gap-5">

            {{-- Option A: Credit --}}
            @php
                $balance = (float) auth()->user()->credit_balance;
                $price = (float) $booking->price;
                $sufficient = $balance >= $price;
            @endphp
            <div class="pay-option active-option">
                <div class="flex items-center justify-between">
                    <h3 class="text-[15px] font-bold text-gray-900">ชำระด้วยเครดิต</h3>
                    <span class="pay-badge ready">พร้อมใช้งาน</span>
                </div>
                <p class="text-xs text-gray-500">หักจากยอดเครดิตคงเหลือของคุณทันที และอนุมัติการจองอัตโนมัติ ไม่ต้องรอแอดมิน</p>

                <div class="co-row" style="padding: 6px 0;">
                    <span class="k">ยอดเครดิตปัจจุบัน</span>
                    <span class="v">฿{{ number_format($balance / 100, 0) }}</span>
                </div>
                <div class="co-row" style="padding: 6px 0;">
                    <span class="k">ยอดชำระ</span>
                    <span class="v">฿-{{number_format($price / 100, 0) }}</span>
                </div>
                <div class="co-row" style="padding: 6px 0;">
                    <span class="k">ยอดเครดิตคงเหลือ</span>
                    <span class="v">฿{{number_format(($balance - $price) / 100, 0) }}</span>
                </div>

                @if(!$sufficient)
                    <div class="text-xs bg-red-50 text-red-600 border border-red-200 rounded-lg px-3 py-2">
                        เครดิตไม่เพียงพอ (ขาดอีก ฿{{ number_format(($price - $balance) / 100, 0) }}) กรุณาติดต่อแอดมินเพื่อเติมเครดิต หรือเลือกชำระด้วย QR PromptPay แทน
                    </div>
                @endif

                <form method="POST" action="{{ route('checkout.pay.credit', $booking) }}" data-credit-payment-form>
                    @csrf
                    <button type="submit" class="btn-pay credit" {{ $sufficient ? '' : 'disabled' }}>
                        ชำระด้วยเครดิต ฿{{ number_format($price / 100, 0) }}
                    </button>
                </form>
            </div>

            {{-- Option B: QR PromptPay [WIP] --}}
            <div class="pay-option wip-option">
                <div class="flex items-center justify-between">
                    <h3 class="text-[15px] font-bold text-gray-900">QR PromptPay</h3>
                    <span class="pay-badge wip">กำลังพัฒนา</span>
                </div>
                <p class="text-xs text-gray-500">สแกนจ่ายผ่าน PromptPay แล้วแนบสลิป — ตอนนี้ยังเป็นเวอร์ชันทดลอง แอดมินจะตรวจสลิปด้วยมือ (ยังไม่ตรวจอัตโนมัติ)</p>

                <form method="POST" action="{{ route('checkout.pay.promptpay', $booking) }}">
                    @csrf
                    <button type="submit" class="btn-pay promptpay">
                        ดำเนินการต่อด้วย QR PromptPay
                    </button>
                </form>
            </div>

            <p class="text-xs text-gray-400 text-center px-2">
                หากไม่ชำระเงินภายในเวลาที่กำหนด ระบบจะยกเลิกรายการนี้อัตโนมัติ และคืนช่วงเวลาให้ผู้อื่นจองได้ทันที
            </p>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
document.querySelector('[data-credit-payment-form]')?.addEventListener('submit', function () {
    const button = this.querySelector('button[type="submit"]');
    if (!button || button.disabled) return;

    button.disabled = true;
    button.textContent = 'กำลังชำระเงิน...';
});

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
            clockEl.closest('.co-timer').querySelector('span').textContent = 'หมดเวลาแล้ว กำลังกลับไปหน้าจองสนาม...';
            setTimeout(() => { window.location.href = "{{ route('booking.index') }}"; }, 1500);
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
