@extends('layouts.app')

@section('title', 'เปลี่ยนรหัสผ่าน · BCBS')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');
.pf-edit { font-family:'Sarabun','Kanit',sans-serif; }
.pf-edit h1,.pf-edit h2,.pf-edit h3 { font-family:'Kanit',sans-serif; }
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

.section-divider {
    display:flex; align-items:center; gap:10px;
    margin:20px 0 16px; color:#d1d5db; font-size:11px; font-weight:600;
    text-transform:uppercase; letter-spacing:.08em;
}
.section-divider::before,.section-divider::after { content:''; flex:1; height:1px; background:#f1f3f5; }
</style>

<div class="pf-edit bg-white min-h-screen text-[#111827]">
<div class="max-w-[520px] mx-auto px-4 py-8">

    {{-- HEADER --}}
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('profile') }}"
           class="inline-flex items-center gap-1.5 text-[13px] text-gray-500 hover:text-gray-800 transition font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            กลับ
        </a>
        <div class="h-4 w-px bg-gray-200"></div>
        <div>
            <h1 class="text-[22px] font-semibold text-gray-800 tracking-tight">เปลี่ยนรหัสผ่าน</h1>
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

    {{-- Password --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-[#e86c2a]/10 border border-[#e86c2a]/20 flex items-center justify-center">
                <svg class="w-4 h-4 text-[#e86c2a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div class="p-5">

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

                <form method="POST" action="{{ route('profile.update') }}" id="profileForm">
                    @csrf

                    <div class="form-group">
                        <label class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" name="us_name" value="{{ $user->us_name }}" required
                               placeholder="กรอกชื่อของคุณ" class="form-input">
                        @error('us_name') <div class="form-error">{{ $message }}</div> @enderror
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
                                class="w-full py-2.5 text-[13px] font-semibold border border-green-200 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 cursor-pointer transition"
                                style="font-family:'Kanit',sans-serif;">
                            ส่ง OTP ไปยังอีเมลใหม่
                        </button>
                        <div id="otpMessage" class="otp-msg"></div>
                    </div>

                    <div class="section-divider" style="margin-top:20px;">บันทึก</div>

                    <button type="submit"
                            class="w-full py-2.5 bg-[#e86c2a] hover:bg-[#d05a1a] text-white font-semibold rounded-lg transition flex items-center justify-center gap-2 text-[14px] cursor-pointer"
                            style="font-family:'Kanit',sans-serif;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M5 13l4 4L19 7"/>
                        </svg>
                        บันทึกการเปลี่ยนแปลง
                    </button>
                </form>
            </div>
        </div>
        <div class="p-5">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">รหัสผ่านเดิม</label>
                    <input type="password" name="current_password" placeholder="ใส่รหัสผ่านปัจจุบัน" class="form-input">
                    @error('current_password') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">รหัสผ่านใหม่</label>
                    <input type="password" name="password" placeholder="อย่างน้อย 6 ตัวอักษร" class="form-input">
                    @error('password') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">ยืนยันรหัสผ่าน</label>
                    <input type="password" name="password_confirmation" placeholder="••••••••" class="form-input">
                </div>
                <div class="section-divider" style="margin-top:20px;">บันทึก</div>
                <button type="submit"
                        class="w-full py-2.5 bg-[#e86c2a] hover:bg-[#d05a1a] text-white font-semibold rounded-lg transition flex items-center justify-center gap-2 text-[14px]"
                        style="font-family:'Kanit',sans-serif;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[14px] font-semibold text-gray-800" style="font-family:'Kanit',sans-serif;">ความปลอดภัย</div>
                    <div class="text-[12px] text-gray-400">เปลี่ยนรหัสผ่านของคุณ</div>
                </div>
            </div>
            <div class="p-5">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">รหัสผ่านเดิม</label>
                        <input type="password" name="current_password" placeholder="ใส่รหัสผ่านปัจจุบัน" class="form-input">
                        @error('current_password') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">รหัสผ่านใหม่</label>
                        <input type="password" name="password" placeholder="อย่างน้อย 6 ตัวอักษร" class="form-input">
                        @error('password') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">ยืนยันรหัสผ่าน</label>
                        <input type="password" name="password_confirmation" placeholder="••••••••" class="form-input">
                    </div>
                    <div class="section-divider" style="margin-top:20px;">บันทึก</div>
                    <button type="submit"
                            class="w-full py-2.5 bg-[#e86c2a] hover:bg-[#d05a1a] text-white font-semibold rounded-lg transition flex items-center justify-center gap-2 text-[14px] cursor-pointer"
                            style="font-family:'Kanit',sans-serif;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        เปลี่ยนรหัสผ่าน
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
</div>

<script>
@if (session('success'))
    document.addEventListener('DOMContentLoaded', () => {
        Swal.fire({ icon:'success', title:'สำเร็จ!',
            text: '{{ session('success') }}',
            confirmButtonText:'ตกลง', confirmButtonColor:'#e86c2a' });
    });
@endif
</script>
@endsection
