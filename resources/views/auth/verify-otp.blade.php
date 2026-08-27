@extends('layouts.app')

@section('title', 'ยืนยัน OTP')

@section('content')
<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600&display=swap');

.auth-page {
    min-height: 100vh;
    background: #f5f5f5;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 16px;
    font-family: 'Sarabun', sans-serif;
}

.auth-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 28px;
}
.auth-brand-ball {
    width: 44px; height: 44px;
    background: #e86c2a;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
}
.auth-brand-name {
    font-family: 'Kanit', sans-serif;
    font-size: 18px;
    font-weight: 600;
    color: #111;
}

.auth-title {
    font-family: 'Kanit', sans-serif;
    font-size: 26px;
    font-weight: 700;
    color: #111;
    text-align: center;
    margin-bottom: 6px;
}
.auth-subtitle {
    font-size: 14px;
    color: #6b7280;
    text-align: center;
    margin-bottom: 28px;
}

/* Card */
.auth-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    padding: 28px 28px 24px;
    width: 100%;
    max-width: 380px;
}
.auth-card-title {
    font-family: 'Kanit', sans-serif;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    text-align: center;
    margin-bottom: 20px;
}

/* OTP icon */
.otp-icon {
    width: 56px; height: 56px;
    background: #fff7ed;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 26px;
    margin: 0 auto 16px;
    border: 2px solid #fed7aa;
}

/* OTP boxes */
.otp-boxes {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin-bottom: 20px;
}
.otp-box {
    width: 48px; height: 54px;
    text-align: center;
    font-size: 22px;
    font-weight: 700;
    font-family: monospace;
    color: #111;
    border: 1.5px solid #d1d5db;
    border-radius: 10px;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
    caret-color: #e86c2a;
}
.otp-box:focus {
    border-color: #e86c2a;
    box-shadow: 0 0 0 3px rgba(232,108,42,0.12);
}

/* Hidden real input */
.otp-real { display: none; }

.btn-submit {
    display: block;
    width: 100%;
    padding: 13px;
    background: #111;
    color: #fff;
    font-family: 'Kanit', sans-serif;
    font-size: 15px;
    font-weight: 600;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: background .2s, transform .15s;
    text-align: center;
}
.btn-submit:hover { background: #222; transform: translateY(-1px); }

.auth-back {
    text-align: center;
    margin-top: 18px;
    font-size: 13px;
    color: #6b7280;
}
.auth-back a {
    color: #e86c2a;
    font-weight: 600;
    text-decoration: none;
}
.auth-back a:hover { text-decoration: underline; }

/* Timer */
.otp-timer {
    text-align: center;
    font-size: 13px;
    color: #9ca3af;
    margin-top: 14px;
}
.otp-timer span { color: #e86c2a; font-weight: 600; }


/* Resend button */
.btn-resend {
    display: block;
    width: 100%;
    padding: 11px;
    background: transparent;
    color: #6b7280;
    font-family: 'Kanit', sans-serif;
    font-size: 14px;
    font-weight: 500;
    border: 1.5px solid #e5e7eb;
    border-radius: 10px;
    cursor: pointer;
    margin-top: 10px;
    transition: all .2s;
    text-align: center;
}
.btn-resend:hover { border-color: #e86c2a; color: #e86c2a; }
.btn-resend:disabled { opacity: .4; cursor: not-allowed; }

/* Error */
.auth-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13px;
    margin-bottom: 16px;
    text-align: center;
}
</style>

<div class="auth-page">


    {{-- Title --}}
    <h1 class="auth-title">ยืนยันบัญชีด้วย OTP</h1>
    <p class="auth-subtitle">กรอกรหัส 6 หลักที่ส่งไปยังอีเมลของคุณ</p>

    {{-- Card --}}
    <div class="auth-card">

        <div class="otp-icon">✉️</div>
        <p class="auth-card-title">รหัส OTP ถูกส่งไปยังอีเมลของคุณแล้ว</p>

        @if ($errors->any())
            <div class="auth-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('verify-otp') }}">
            @csrf

            {{-- Hidden real input --}}
            <input type="text" name="otp_code" id="otp_real"
                class="otp-real" maxlength="6" required>

            {{-- Visual OTP boxes --}}
            <div class="otp-boxes">
                <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            </div>

            <button type="submit" class="btn-submit">ยืนยัน OTP</button>
        </form>

        <div class="otp-timer">
            รหัสหมดอายุใน <span id="countdown">05:00</span>
        </div>

        {{-- Resend OTP --}}
        <form method="POST" action="{{ route('otp.resend') }}" style="margin-top:10px;" id="resend-form">
            @csrf
            <button type="submit" class="btn-resend" id="resend-btn" disabled>
                ส่งรหัส OTP อีกครั้ง (<span id="resend-countdown">30</span>)
            </button>
        </form>

        <div class="auth-back">
            ต้องการกลับไป?
            <a href="{{ route('login') }}">เข้าสู่ระบบ</a>
        </div>
    </div>

</div>

<script>
// ── OTP box auto-focus & sync ──
const boxes = document.querySelectorAll('.otp-box');
const real  = document.getElementById('otp_real');

function syncReal() {
    real.value = Array.from(boxes).map(b => b.value).join('');
}

boxes.forEach((box, i) => {
    box.addEventListener('input', () => {
        box.value = box.value.replace(/\D/, '');
        syncReal();
        if (box.value && i < boxes.length - 1) boxes[i + 1].focus();
    });
    box.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !box.value && i > 0) {
            boxes[i - 1].value = '';
            boxes[i - 1].focus();
            syncReal();
        }
    });
    box.addEventListener('paste', e => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
        text.split('').forEach((c, j) => { if (boxes[j]) boxes[j].value = c; });
        syncReal();
        if (boxes[text.length]) boxes[text.length].focus();
        else boxes[boxes.length - 1].focus();
    });
});

boxes[0].focus();

// ── Countdown timer ──
let secs = 300;
const cd = document.getElementById('countdown');
const tick = setInterval(() => {
    secs--;
    if (secs <= 0) { clearInterval(tick); cd.textContent = 'หมดอายุแล้ว'; cd.style.color = '#dc2626'; return; }
    const m = String(Math.floor(secs / 60)).padStart(2,'0');
    const s = String(secs % 60).padStart(2,'0');
    cd.textContent = m + ':' + s;
}, 1000);

// ── Resend cooldown 30s ──
const resendBtn = document.getElementById('resend-btn');
const resendCd  = document.getElementById('resend-countdown');
let resendSecs  = 30;

const resendTick = setInterval(() => {
    resendSecs--;
    resendCd.textContent = resendSecs;
    if (resendSecs <= 0) {
        clearInterval(resendTick);
        resendBtn.disabled = false;
        resendBtn.innerHTML = 'ส่งรหัส OTP อีกครั้ง';
    }
}, 1000);

document.getElementById('resend-form').addEventListener('submit', () => {
    resendBtn.disabled = true;
    resendSecs = 30;
    resendCd.textContent = resendSecs;
    resendBtn.innerHTML = 'ส่งรหัส OTP อีกครั้ง (<span id="resend-countdown">' + resendSecs + '</span>)';
    // reassign span reference after innerHTML update
    const newCd = resendBtn.querySelector('span');
    const newTick = setInterval(() => {
        resendSecs--;
        newCd.textContent = resendSecs;
        if (resendSecs <= 0) {
            clearInterval(newTick);
            resendBtn.disabled = false;
            resendBtn.innerHTML = 'ส่งรหัส OTP อีกครั้ง';
        }
    }, 1000);
});
</script>

@endsection
