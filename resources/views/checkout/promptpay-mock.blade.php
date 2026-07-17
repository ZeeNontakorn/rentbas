@extends('layouts.app')

@section('title', 'ชำระเงินผ่าน QR PromptPay')

@section('content')
<div class="bg-white min-h-screen text-[#111827]">
<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');
.pp-main { font-family: 'Sarabun', 'Kanit', sans-serif; }
.pp-main h1, .pp-main h2, .pp-main h3 { font-family: 'Kanit', sans-serif; }

.pp-card { border: 1px solid #e5e7eb; border-radius: 14px; padding: 30px; text-align: center; }
.pp-qr-box {
    width: 220px; height: 220px; margin: 0 auto 18px; border-radius: 12px;
    background: repeating-linear-gradient(45deg, #f3f4f6, #f3f4f6 10px, #e5e7eb 10px, #e5e7eb 20px);
    display: flex; align-items: center; justify-content: center; position: relative;
}
.pp-qr-box .lock {
    background: rgba(11,11,26,0.85); color: #fff; font-family: 'Kanit', sans-serif; font-weight: 700;
    font-size: 13px; padding: 8px 16px; border-radius: 999px;
}
.pp-upload {
    border: 2px dashed #d1d5db; border-radius: 12px; padding: 26px; text-align: center; color: #9ca3af;
    font-size: 13px; margin-top: 18px; cursor: not-allowed; background: #f9fafb;
}
.wip-banner {
    background: #fff7ed; border: 1px solid #fed7aa; color: #92400e; border-radius: 10px;
    padding: 12px 18px; font-size: 13px; text-align: left; display: flex; gap: 10px; align-items: flex-start;
}
</style>

<div class="pp-main max-w-[520px] mx-auto px-4 py-10" data-aos="fade-up">

    <div class="mb-6 text-center">
        <h1 class="text-[26px] font-bold text-gray-900">สแกนจ่ายผ่าน QR PromptPay</h1>
        <p class="text-gray-500 text-sm mt-1">การจอง #{{ str_pad($booking->id, 6, '0', STR_PAD_LEFT) }} — ยอดชำระ ฿{{ number_format($booking->price / 100, 0) }}</p>
    </div>

    <div class="wip-banner mb-6">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>
            <strong>ฟีเจอร์นี้ยังอยู่ระหว่างการพัฒนา (WIP)</strong> — ตอนนี้เป็นแค่หน้าตัวอย่าง (mock) ยังไม่สามารถสแกนจ่ายจริงหรือแนบสลิปได้
            รายการนี้ถูกล็อกไว้ในสถานะ "รอตรวจสอบสลิป" กรุณาติดต่อแอดมินโดยตรงเพื่อดำเนินการชำระเงินในระหว่างนี้ หรือกลับไปเลือกชำระด้วยเครดิตแทน
        </span>
    </div>

    <div class="pp-card">
        <div class="pp-qr-box">
            <span class="lock">🔒 ตัวอย่าง QR (Mock)</span>
        </div>

        <p class="text-sm text-gray-600 mb-1">พร้อมเพย์: <span class="font-bold text-gray-900">099-999-9999</span></p>
        <p class="text-sm text-gray-600">ชื่อบัญชี: <span class="font-bold text-gray-900">THATA HOMECOURT</span></p>

        <div class="pp-upload">
            📎 แนบสลิปการโอนเงิน (ยังใช้งานไม่ได้ในเวอร์ชันนี้)
        </div>

        <a href="{{ route('checkout.show', $booking) }}" class="inline-block mt-6 text-sm font-medium text-gray-500 hover:text-gray-800 transition">
            ← กลับไปเลือกวิธีชำระเงินอื่น
        </a>
    </div>
</div>
</div>
@endsection
