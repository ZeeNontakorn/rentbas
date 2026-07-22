@extends('layouts.app')

@section('title', 'โปรไฟล์ของฉัน · BCBS')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');

:root {
    --ore: #e86c2a;
    --ore-d: #d05a1a;
    --ink: #0d0f1e;
    --navy: #1e2235;
    --navy-d: #13162a;
    --cream: #f6f5f0;
    --white: #ffffff;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.pf-page {
    min-height: calc(100vh - 56px);
    background: #f0f2f5;
    font-family: 'Sarabun', sans-serif;
}

/* ── HERO BANNER ── */
.pf-hero {
    background: linear-gradient(135deg, var(--navy-d) 0%, #1a1f3a 50%, #0f1225 100%);
    padding: 48px max(24px, calc((100% - 1100px) / 2));
    position: relative;
    overflow: hidden;
}
.pf-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse 60% 80% at 80% 50%, rgba(232,108,42,.18) 0%, transparent 70%);
    pointer-events: none;
}
.pf-hero-inner {
    display: flex;
    align-items: center;
    gap: 28px;
    position: relative;
    z-index: 1;
}
.pf-avatar {
    width: 88px; height: 88px;
    background: linear-gradient(135deg, var(--ore), #f5a06a);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 36px;
    flex-shrink: 0;
    box-shadow: 0 0 0 4px rgba(232,108,42,.25), 0 8px 28px rgba(0,0,0,.35);
    position: relative;
}
.pf-avatar-ring {
    position: absolute; inset: -5px;
    border-radius: 50%;
    border: 2px dashed rgba(232,108,42,.4);
    animation: spin 18s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.pf-hero-info { flex: 1; }
.pf-hero-eyebrow {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: .2em;
    text-transform: uppercase;
    color: var(--ore);
    margin-bottom: 6px;
}
.pf-hero-name {
    font-family: 'Kanit', sans-serif;
    font-size: clamp(22px, 3vw, 32px);
    font-weight: 800;
    color: #fff;
    line-height: 1.1;
    margin-bottom: 8px;
}
.pf-hero-email {
    font-size: 13px;
    color: rgba(255,255,255,.45);
}
.pf-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    background: rgba(232,108,42,.18);
    border: 1px solid rgba(232,108,42,.3);
    border-radius: 20px;
    font-family: 'Kanit', sans-serif;
    font-size: 11px;
    font-weight: 600;
    color: #ffa96b;
    margin-top: 10px;
}
.pf-hero-badge::before {
    content: '';
    width: 6px; height: 6px;
    background: var(--ore);
    border-radius: 50%;
    box-shadow: 0 0 6px rgba(232,108,42,.8);
}

/* ── CONTENT BODY ── */
.pf-body {
    max-width: 1100px;
    margin: 0 auto;
    padding: 32px max(24px, 16px);
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    align-items: start;
}
@media (max-width: 720px) {
    .pf-body { grid-template-columns: 1fr; padding: 20px 16px; }
    .pf-hero-inner { flex-direction: column; text-align: center; }
}

/* ── CARDS ── */
.pf-card {
    background: #fff;
    border-radius: 18px;
    border: 1px solid #e8eaed;
    overflow: hidden;
    box-shadow: 0 2px 16px rgba(0,0,0,.05);
    transition: box-shadow .25s;
}
.pf-card:hover { box-shadow: 0 6px 28px rgba(0,0,0,.09); }

.pf-card-header {
    padding: 18px 24px 16px;
    border-bottom: 1px solid #f1f3f5;
    display: flex;
    align-items: center;
    gap: 10px;
}
.pf-card-icon {
    width: 36px; height: 36px;
    background: linear-gradient(135deg, var(--ore), #f5a06a);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.pf-card-title {
    font-family: 'Kanit', sans-serif;
    font-size: 15px;
    font-weight: 700;
    color: #1a1a2e;
}
.pf-card-sub {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 2px;
}
.pf-card-body { padding: 20px 24px 24px; }

/* ── FORM ── */
.form-group { margin-bottom: 16px; }
.form-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 6px;
}
.form-input {
    width: 100%;
    padding: 11px 14px;
    font-size: 14px;
    font-family: 'Sarabun', sans-serif;
    color: #1a1a2e;
    background: #fafafa;
    border: 1.5px solid #e5e7eb;
    border-radius: 10px;
    outline: none;
    transition: border-color .2s, box-shadow .2s, background .2s;
}
.form-input::placeholder { color: #c4c9d4; }
.form-input:focus {
    border-color: var(--ore);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(232,108,42,.1);
}
.form-error { font-size: 12px; color: #dc2626; margin-top: 4px; }

/* ── BUTTONS ── */
.btn-primary {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 13px;
    background: linear-gradient(135deg, var(--ore), #f5813a);
    color: #fff;
    font-family: 'Kanit', sans-serif;
    font-size: 14px;
    font-weight: 700;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: transform .15s, box-shadow .2s;
    margin-top: 6px;
    box-shadow: 0 4px 16px rgba(232,108,42,.3);
}
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(232,108,42,.4);
}
.btn-primary:active { transform: translateY(0); }

.btn-otp {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    padding: 10px;
    background: #f0fdf4;
    color: #15803d;
    border: 1.5px solid #bbf7d0;
    font-family: 'Kanit', sans-serif;
    font-size: 13px;
    font-weight: 600;
    border-radius: 10px;
    cursor: pointer;
    transition: background .2s, border-color .2s;
}
.btn-otp:hover { background: #dcfce7; border-color: #86efac; }

/* ── OTP NOTICE BOX ── */
.otp-notice {
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 1px solid #fde68a;
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 16px;
    display: flex;
    gap: 10px;
    align-items: flex-start;
}
.otp-notice-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
.otp-notice-text { font-size: 12.5px; color: #78350f; line-height: 1.6; }
.otp-notice-text strong { display: block; font-weight: 700; margin-bottom: 2px; font-size: 13px; }

.otp-msg-box {
    margin-top: 10px;
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 13px;
    display: none;
}
.otp-msg-box.ok  { display: flex; align-items: center; gap: 8px; background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.otp-msg-box.err { display: flex; align-items: center; gap: 8px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

/* ── DIVIDER ── */
.pf-divider {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 18px 0;
    color: #d1d5db;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.pf-divider::before, .pf-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #f1f3f5;
}

/* ── INFO ROW ── */
.info-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f9fafb;
}
.info-row:last-child { border-bottom: none; }
.info-icon {
    width: 32px; height: 32px;
    background: #f3f4f6;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}
.info-label { font-size: 11px; color: #9ca3af; font-weight: 600; letter-spacing: .04em; }
.info-value { font-size: 14px; color: #1a1a2e; font-weight: 500; margin-top: 1px; }
</style>

@php
    $isCoach = in_array($user->membership_type, ['coach', 'court_assistant'], true);
    $coachProfileImage = $user->staffProfile?->profile_image;
@endphp

{{-- ═══ HERO ═══ --}}
<div class="pf-hero">
    <div class="pf-hero-inner">
        <div class="pf-avatar">
            🏀
            <div class="pf-avatar-ring"></div>
        </div>
        <div class="pf-hero-info">
            <p class="pf-hero-eyebrow">BCBS Member Account</p>
            <h1 class="pf-hero-name">{{ auth()->user()->name }}</h1>
            <p class="pf-hero-email">{{ auth()->user()->email }}</p>
            <div class="pf-hero-badge">สมาชิกระบบ BCBS Arena</div>
        </div>
    </div>
</div>

{{-- ═══ BODY ═══ --}}
<div class="pf-body">

    {{-- LEFT: Profile Info --}}
    <div class="pf-card">
        <div class="pf-card-header">
            <div class="pf-card-icon">👤</div>
            <div>
                <div class="pf-card-title">ข้อมูลส่วนตัว</div>
                <div class="pf-card-sub">แก้ไขชื่อ อีเมล และเบอร์โทร</div>
            </div>
        </div>
        <div class="pf-card-body">

            @if ($errors->any())
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const msgs = {!! json_encode($errors->all()) !!};
                    Swal.fire({
                        icon: 'error',
                        title: 'กรุณาตรวจสอบข้อมูล',
                        html: msgs.join('<br>'),
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#e86c2a'
                    });
                });
            </script>
            @endif

            <form method="POST" action="{{ route('profile.update') }}" id="profileForm" enctype="multipart/form-data">
                @csrf

                {{-- ชื่อ --}}
                <div class="form-group">
                    <label class="form-label">✏️ ชื่อผู้ใช้</label>
                    <input type="text" name="name" value="{{ $user->name }}" required
                        placeholder="กรอกชื่อของคุณ" class="form-input">
                    @error('name')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- อีเมล --}}
                <div class="form-group">
                    <label class="form-label">📧 อีเมล</label>
                    <input type="email" name="email" value="{{ $user->email }}" required
                        id="emailInput" placeholder="example@email.com" class="form-input">
                    @error('email')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- เบอร์โทร --}}
                <div class="form-group">
                    <label class="form-label">📱 เบอร์โทรศัพท์</label>
                    <input type="tel" name="phone" value="{{ $user->phone ?? '' }}"
                        placeholder="0812345678" pattern="[0-9]*" inputmode="numeric"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                        maxlength="10" class="form-input">
                    @error('phone')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                @if ($isCoach)
                    <div class="form-group">
                        <label class="form-label">รูปโปรไฟล์</label>
                        <div style="display:flex; gap:12px; align-items:flex-start; margin-bottom:10px; flex-wrap:wrap;">
                            <div style="display:flex; flex-direction:column; align-items:center; gap:6px;">
                                @if ($coachProfileImage)
                                    <img src="{{ route('storage.local', ['path' => $coachProfileImage]) }}"
                                         alt="Coach profile"
                                         style="width:66px;height:66px;border-radius:12px;object-fit:cover;border:1px solid #e5e7eb;">
                                @else
                                    <div style="width:66px;height:66px;border-radius:12px;border:1px dashed #d1d5db;background:#f9fafb;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:12px;">ไม่มีรูป</div>
                                @endif
                                <span style="font-size:11px;color:#6b7280;">รูปปัจจุบัน</span>
                            </div>

                            <div id="newImagePreviewBlock" style="display:none; flex-direction:column; align-items:center; gap:6px;">
                                <img id="newImagePreview"
                                     src=""
                                     alt="New profile preview"
                                     style="width:66px;height:66px;border-radius:12px;object-fit:cover;border:1px solid #e5e7eb;">
                                <span style="font-size:11px;color:#6b7280;">รูปใหม่</span>
                            </div>
                        </div>

                        <input id="profileImageInput" type="file" name="profile_image" accept="image/png,image/jpeg,image/webp" class="form-input" style="padding:8px 10px;">
                        @error('profile_image')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                {{-- OTP Section --}}
                <div id="otpSection" style="display:none;">
                    <div class="otp-notice">
                        <div class="otp-notice-icon">🔐</div>
                        <div class="otp-notice-text">
                            <strong>ต้องยืนยัน OTP เพื่อเปลี่ยนอีเมล</strong>
                            กดปุ่ม "ส่ง OTP" แล้วกรอกรหัสที่ได้รับในอีเมลใหม่ของคุณ
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">🔢 รหัส OTP (6 หลัก)</label>
                        <input type="text" name="otp" placeholder="000000" maxlength="6"
                            pattern="[0-9]{6}" class="form-input">
                        @error('otp')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="button" class="btn-otp" onclick="requestOtp()">
                        📨 ส่ง OTP ไปยังอีเมลใหม่
                    </button>
                    <div id="otpMessage" class="otp-msg-box"></div>
                </div>

                <div class="pf-divider">บันทึกข้อมูล</div>

                <button type="submit" class="btn-primary">
                    💾 บันทึกการเปลี่ยนแปลง
                </button>
            </form>
        </div>
    </div>

    {{-- RIGHT: Security --}}
    <div style="display: flex; flex-direction: column; gap: 20px;">

        {{-- Current Info Card --}}
        <div class="pf-card">
            <div class="pf-card-header">
                <div class="pf-card-icon">📋</div>
                <div>
                    <div class="pf-card-title">ข้อมูลบัญชีปัจจุบัน</div>
                    <div class="pf-card-sub">ข้อมูลที่บันทึกในระบบ</div>
                </div>
            </div>
            <div class="pf-card-body" style="padding-top:12px;padding-bottom:12px;">
                <div class="info-row">
                    <div class="info-icon">👤</div>
                    <div>
                        <div class="info-label">ชื่อ</div>
                        <div class="info-value">{{ $user->name }}</div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon">📧</div>
                    <div>
                        <div class="info-label">อีเมล</div>
                        <div class="info-value">{{ $user->email }}</div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon">📱</div>
                    <div>
                        <div class="info-label">เบอร์โทรศัพท์</div>
                        <div class="info-value">{{ $user->phone ?: '—' }}</div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon">🏅</div>
                    <div>
                        <div class="info-label">ระดับ</div>
                        <div class="info-value">{{ $user->role === 'admin' ? 'ผู้ดูแลระบบ' : 'สมาชิก' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Password Card --}}
        <div class="pf-card">
            <div class="pf-card-header">
                <div class="pf-card-icon">🔒</div>
                <div>
                    <div class="pf-card-title">ความปลอดภัย</div>
                    <div class="pf-card-sub">เปลี่ยนรหัสผ่านของคุณ</div>
                </div>
            </div>
            <div class="pf-card-body">
                <p style="font-size:13px;color:#6b7280;margin-bottom:16px;line-height:1.6;">
                    หากต้องการเปลี่ยนรหัสผ่าน กรอกรหัสผ่านใหม่และยืนยัน แล้วกดบันทึกในฟอร์มด้านซ้าย
                </p>
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">🔑 รหัสผ่านใหม่</label>
                        <input type="password" name="password" placeholder="••••••••" class="form-input">
                        @error('password')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">🔑 ยืนยันรหัสผ่าน</label>
                        <input type="password" name="password_confirmation" placeholder="••••••••" class="form-input">
                    </div>
                    <div class="pf-divider" style="margin-top:18px;">บันทึก</div>
                    <button type="submit" class="btn-primary">
                        🔐 เปลี่ยนรหัสผ่าน
                    </button>
                </form>
            </div>
        </div>

    </div>{{-- /right --}}

</div>{{-- /pf-body --}}

<script>
const originalEmail = '{{ $user->email }}';
const emailInput    = document.getElementById('emailInput');
const otpSection    = document.getElementById('otpSection');
const profileImageInput = document.getElementById('profileImageInput');
const newImagePreviewBlock = document.getElementById('newImagePreviewBlock');
const newImagePreview = document.getElementById('newImagePreview');

emailInput.addEventListener('input', function () {
    otpSection.style.display = this.value !== originalEmail ? 'block' : 'none';
});

if (profileImageInput && newImagePreviewBlock && newImagePreview) {
    profileImageInput.addEventListener('change', function () {
        const file = this.files && this.files[0] ? this.files[0] : null;

        if (!file) {
            newImagePreviewBlock.style.display = 'none';
            newImagePreview.removeAttribute('src');
            return;
        }

        const objectUrl = URL.createObjectURL(file);
        newImagePreview.src = objectUrl;
        newImagePreviewBlock.style.display = 'flex';
    });
}

@if (session('success'))
    document.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
            icon: 'success',
            title: 'สำเร็จ!',
            text: '{{ session('success') }}',
            confirmButtonText: 'ตกลง',
            confirmButtonColor: '#e86c2a'
        });
    });
@endif

function requestOtp() {
    const email = emailInput.value.trim();
    const box   = document.getElementById('otpMessage');
    if (email === originalEmail) {
        box.textContent = 'อีเมลไม่มีการเปลี่ยนแปลง';
        box.className   = 'otp-msg-box err';
        return;
    }
    const fd = new FormData();
    fd.append('email', email);
    fd.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    box.innerHTML   = '⏳ กำลังส่ง OTP...';
    box.className   = 'otp-msg-box ok';
    fetch('{{ route('profile.request-otp-email') }}', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                box.innerHTML = '✅ ส่ง OTP ไปยัง <strong>' + email + '</strong> แล้ว';
                box.className = 'otp-msg-box ok';
            } else {
                box.textContent = '❌ ' + (d.message || 'ไม่สามารถส่ง OTP ได้');
                box.className   = 'otp-msg-box err';
            }
        })
        .catch(() => {
            box.textContent = '❌ เกิดข้อผิดพลาดในการส่ง OTP';
            box.className   = 'otp-msg-box err';
        });
}
</script>
@endsection
