@extends('layouts.app')

@section('title', 'บัญชีของฉัน · BCBS')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');
.pf-main { font-family:'Sarabun','Kanit',sans-serif; }
.pf-main h1,.pf-main h2,.pf-main h3 { font-family:'Kanit',sans-serif; }
.info-row { display:flex; align-items:center; gap:14px; padding:14px 0; border-bottom:1px solid #f3f4f6; }
.info-row:last-child { border-bottom:none; }
.info-icon { width:36px; height:36px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.info-label { font-size:11px; color:#9ca3af; font-weight:600; letter-spacing:.05em; text-transform:uppercase; margin-bottom:2px; }
.info-value { font-size:14px; color:#111827; font-weight:500; }

.form-label { display:block; font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.06em; margin-bottom:6px; }
.form-input {
    width:100%; padding:10px 13px; font-size:14px; font-family:'Sarabun',sans-serif;
    color:#111827; background:#fafafa; border:1.5px solid #e5e7eb; border-radius:8px;
    outline:none; transition:border-color .2s, box-shadow .2s, background .2s;
}
.form-input::placeholder { color:#d1d5db; }
.form-input:focus { border-color:#e86c2a; background:#fff; box-shadow:0 0 0 3px rgba(232,108,42,.08); }
.form-error { font-size:12px; color:#dc2626; margin-top:4px; }
.form-group { margin-bottom:16px; }

.otp-notice {
    background:#fffbeb; border:1px solid #fde68a; border-radius:10px;
    padding:12px 14px; margin-bottom:16px;
    display:flex; gap:10px; align-items:flex-start;
}
.otp-notice-text { font-size:13px; color:#78350f; line-height:1.6; }
.otp-notice-text strong { display:block; font-weight:700; margin-bottom:2px; }
.otp-msg { margin-top:10px; padding:10px 14px; border-radius:8px; font-size:13px; display:none; }
.otp-msg.ok  { display:flex; align-items:center; gap:8px; background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
.otp-msg.err { display:flex; align-items:center; gap:8px; background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }

.section-divider {
    display:flex; align-items:center; gap:10px;
    margin:20px 0 16px; color:#d1d5db; font-size:11px; font-weight:600;
    text-transform:uppercase; letter-spacing:.08em;
}
.section-divider::before,.section-divider::after { content:''; flex:1; height:1px; background:#f1f3f5; }

/* Avatar display (read-only) */
.avatar-wrap {
    width:140px; flex-shrink:0;
    display:flex; flex-direction:column; align-items:center;
    padding-top:4px;
}
.avatar-frame {
    position:relative; width:140px; height:160px;
}
.avatar-preview {
    width:16rem; height:20rem; border-radius:10px;
    object-fit:cover; border:1.5px solid #e5e7eb;
    background:#f9fafb;
    display:flex; align-items:center; justify-content:center;
}

/* Placeholder box only fills 70% of the preview area, centered */
.avatar-placeholder-box {
    width:70%; height:70%;
    display:flex; align-items:center; justify-content:center;
    border:1px dashed #e5e7eb; border-radius:6px;
    background:#fff;
}
.avatar-placeholder-text { font-size:11px; color:#9ca3af; font-weight:500; text-align:center; }
</style>

<div class="pf-main bg-white min-h-screen text-[#111827]">
<div class="max-w-[860px] mx-auto px-4 py-8">

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-baseline gap-4">
        <div>
            <h1 class="text-[32px] font-bold text-gray-900 tracking-tight">บัญชีของฉัน</h1>
            <p class="text-gray-500 text-[14px] mt-0.5">ข้อมูลสมาชิก Thata Homecourt</p>
        </div>
        <a href="{{ route('profile.edit') }}"
           class="inline-flex items-center gap-2 bg-[#e86c2a] hover:bg-[#d05a1a] text-white font-medium px-4 py-2 rounded-lg transition text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            แก้ไขรหัสผ่าน
        </a>
    </div>

    {{-- BANNER --}}
    <div class="w-full h-[180px] rounded-[14px] overflow-hidden mb-6 shadow-sm relative group">
        <img src="https://images.unsplash.com/photo-1504450758481-7338eba7524a?q=80&w=1400&auto=format&fit=crop"
             style="filter:grayscale(70%) sepia(10%);"
             alt="Profile Banner"
             class="w-full h-full object-cover transition duration-500 group-hover:scale-105 group-hover:grayscale-0">
        <div class="absolute inset-0 bg-gradient-to-r from-black/75 via-black/35 to-transparent"></div>
        <div class="absolute inset-y-0 left-0 flex flex-col justify-center px-8 text-white">
            <p class="text-[11px] font-semibold tracking-[.18em] uppercase text-[#e86c2a] mb-1">Member Profile</p>
            <h2 class="text-[22px] font-bold tracking-wide">{{ $user->us_name }}</h2>
            <p class="text-gray-300 text-[13px] mt-1">{{ $user->email }}</p>
        </div>
    </div>

    @if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const msgs = {!! json_encode($errors->all()) !!};
            Swal.fire({ icon:'error', title:'กรุณาตรวจสอบข้อมูล',
                html: msgs.join('<br>'), confirmButtonText:'ตกลง',
                confirmButtonColor:'#e86c2a' });
        });
    </script>
    @endif

    @php
        $isStaff = in_array($user->membership_type ?? null, ['coach', 'court_assistant']);
    @endphp

    {{-- EDITABLE INFO CARD --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm mb-6">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-[#e86c2a]/10 flex items-center justify-center border border-[#e86c2a]/20">
                <svg class="w-4 h-4 text-[#e86c2a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div>
                <div class="text-[14px] font-semibold text-gray-800" style="font-family:'Kanit',sans-serif;">ข้อมูลบัญชี</div>
                <div class="text-[12px] text-gray-400">แก้ไขข้อมูลที่บันทึกในระบบ</div>
            </div>
        </div>

        <div class="p-5">
            <form method="POST" action="{{ route('profile.update') }}" id="profileForm">
                @csrf

                <div class="info-row">
                    <div class="info-icon">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="info-label">ชื่อผู้ใช้</div>
                        <div class="info-value">{{ $user->us_name }}</div>
                    </div>
                </div>

                    {{-- LEFT: fields --}}
                    <div class="w-full {{ $isStaff ? 'md:w-[65%]' : '' }}">
                        <div class="form-group">
                            <label class="form-label">ชื่อผู้ใช้</label>
                            <input type="text" name="name" value="{{ $user->name }}" required
                                placeholder="กรอกชื่อของคุณ" class="form-input">
                            @error('name') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">อีเมล</label>
                            <input type="email" name="email" value="{{ $user->email }}" required
                                id="emailInput" placeholder="example@email.com" class="form-input">
                            @error('email') <div class="form-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">เบอร์โทรศัพท์</label>
                            <input type="tel" name="phone" value="{{ $user->phone ?? '' }}"
                                placeholder="0812345678" pattern="[0-9]*" inputmode="numeric"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                maxlength="10" class="form-input">
                            @error('phone') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        {{-- OTP Section --}}
                        <div id="otpSection" style="display:none; margin-top:16px;">
                            <div class="otp-notice">
                                <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                                <div class="otp-notice-text">
                                    <strong>ต้องยืนยัน OTP เพื่อเปลี่ยนอีเมล</strong>
                                    กดปุ่ม "ส่ง OTP" แล้วกรอกรหัสที่ได้รับในอีเมลใหม่
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">รหัส OTP (6 หลัก)</label>
                                <input type="text" name="otp" placeholder="000000" maxlength="6"
                                       pattern="[0-9]{6}" class="form-input">
                                @error('otp') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                            <button type="button"
                                    onclick="requestOtp()"
                                    class="w-full py-2.5 text-[13px] font-semibold border border-green-200 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition"
                                    style="font-family:'Kanit',sans-serif;">
                                ส่ง OTP ไปยังอีเมลใหม่
                            </button>
                            <div id="otpMessage" class="otp-msg"></div>
                        </div>

                        <div class="section-divider" style="margin-top:20px;">บันทึก</div>

                        <button type="submit"
                                class="w-full py-2.5 bg-[#e86c2a] hover:bg-[#d05a1a] text-white font-semibold rounded-lg transition flex items-center justify-center gap-2 text-[14px]"
                                style="font-family:'Kanit',sans-serif;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M5 13l4 4L19 7"/>
                            </svg>
                            บันทึกการเปลี่ยนแปลง
                        </button>
                    </div>

                    {{-- RIGHT: avatar display (staff only: coach / court_assistant), read-only --}}
                    @if ($isStaff)
                    <div class="avatar-wrap">
                        <div class="avatar-frame">
                            @if (!empty($user->avatar))
                                <img src="{{ asset('storage/' . $user->avatar) }}"
                                     alt="Avatar" class="avatar-preview">
                            @else
                                <div class="avatar-preview">
                                    <div class="avatar-placeholder-box">
                                        <span class="avatar-placeholder-text">No Image</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif

                </div>
            </form>
        </div>
    </div>

    {{-- ACCOUNT META (read-only: password + role) --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm mb-6">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-[#e86c2a]/10 flex items-center justify-center border border-[#e86c2a]/20">
                <svg class="w-4 h-4 text-[#e86c2a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div>
                <div class="text-[14px] font-semibold text-gray-800" style="font-family:'Kanit',sans-serif;">ความปลอดภัยและสิทธิ์</div>
                <div class="text-[12px] text-gray-400">รหัสผ่านและประเภทสมาชิก</div>
            </div>
        </div>

        <div class="px-5">
            <div class="info-row">
                <div class="info-icon">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <div class="info-label">รหัสผ่าน</div>
                    <div class="info-value tracking-widest text-gray-400">••••••••</div>
                </div>
            </div>

            <div class="info-row">
                <div class="info-icon">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <div>
                    @php
                        $roleLabel = match(true) {
                            $user->role === 'superadmin' => 'ผู้ดูแลระบบ', // Super Admin
                            $user->role === 'admin' => 'ผู้ดูแลระบบ',            // Admin
                            default => $user->membershipTypeLabel(),
                        };
                    @endphp
                    <div class="info-label">ประเภทสมาชิก</div>
                    <div class="info-value">
                        {{ $roleLabel }}
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<script>
const originalEmail = '{{ $user->email }}';
const emailInput    = document.getElementById('emailInput');
const otpSection    = document.getElementById('otpSection');

emailInput.addEventListener('input', function () {
    otpSection.style.display = this.value !== originalEmail ? 'block' : 'none';
});

@if (session('success'))
    document.addEventListener('DOMContentLoaded', () => {
        Swal.fire({ icon:'success', title:'สำเร็จ!',
            text: '{{ session('success') }}',
            confirmButtonText:'ตกลง', confirmButtonColor:'#e86c2a' });
    });
@endif

function requestOtp() {
    const email = emailInput.value.trim();
    const box   = document.getElementById('otpMessage');
    if (email === originalEmail) {
        box.textContent = 'อีเมลไม่มีการเปลี่ยนแปลง';
        box.className   = 'otp-msg err'; return;
    }
    const fd = new FormData();
    fd.append('email', email);
    fd.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    box.innerHTML = 'กำลังส่ง OTP...';
    box.className = 'otp-msg ok';
    fetch('{{ route('profile.request-otp-email') }}', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                box.innerHTML = 'ส่ง OTP ไปยัง <strong>' + email + '</strong> แล้ว';
                box.className = 'otp-msg ok';
            } else {
                box.textContent = d.message || 'ไม่สามารถส่ง OTP ได้';
                box.className   = 'otp-msg err';
            }
        })
        .catch(() => { box.textContent = 'เกิดข้อผิดพลาด'; box.className = 'otp-msg err'; });
}
</script>
@endsection
