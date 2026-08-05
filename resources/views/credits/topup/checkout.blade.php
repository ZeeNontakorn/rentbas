@extends('layouts.app')

@section('title', 'ยืนยันการเติมเครดิต')

@section('content')
<div class="bg-white min-h-screen text-[#111827]">
<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');
.tu-main { font-family: 'Sarabun', 'Kanit', sans-serif; }
.tu-main h1, .tu-main h2, .tu-main h3 { font-family: 'Kanit', sans-serif; }

.tu-card { border: 1px solid #e5e7eb; border-radius: 14px; padding: 26px; }
.tu-qr-box { width: 200px; height: 200px; margin: 0 auto 14px; padding: 10px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; }
.tu-qr-box canvas { width: 100% !important; height: 100% !important; }

.method-option {
    display: flex; align-items: center; gap: 10px; border: 1.5px solid #e5e7eb; border-radius: 10px;
    padding: 12px 14px; cursor: pointer; transition: all .15s; font-size: 13.5px;
}
.method-option:hover { border-color: #87D068; }
.method-option input { accent-color: #87D068; width: 16px; height: 16px; }
.method-option.active { border-color: #87D068; background: #f7fdf4; }

.tu-input {
    width: 100%; padding: 10px 14px; border-radius: 10px; border: 1px solid #d1d5db; font-size: 13.5px;
    outline: none; transition: all .15s;
}
.tu-input:focus { border-color: #87D068; box-shadow: 0 0 0 3px rgba(135,208,104,0.15); }
.tu-input.invalid { border-color: #ef4444; }

.tu-upload {
    border: 2px dashed #d1d5db; border-radius: 12px; padding: 22px; text-align: center; color: #6b7280;
    font-size: 13px; cursor: pointer; transition: all .15s;
}
.tu-upload:hover { border-color: #87D068; color: #4a8f2c; }
.tu-upload.has-file { border-color: #87D068; background: #f7fdf4; color: #4a8f2c; }
</style>

<div class="tu-main max-w-[520px] mx-auto px-4 py-10" data-aos="fade-up">

    @include('components.mail-loading-overlay')

    <a href="{{ route('credits.topup.index') }}" class="text-[13px] text-gray-500 hover:text-gray-800 mb-4 inline-block">← เลือกแพ็กเกจใหม่</a>
    <h1 class="text-[26px] font-bold text-gray-900 mb-1">ยืนยันการเติมเครดิต</h1>
    <p class="text-gray-500 text-[14px] mb-6">ยอดชำระ <span class="font-bold text-gray-800">฿{{ number_format($priceSatang / 100, 2) }}</span> · รับเครดิต <span class="font-bold text-emerald-600">฿{{ number_format($creditSatang / 100, 2) }}</span></p>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">
            @foreach ($errors->all() as $err)<div>• {{ $err }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('credits.topup.store') }}" enctype="multipart/form-data" id="topupForm" class="flex flex-col gap-6"
          onsubmit="showMailLoadingOverlay('กำลังส่งคำขอเติมเครดิตและแจ้งเตือนแอดมิน...'); document.getElementById('submitBtn').disabled = true;">
        @csrf
        <input type="hidden" name="package_id" value="{{ $package?->id }}">
        <input type="hidden" name="price_satang" value="{{ $priceSatang }}">
        <input type="hidden" name="credit_satang" value="{{ $creditSatang }}">

        {{-- ช่องทางชำระเงิน --}}
        <div>
                    <span>สแกน QR PromptPay แล้วแนบสลิป</span>
        </div>

        {{-- QR PromptPay (mock) --}}
        <div id="promptpayPanel" class="tu-card text-center">
            <div class="tu-qr-box"><canvas id="qrCanvas"></canvas></div>
            <p class="text-sm text-gray-600 mb-0.5">พร้อมเพย์: <span class="font-bold text-gray-900">099-999-9999</span></p>
            <p class="text-sm text-gray-600 mb-4">ชื่อบัญชี: <span class="font-bold text-gray-900">THATA HOMECOURT</span></p>

            <label for="slipInput" class="tu-upload block" id="uploadLabel">
                📎 แตะเพื่อแนบสลิปการโอนเงิน
            </label>
            <input type="file" name="slip" id="slipInput" accept="image/*" class="hidden" onchange="onSlipChange(this)">
        </div>
        <button type="submit" id="submitBtn" class="w-full bg-[#87D068] hover:bg-[#76bc5a] text-white font-bold py-3.5 rounded-lg shadow transition" style="font-family:'Kanit',sans-serif;">
            ส่งคำขอเติมเครดิต
        </button>
    </form>
</div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    // mock QR payload — ไม่ใช่ QR PromptPay จริง แค่ตัวอย่างสำหรับ mockup หน้าจอ
    const payload = 'MOCK-TOPUP|amount={{ $priceSatang }}|ref={{ now()->timestamp }}';
    new QRCode(document.getElementById('qrCanvas'), {
        text: payload, width: 180, height: 180, colorDark: '#111827', colorLight: '#ffffff'
    });
})();

function onSlipChange(input) {
    const label = document.getElementById('uploadLabel');
    if (input.files && input.files[0]) {
        label.textContent = '✅ แนบไฟล์แล้ว: ' + input.files[0].name;
        label.classList.add('has-file');
    } else {
        label.textContent = '📎 แตะเพื่อแนบสลิปการโอนเงิน';
        label.classList.remove('has-file');
    }
}
</script>
@endpush
@endsection
