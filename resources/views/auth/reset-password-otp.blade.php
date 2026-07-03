@extends('layouts.app')
@section('title', 'ยืนยัน OTP รีเซ็ตรหัสผ่าน')

@section('content')
<div class="min-h-screen bg-[#f5f5f5] flex items-center justify-center px-4 py-16">

    <div class="w-full max-w-md">

        {{-- CARD --}}
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">

            {{-- HEADER --}}
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-8 py-7 text-center">
                <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h1 class="text-white text-xl font-semibold">ยืนยันรหัส OTP</h1>
                <p class="text-orange-100 text-sm mt-1">รหัส 6 หลักถูกส่งไปยัง <strong>{{ $email }}</strong></p>
            </div>

            {{-- BODY --}}
            <div class="px-8 py-8">

                {{-- Error --}}
                @if ($errors->any())
                    <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-6 text-sm">
                        <svg class="w-5 h-5 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div>@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
                    </div>
                @endif

                <p class="text-sm text-gray-500 text-center mb-6">กรอกรหัส OTP 6 หลักที่ส่งไปยังอีเมลของคุณ รหัสมีอายุ <span class="text-orange-500 font-medium">5 นาที</span></p>

                <form method="POST" action="{{ route('password.otp.verify') }}" id="otpForm">
                    @csrf

                    {{-- OTP 6-box input --}}
                    <div class="flex justify-center gap-2.5 mb-8" id="otp-boxes">
                        @for ($i = 0; $i < 6; $i++)
                            <input type="text" inputmode="numeric" maxlength="1"
                                class="otp-digit w-12 h-14 text-center text-xl font-bold border-2 border-gray-300 rounded-xl focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition text-gray-800"
                                autocomplete="off">
                        @endfor
                        <input type="hidden" name="otp_code" id="otp_code">
                    </div>

                    <button type="submit"
                        class="w-full py-3 px-4 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-xl text-sm transition shadow-md flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        ยืนยัน OTP
                    </button>
                </form>

                {{-- Timer --}}
                <div class="text-center mt-5 text-sm text-gray-500">
                    <span id="timer-text">รหัสหมดอายุใน <span id="countdown" class="text-orange-500 font-semibold">05:00</span></span>
                    <p id="expired-text" class="hidden text-red-500">รหัสหมดอายุแล้ว กรุณา <a href="{{ route('password.request') }}" class="underline font-medium">ขอรหัสใหม่</a></p>
                </div>

                <p class="text-center text-sm text-gray-500 mt-4">
                    <a href="{{ route('password.request') }}" class="text-orange-500 hover:text-orange-600 hover:underline">← กลับไปกรอกอีเมลใหม่</a>
                </p>

            </div>
        </div>

        {{-- STEPS --}}
        <div class="flex items-center justify-center gap-3 mt-6">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-green-500 text-white text-xs flex items-center justify-center font-semibold">✓</div>
                <span class="text-xs text-green-600">กรอกอีเมล</span>
            </div>
            <div class="w-8 h-px bg-gray-300"></div>
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-orange-500 text-white text-xs flex items-center justify-center font-semibold">2</div>
                <span class="text-xs text-orange-600 font-medium">ยืนยัน OTP</span>
            </div>
            <div class="w-8 h-px bg-gray-300"></div>
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-gray-200 text-gray-500 text-xs flex items-center justify-center font-semibold">3</div>
                <span class="text-xs text-gray-500">ตั้งรหัสใหม่</span>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
// OTP box navigation
const digits = document.querySelectorAll('.otp-digit');
const hiddenInput = document.getElementById('otp_code');

digits.forEach((box, i) => {
    box.addEventListener('input', () => {
        box.value = box.value.replace(/\D/g, '').slice(0, 1);
        if (box.value && i < digits.length - 1) digits[i + 1].focus();
        updateHidden();
    });
    box.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !box.value && i > 0) {
            digits[i - 1].focus();
            digits[i - 1].value = '';
            updateHidden();
        }
    });
    box.addEventListener('paste', (e) => {
        const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        e.preventDefault();
        [...text].slice(0, 6).forEach((ch, j) => {
            if (digits[j]) digits[j].value = ch;
        });
        digits[Math.min(text.length, 5)].focus();
        updateHidden();
    });
});

function updateHidden() {
    hiddenInput.value = [...digits].map(d => d.value).join('');
}

// Submit on last digit filled
digits[5].addEventListener('input', () => {
    if ([...digits].every(d => d.value)) document.getElementById('otpForm').submit();
});

// Countdown timer (5 min)
let secs = 5 * 60;
const countdown = document.getElementById('countdown');
const timerText = document.getElementById('timer-text');
const expiredText = document.getElementById('expired-text');

const timer = setInterval(() => {
    secs--;
    if (secs <= 0) {
        clearInterval(timer);
        timerText.classList.add('hidden');
        expiredText.classList.remove('hidden');
        return;
    }
    const m = String(Math.floor(secs / 60)).padStart(2, '0');
    const s = String(secs % 60).padStart(2, '0');
    countdown.textContent = `${m}:${s}`;
}, 1000);
</script>
@endpush
@endsection
