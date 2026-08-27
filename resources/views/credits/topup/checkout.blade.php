@extends('layouts.app')

@section('title', 'ยืนยันการเติมเครดิต')

@section('content')
<div class="bg-white min-h-screen text-[#111827]">
<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');
.tu-main { font-family: 'Sarabun', 'Kanit', sans-serif; }
.tu-main h1, .tu-main h2, .tu-main h3 { font-family: 'Kanit', sans-serif; }

.tu-card { border: 1px solid #e5e7eb; border-radius: 14px; padding: 26px; }

/* ปรับ tu-qr-box ให้รองรับการวางโลโก้ทับตรงกลาง */
.tu-qr-box {
    position: relative;
    width: 200px; height: 200px; margin: 0 auto 14px; padding: 10px;
    background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
    display: flex; justify-content: center; align-items: center;
}
.tu-qr-box canvas { width: 100% !important; height: 100% !important; }

/* CSS สำหรับโลโก้พร้อมเพย์ตรงกลาง */
.qr-logo-overlay {
    position: absolute;
    width: 45px; /* ขนาดโลโก้ตรงกลาง (ห้ามใหญ่เกินไปเดี๋ยวสแกนไม่ได้) */
    height: auto;
    background-color: transparent;
    padding: 0;
    border-radius: 8px;
}

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

@include('components.mail-loading-overlay')

<div class="tu-main max-w-[520px] mx-auto px-4 py-10" data-aos="fade-up">



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

        {{-- QR PromptPay --}}
        <div id="promptpayPanel" class="tu-card text-center">

            {{-- พื้นที่แสดง QR Code พร้อมโลโก้ตรงกลาง --}}
            <div class="tu-qr-box">
                <canvas id="qrCanvas"></canvas>
                <!-- โลโก้พร้อมเพย์ที่จะแสดงทับตรงกลาง -->
                <img src="{{ Storage::disk('public')->url('icon-thaiqr.png') }}" class="qr-logo-overlay" alt="PromptPay">
            </div>

            {{-- จัดรูปแบบเบอร์ให้สวยงาม (ถ้าเป็นเบอร์โทรให้ใส่ขีดดเพื่อให้ดูง่าย) --}}
            <p class="text-sm text-gray-600 mb-0.5">พร้อมเพย์: <span class="font-bold text-gray-900">{{ $promptpayNumber ?? 'ยังไม่ได้ตั้งค่าเบอร์' }}</span></p>

            {{-- แนะนำให้ส่ง $promptpayName มาจาก Controller ด้วยวิธีเดียวกับเบอร์ครับ --}}
            <p class="text-sm text-gray-600 mb-4">ชื่อบัญชี: <span class="font-bold text-gray-900">{{ $promptpayName ?? 'THATA HOMECOURT' }}</span></p>

            <label for="slipInput" class="tu-upload block relative" id="uploadLabel">
                <span id="uploadText">📎 แตะเพื่อแนบสลิปการโอนเงิน</span>

                {{-- ส่วนสำหรับแสดง Preview รูปภาพ (ถูกซ่อนไว้เป็นค่าเริ่มต้น) --}}
                <img id="slipPreview" src="" alt="Slip Preview" class="hidden mt-3 mx-auto max-h-[300px] w-auto object-contain rounded-lg shadow-sm border border-gray-200">
            </label>
            <input type="file" name="slip" id="slipInput" accept="image/*" class="hidden" onchange="onSlipChange(this)">
        </div>
        <button type="submit" id="submitBtn" class="w-full bg-[#87D068] hover:bg-[#76bc5a] text-white font-bold py-3.5 rounded-lg shadow transition" style="font-family:'Kanit',sans-serif; cursor:pointer;">
            ส่งคำขอเติมเครดิต
        </button>
    </form>
</div>
</div>

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function() {
    // กำหนดข้อมูล
    const mobileNumber = '{{ $promptpayNumber ?? "" }}';
    const amount = {{ $priceSatang / 100 }}; // แปลงสตางค์เป็นบาท
    const canvas = document.getElementById('qrCanvas');

    // ตรวจสอบว่ามีเบอร์หรือไม่ และเรียกใช้ฟังก์ชันที่ Bundle มาจาก Vite
    if (mobileNumber && typeof window.generatePromptPayQR === 'function') {
        window.generatePromptPayQR(mobileNumber, amount, canvas);
    }
});

function onSlipChange(input) {
    const label = document.getElementById('uploadLabel');
    const textSpan = document.getElementById('uploadText');
    const previewImg = document.getElementById('slipPreview');

    if (input.files && input.files[0]) {
        const file = input.files[0];

        // 1. เปลี่ยนข้อความแจ้งเตือน
        textSpan.innerHTML = `✅ แนบไฟล์แล้ว: <span class="font-semibold">${file.name}</span> <br><span class="text-xs text-gray-500">(แตะที่รูปเพื่อเปลี่ยนไฟล์)</span>`;
        label.classList.add('has-file');
        label.classList.remove('py-22'); // อาจจะลด padding ลงนิดหน่อยถ้ามีรูป

        // 2. สร้าง URL จำลองของรูปภาพและนำไปแสดงผล
        previewImg.src = URL.createObjectURL(file);
        previewImg.classList.remove('hidden');
        previewImg.classList.add('block');
    } else {
        // กรณีที่ผู้ใช้กดยกเลิกการเลือกไฟล์ ให้คืนค่าทุกอย่างกลับเป็นเหมือนเดิม
        textSpan.textContent = '📎 แตะเพื่อแนบสลิปการโอนเงิน';
        label.classList.remove('has-file');

        previewImg.src = '';
        previewImg.classList.add('hidden');
        previewImg.classList.remove('block');
    }
}
</script>
@endpush
@endsection
