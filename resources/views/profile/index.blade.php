@extends('layouts.app')

@section('title', 'บัญชีของฉัน · BCBS')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');
.pf-main { font-family:'Sarabun','Kanit',sans-serif; }
.pf-main h1,.pf-main h2,.pf-main h3, .font-kanit { font-family:'Kanit',sans-serif; }

.form-label { display:block; font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase; margin-bottom:6px; }
.form-input {
    width:100%; padding:10px 13px; font-size:14px; font-family:'Sarabun',sans-serif;
    color:#111827; background:#fafafa; border:1.5px solid #e5e7eb; border-radius:8px;
    outline:none; transition:border-color .2s, box-shadow .2s;
}
.form-input::placeholder { color:#d1d5db; }
.form-input:focus { border-color:#e86c2a; background:#fff; box-shadow:0 0 0 3px rgba(232,108,42,.08); }
.form-group { margin-bottom:16px; }

.section-divider { display:flex; align-items:center; gap:10px; color:#d1d5db; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.08em; }
.section-divider::before,.section-divider::after { content:''; flex:1; height:1px; background:#f1f3f5; }
</style>

<div class="pf-main bg-slate-50 min-h-screen text-[#111827] pb-12">
<div class="max-w-[1200px] mx-auto px-4 py-8">

    {{-- Title --}}
    <div class="mb-6">
        <h1 class="text-[32px] font-bold text-gray-900 tracking-tight">บัญชีของฉัน</h1>
        <p class="text-gray-500 text-[14px] mt-0.5">จัดการข้อมูลสมาชิก THATAHOMECOURT</p>
    </div>

    {{-- Alert --}}
    @if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const msgs = {!! json_encode($errors->all()) !!};
            Swal.fire({ icon:'error', title:'กรุณาตรวจสอบข้อมูล', html: msgs.join('<br>'), confirmButtonText:'ตกลง', confirmButtonColor:'#e86c2a' });
        });
    </script>
    @endif
    @if (session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Swal.fire({ icon:'success', title:'สำเร็จ!', text: '{{ session('success') }}', confirmButtonText:'ตกลง', confirmButtonColor:'#e86c2a' });
        });
    </script>
    @endif

    {{-- BANNER ยาวเต็มความกว้าง --}}
    <div class="w-full h-[200px] md:h-[240px] rounded-xl overflow-hidden shadow-sm relative mb-8 group bg-gray-900">
        <img src="https://images.unsplash.com/photo-1504450758481-7338eba7524a?q=80&w=1400&auto=format&fit=crop"
             style="filter:grayscale(60%) sepia(20%); opacity: 0.6;" alt="Profile Banner"
             class="w-full h-full object-cover transition duration-500 group-hover:scale-105 group-hover:opacity-80">
        <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/40 to-transparent"></div>
        <div class="absolute inset-y-0 left-0 flex flex-col justify-center px-8 md:px-12 text-white">
            <p class="text-[12px] font-semibold tracking-[.18em] uppercase text-[#e86c2a] mb-2">Member Profile</p>
            <h2 class="text-[28px] md:text-[36px] font-bold tracking-wide">{{ $user->name }}</h2>
            <p class="text-gray-300 text-[15px] mt-1">{{ $user->email }}</p>
        </div>
    </div>

    {{-- แบ่ง 2 ฝั่ง ซ้าย(ข้อมูล+รูป) - ขวา(รหัสผ่าน) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
        
        {{-- ================= ฝั่งซ้าย: ข้อมูลบัญชีและรูปโปรไฟล์ ================= --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm flex flex-col h-full">
            <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3 bg-gray-50/50">
                <div class="w-10 h-10 rounded-lg bg-[#e86c2a]/10 flex items-center justify-center border border-[#e86c2a]/20">
                    <svg class="w-5 h-5 text-[#e86c2a]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                    <div class="text-[16px] font-semibold text-gray-800">ข้อมูลบัญชี</div>
                    <div class="text-[13px] text-gray-500">แก้ไขข้อมูลที่บันทึกในระบบ</div>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="p-6 flex flex-col flex-grow">
                @csrf
                
                {{-- อัปโหลดรูปโปรไฟล์ --}}
                <div class="flex items-center gap-5 mb-5 p-4 bg-gray-50/50 rounded-lg border border-gray-100">
                    <div class="relative w-[72px] h-[72px] rounded-full border-2 border-white shadow-sm bg-orange-50 flex items-center justify-center overflow-hidden group cursor-pointer flex-shrink-0" onclick="document.getElementById('profileImageInput').click()">
                        <img id="imagePreview" src="{{ !empty($user->avatar) ? asset('storage/' . $user->avatar) : '' }}" class="{{ !empty($user->avatar) ? '' : 'hidden' }} w-full h-full object-cover">
                        <span id="imageFallback" class="{{ !empty($user->avatar) ? 'hidden' : '' }} text-3xl font-kanit font-bold text-orange-400">{{ mb_strtoupper(mb_substr($user->name ?? $user->us_name, 0, 1)) }}</span>
                        
                        <div class="absolute inset-0 bg-black/40 hidden group-hover:flex flex-col items-center justify-center transition">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                    </div>
                    
                    <div class="flex-1">
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="document.getElementById('profileImageInput').click()" class="text-[12px] font-medium bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg transition shadow-sm cursor-pointer">เปลี่ยนรูป</button>
                            
                            {{-- ปุ่มลบรูป --}}
                            <button type="button" id="removeAvatarBtn" class="{{ !empty($user->avatar) ? '' : 'hidden' }} text-[12px] font-medium text-red-600 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition shadow-sm cursor-pointer">ลบรูปโปรไฟล์</button>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1.5">รองรับ JPG, PNG, WEBP ขนาดไม่เกิน 2MB</p>
                        
                        <input type="file" name="avatar" id="profileImageInput" accept="image/png, image/jpeg, image/jpg, image/webp" class="hidden">
                        {{-- ตัวแปรลับส่งไปบอก Controller ว่ากดลบรูป --}}
                        <input type="hidden" name="remove_avatar" id="removeAvatarInput" value="0">
                    </div>
                </div>

                {{-- กรอกข้อมูล --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-group mb-0">
                        <label class="form-label">ชื่อผู้ใช้ (Username)</label>
                        <input type="text" name="us_name" value="{{ old('us_name', $user->us_name) }}" class="form-input" placeholder="ชื่อที่ใช้เข้าระบบ">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">ชื่อ-นามสกุล (Name)</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-input" placeholder="ชื่อและนามสกุลจริง">
                    </div>
                </div>

                <div class="section-divider" style="margin: 10px 0 0">ข้อมูลติดต่อ</div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 mb-2">
                    <div class="form-group mb-0">
                        <label class="form-label">อีเมล (Email)</label>
                        <input type="email" name="email" id="emailInput" value="{{ old('email', $user->email) }}" class="form-input" placeholder="example@email.com">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">เบอร์โทรศัพท์ (Phone)</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-input" placeholder="08xxxxxxxx" maxlength="10">
                    </div>
                </div>

                <div id="otpSection" style="display:none;" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-2 mb-4">
                    <p class="text-[13px] text-yellow-800 mb-3 font-medium">คุณมีการเปลี่ยนแปลงอีเมล กรุณายืนยัน OTP</p>
                    <div class="flex gap-2 mb-1">
                        <input type="text" name="otp" placeholder="รหัส OTP 6 หลัก" maxlength="6" class="form-input flex-1 bg-white text-center tracking-widest font-semibold">
                        <button type="button" onclick="requestOtp()" class="px-4 bg-white border border-yellow-300 text-yellow-700 rounded-lg hover:bg-yellow-100 text-[13px] font-semibold transition">ส่ง OTP</button>
                    </div>
                    <div id="otpMessage" class="text-[12px] mt-2 hidden px-2 py-1.5 rounded bg-white border"></div>
                </div>

                <div class="mt-auto pt-4"> 
                        <div class="section-divider" style="margin: 10px 0 14px;"></div>
                        <button type="submit" class="w-full py-2.5 bg-[#e86c2a] hover:bg-[#d05a1a] text-white font-semibold rounded-lg transition flex justify-center items-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        บันทึกข้อมูลส่วนตัว
                    </button>
                </div>
            </form>
        </div>

        {{-- ================= ฝั่งขวา: เปลี่ยนรหัสผ่าน ================= --}}
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm flex flex-col h-full">
            <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3 bg-gray-50/50">
                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center border border-gray-200">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div>
                    <div class="text-[16px] font-semibold text-gray-800">ตั้งรหัสผ่านใหม่</div>
                    <div class="text-[13px] text-gray-500">กรอกรหัสผ่านปัจจุบันเพื่อเปลี่ยนรหัสผ่านใหม่</div>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" id="passwordForm" class="p-6 flex flex-col flex-grow">
                @csrf
                <div class="form-group mt-2">
                    <label class="form-label">รหัสผ่านปัจจุบัน</label>
                    <input type="password" name="current_password" id="current_password" class="form-input" placeholder="ใส่รหัสผ่านที่ใช้ในปัจจุบัน">
                </div>
                
                <div class="form-group mt-4">
                    <label class="form-label">รหัสผ่านใหม่</label>
                    <input type="password" name="password" id="new_password" class="form-input" placeholder="อย่างน้อย 6 ตัวอักษร">
                </div>
                
                <div class="form-group mb-0 mt-4">
                    <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                    <input type="password" name="password_confirmation" id="confirm_password" class="form-input" placeholder="กรอกรหัสผ่านใหม่อีกครั้ง">
                </div>

                {{-- กล่องแจ้งเตือน Text Alert ของรหัสผ่าน --}}
                <div id="passwordAlert" class="hidden text-[13px] px-3 py-2 rounded bg-red-50 border border-red-200 text-red-600 mt-4"></div>

                <div class="mt-auto pt-4">
                    <div class="section-divider" style="margin: 10px 0 14px;"></div>
                    <button type="submit" class="w-full py-2.5 bg-gray-800 hover:bg-gray-900 text-white font-semibold rounded-lg transition flex justify-center items-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        บันทึกรหัสผ่านใหม่
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
</div>

<script>
// ==========================================
// 1. สคริปต์พรีวิวและลบรูปภาพ
// ==========================================
const fileInput = document.getElementById('profileImageInput');
const imagePreview = document.getElementById('imagePreview');
const imageFallback = document.getElementById('imageFallback');
const removeAvatarBtn = document.getElementById('removeAvatarBtn');
const removeAvatarInput = document.getElementById('removeAvatarInput');

if(fileInput) {
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({ icon: 'error', title: 'ไฟล์ใหญ่เกินไป', text: 'กรุณาอัปโหลดรูปขนาดไม่เกิน 2MB' });
                this.value = ''; return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.classList.remove('hidden');
                if(imageFallback) imageFallback.classList.add('hidden');
                if(removeAvatarBtn) removeAvatarBtn.classList.remove('hidden');
                if(removeAvatarInput) removeAvatarInput.value = '0'; // ยกเลิกสถานะการลบรูปถ้าเลือกใหม่
            }
            reader.readAsDataURL(file);
        }
    });
}

if(removeAvatarBtn) {
    removeAvatarBtn.addEventListener('click', function() {
        imagePreview.classList.add('hidden');
        imagePreview.src = '';
        if(imageFallback) imageFallback.classList.remove('hidden');
        if(fileInput) fileInput.value = '';
        this.classList.add('hidden');
        if(removeAvatarInput) removeAvatarInput.value = '1'; // ส่งค่า 1 เพื่อบอก Controller ให้ลบรูป
    });
}

// ==========================================
// 2. สคริปต์ตรวจสอบฟอร์มเปลี่ยนรหัสผ่านก่อนส่ง (Client-side Validation)
// ==========================================
const passwordForm = document.getElementById('passwordForm');
if (passwordForm) {
    passwordForm.addEventListener('submit', function(e) {
        const current = document.getElementById('current_password').value;
        const newPass = document.getElementById('new_password').value;
        const confirm = document.getElementById('confirm_password').value;
        const alertBox = document.getElementById('passwordAlert');

        // รีเซ็ตการแสดงผล Alert
        alertBox.classList.add('hidden');
        
        // เช็คว่าปล่อยว่างหรือเปล่า
        if (!current || !newPass || !confirm) {
            e.preventDefault();
            showAlert('กรุณากรอกข้อมูลรหัสผ่านให้ครบทุกช่อง');
            return;
        }
        
        // เช็คความยาวรหัสผ่านใหม่
        if (newPass.length < 6) {
            e.preventDefault();
            showAlert('รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
            return;
        }
        
        // เช็คว่ารหัสผ่านใหม่กับการยืนยันตรงกันไหม
        if (newPass !== confirm) {
            e.preventDefault();
            showAlert('การยืนยันรหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง');
            return;
        }

        // ฟังก์ชันสำหรับแสดง Alert
        function showAlert(msg) {
            alertBox.textContent = msg;
            alertBox.classList.remove('hidden');
        }
    });
}

// ==========================================
// 3. สคริปต์จัดการ OTP และอีเมล
// ==========================================
const originalEmail = '{{ $user->email }}';
const emailInput = document.getElementById('emailInput');
const otpSection = document.getElementById('otpSection');

if(emailInput) {
    emailInput.addEventListener('input', function () {
        otpSection.style.display = this.value !== originalEmail ? 'block' : 'none';
    });
}

function requestOtp() {
    const email = emailInput.value.trim();
    const box = document.getElementById('otpMessage');
    box.style.display = 'block';

    if (email === originalEmail) {
        box.textContent = 'อีเมลยังไม่มีการเปลี่ยนแปลง';
        box.className = 'text-[12px] mt-2 px-3 py-2 rounded bg-red-50 border border-red-200 text-red-600 block';
        return;
    }

    const fd = new FormData();
    fd.append('email', email);
    fd.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    
    box.textContent = 'กำลังส่ง OTP...';
    box.className = 'text-[12px] mt-2 px-3 py-2 rounded bg-blue-50 border border-blue-200 text-blue-600 block';
    
    fetch('{{ route("profile.request-otp-email") }}', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                box.textContent = 'ระบบได้ส่ง OTP ไปยัง ' + email + ' แล้ว';
                box.className = 'text-[12px] mt-2 px-3 py-2 rounded bg-green-50 border border-green-200 text-green-700 block';
            } else {
                box.textContent = d.message || 'เกิดข้อผิดพลาด ไม่สามารถส่ง OTP ได้';
                box.className = 'text-[12px] mt-2 px-3 py-2 rounded bg-red-50 border border-red-200 text-red-600 block';
            }
        }).catch(() => { 
            box.textContent = 'เกิดข้อผิดพลาดของระบบในการเชื่อมต่อ'; 
            box.className = 'text-[12px] mt-2 px-3 py-2 rounded bg-red-50 border border-red-200 text-red-600 block';
        });
}
</script>
@endsection