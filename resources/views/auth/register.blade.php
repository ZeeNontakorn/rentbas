@extends('layouts.app')

@section('title', 'สมัครสมาชิก')

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
    margin-bottom: 24px;
}

/* Tab switcher */
.auth-tabs {
    display: flex;
    background: #e5e7eb;
    border-radius: 10px;
    padding: 4px;
    margin-bottom: 20px;
    width: 100%;
    max-width: 380px;
}

.auth-tab {
    flex: 1;
    padding: 9px 0;
    text-align: center;
    font-family: 'Kanit', sans-serif;
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
    border-radius: 7px;
    cursor: pointer;
    text-decoration: none;
    transition: background .2s, color .2s;
}

.auth-tab.active {
    background: #fff;
    color: #111;
    font-weight: 600;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
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

/* Form */
.form-group {
    margin-bottom: 16px;
}

.form-label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
}

.form-input {
    width: 100%;
    padding: 11px 14px;
    font-size: 14px;
    font-family: 'Sarabun', sans-serif;
    color: #111;
    background: #fff;
    border: 1.5px solid #d1d5db;
    border-radius: 10px;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
}

.form-input::placeholder {
    color: #9ca3af;
}

.form-input:focus {
    border-color: #e86c2a;
    box-shadow: 0 0 0 3px rgba(232, 108, 42, 0.12);
}

.form-input.invalid {
    border-color: #dc2626;
}

.form-input.invalid:focus {
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
}

.field-error {
    display: none;
    color: #dc2626;
    font-size: 12px;
    margin-top: 6px;
    line-height: 1.4;
}

.field-error.show {
    display: block;
}

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
    margin-top: 4px;
    transition: background .2s, transform .15s;
    text-align: center;
}

.btn-submit:hover {
    background: #222;
    transform: translateY(-1px);
}

/* Error */
.auth-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13px;
    margin-bottom: 16px;
}

/* ข้อกำหนดการสมัครสมาชิก */

.consent-check{
    display:flex;
    align-items:flex-start;
    gap:10px;
    font-size:13px;
    color:#374151;
    line-height:1.6;
}

.consent-check input{
    margin-top:3px;
    width:16px;
    height:16px;
    cursor:pointer;
}

.policy-link{
    color:#2563eb;
    text-decoration:none;
    font-weight:600;
}

.policy-link:hover{
    text-decoration:underline;
}

.btn-submit:disabled{
    background:#9ca3af;
    cursor:not-allowed;
    transform:none;
}

.btn-submit:disabled:hover{
    background:#9ca3af;
}

/* Consent Modal */
.modal-overlay{
    display:none;
    position:fixed;
    top:0; left:0; right:0; bottom:0;
    background:rgba(17,17,17,0.55);
    align-items:center;
    justify-content:center;
    padding:16px;
    z-index:1000;
}

.modal-overlay.show{
    display:flex;
}

.modal-box{
    background:#fff;
    border-radius:16px;
    padding:24px 24px 20px;
    width:100%;
    max-width:420px;
    max-height:85vh;
    overflow-y:auto;
    box-shadow:0 12px 40px rgba(0,0,0,0.25);
}

.modal-box h3{
    font-family:'Kanit', sans-serif;
    font-size:17px;
    font-weight:600;
    color:#111;
    margin-bottom:14px;
}

.modal-content p{
    font-size:13px;
    color:#4b5563;
    line-height:1.7;
    margin-bottom:12px;
}

.modal-actions{
    display:flex;
    gap:10px;
    margin-top:18px;
}

.btn-cancel{
    flex:1;
    padding:11px;
    background:#f3f4f6;
    color:#374151;
    font-family:'Kanit', sans-serif;
    font-size:14px;
    font-weight:600;
    border:none;
    border-radius:10px;
    cursor:pointer;
    transition: background .2s;
}

.btn-cancel:hover{
    background:#e5e7eb;
}

.btn-confirm{
    flex:1;
    padding:11px;
    background:#111;
    color:#fff;
    font-family:'Kanit', sans-serif;
    font-size:14px;
    font-weight:600;
    border:none;
    border-radius:10px;
    cursor:pointer;
    transition: background .2s;
}

.btn-confirm:hover:not(:disabled){
    background:#222;
}

.btn-confirm:disabled{
    background:#9ca3af;
    cursor:not-allowed;
}
</style>

<div class="auth-page">
    @include('components.mail-loading-overlay')



    {{-- Title --}}
    <h1 class="auth-title">ยินดีต้อนรับ</h1>
    <p class="auth-subtitle">สมัครสมาชิกเพื่อจองสนามบาสเกตบอล</p>

    {{-- Tabs --}}
    <div class="auth-tabs">
        <a href="{{ route('login') }}" class="auth-tab">เข้าสู่ระบบ</a>
        <a href="{{ route('register') }}" class="auth-tab active">สมัครสมาชิก</a>
    </div>

    {{-- Card --}}
    <div class="auth-card">
        <p class="auth-card-title">สร้างบัญชีใหม่เพื่อเริ่มจองสนาม</p>

        @if ($errors->any())
        <div class="auth-error">{{ $errors->first() }}</div>
        @endif

        {{-- novalidate: ปิด HTML5 validation ของ browser เพื่อใช้ข้อความแจ้งเตือนของระบบเองแทน --}}
        <form method="POST" action="{{ route('register') }}" id="registerForm" novalidate>
            @csrf

            <div class="form-group">
                <label class="form-label">ชื่อผู้ใช้</label>
                <input type="text" name="us_name" id="regName" value="{{ old('us_name') }}" placeholder="username"
                    class="form-input">
                <span class="field-error" id="error-name"></span>
            </div>

            <div class="form-group">
                <label class="form-label">ชื่อจริง</label>
                <input type="text" name="name" id="regFullName" value="{{ old('name') }}" placeholder="ชื่อ-นามสกุล"
                    class="form-input">
                <span class="field-error" id="error-fullname"></span>
            </div>

            <div class="form-group">
                <label class="form-label">อีเมล</label>
                <input type="email" name="email" id="regEmail" value="{{ old('email') }}" placeholder="example@email.com"
                    class="form-input">
                <span class="field-error" id="error-email"></span>
            </div>

            <div class="form-group">
                <label class="form-label">เบอร์โทรศัพท์</label>
                <input type="tel" name="phone" id="regPhone" value="{{ old('phone') }}" placeholder="08X-XXX-XXXX"
                    inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    maxlength="10" class="form-input">
                <span class="field-error" id="error-phone"></span>
            </div>

            <div class="form-group">
                <label class="form-label">รหัสผ่าน</label>
                <input type="password" name="password" id="regPassword" placeholder="••••••••" class="form-input">
                <span class="field-error" id="error-password"></span>
            </div>

            <div class="form-group">
                <label class="form-label">ยืนยันรหัสผ่าน</label>
                <input type="password" name="password_confirmation" id="regPasswordConfirm" placeholder="••••••••"
                    class="form-input">
                <span class="field-error" id="error-password_confirmation"></span>
            </div>

            <button type="submit" class="btn-submit" id="openConsentModal">สมัครสมาชิก</button>
        </form>

        <!-- Consent Modal -->
        <div id="consentModal" class="modal-overlay">
            <div class="modal-box">

                <h3>ข้อกำหนดในการเก็บรวบรวมและใช้ข้อมูลส่วนบุคคล</h3>

                <div class="modal-content">

                    <p>
                        ระบบมีความจำเป็นต้องเก็บรวบรวมข้อมูลส่วนบุคคลของท่าน
                        เช่น ชื่อผู้ใช้ อีเมล เบอร์โทรศัพท์ และข้อมูลที่เกี่ยวข้องกับการใช้งาน
                        เพื่อใช้ในการสมัครสมาชิก ยืนยันตัวตน การจองสนาม
                        การติดต่อผู้ใช้งาน และการให้บริการภายในระบบ
                    </p>

                    <p>
                        ข้อมูลของท่านจะถูกจัดเก็บอย่างเหมาะสม และจะไม่เปิดเผยแก่บุคคลภายนอก
                        เว้นแต่ได้รับความยินยอมหรือเป็นไปตามที่กฎหมายกำหนด
                    </p>

                    <label class="consent-check">
                        <input type="checkbox" id="acceptPolicy">
                        ข้าพเจ้าได้อ่านและยอมรับข้อกำหนดในการเก็บรวบรวมและใช้ข้อมูลส่วนบุคคล
                    </label>

                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" id="cancelConsentBtn">ยกเลิก</button>
                    <button type="button" class="btn-confirm" id="confirmConsentBtn" disabled>ยืนยันและสมัครสมาชิก</button>
                </div>

            </div>
        </div>
    </div>

</div>

<script>
(function () {
    const form = document.getElementById('registerForm');
    const modal = document.getElementById('consentModal');
    const checkbox = document.getElementById('acceptPolicy');
    const confirmBtn = document.getElementById('confirmConsentBtn');
    const cancelBtn = document.getElementById('cancelConsentBtn');

    let hasConsented = false;

    // ผูก field key กับ input element
    const fields = {
        name: document.getElementById('regName'),
        fullname: document.getElementById('regFullName'),
        email: document.getElementById('regEmail'),
        phone: document.getElementById('regPhone'),
        password: document.getElementById('regPassword'),
        password_confirmation: document.getElementById('regPasswordConfirm'),
    };

    function showError(key, message) {
        const input = fields[key];
        const errorEl = document.getElementById('error-' + key);
        input.classList.add('invalid');
        errorEl.textContent = message;
        errorEl.classList.add('show');
    }

    function clearError(key) {
        const input = fields[key];
        const errorEl = document.getElementById('error-' + key);
        input.classList.remove('invalid');
        errorEl.textContent = '';
        errorEl.classList.remove('show');
    }

    function clearAllErrors() {
        Object.keys(fields).forEach(clearError);
    }

    // ล้าง error ของช่องนั้นๆ ทันทีที่ผู้ใช้เริ่มพิมพ์แก้ไข
    Object.keys(fields).forEach(function (key) {
        fields[key].addEventListener('input', function () {
            clearError(key);
        });
    });

    function validateForm() {
        clearAllErrors();
        let isValid = true;
        let firstInvalid = null;

        const name = fields.name.value.trim();
        const fullname = fields.fullname.value.trim();
        const email = fields.email.value.trim();
        const phone = fields.phone.value.trim();
        const password = fields.password.value;
        const passwordConfirm = fields.password_confirmation.value;

        // ชื่อผู้ใช้
        if (name === '') {
            showError('name', 'กรุณากรอกชื่อผู้ใช้');
            isValid = false; firstInvalid = firstInvalid || 'name';
        } else if (name.length < 3 || name.length > 20) {
            showError('name', 'ชื่อผู้ใช้ต้องมีความยาว 3-20 ตัวอักษร');
            isValid = false; firstInvalid = firstInvalid || 'name';
        } else if (!/^[a-zA-Z0-9_]+$/.test(name)) {
            showError('name', 'ชื่อผู้ใช้ใช้ได้เฉพาะตัวอักษรภาษาอังกฤษ ตัวเลข และ _ เท่านั้น');
            isValid = false; firstInvalid = firstInvalid || 'name';
        }

        // ชื่อจริง
        if (fullname === '') {
            showError('fullname', 'กรุณากรอกชื่อจริง');
            isValid = false; firstInvalid = firstInvalid || 'fullname';
        } else if (fullname.length > 255) {
            showError('fullname', 'ชื่อจริงต้องมีความยาวไม่เกิน 255 ตัวอักษร');
            isValid = false; firstInvalid = firstInvalid || 'fullname';
        }

        // อีเมล
        if (email === '') {
            showError('email', 'กรุณากรอกอีเมล');
            isValid = false; firstInvalid = firstInvalid || 'email';
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError('email', 'รูปแบบอีเมลไม่ถูกต้อง');
            isValid = false; firstInvalid = firstInvalid || 'email';
        }

        // เบอร์โทรศัพท์ (ต้องเป็นตัวเลข 10 หลักพอดี ขึ้นต้นด้วย 0)
        if (phone === '') {
            showError('phone', 'กรุณากรอกเบอร์โทรศัพท์');
            isValid = false; firstInvalid = firstInvalid || 'phone';
        } else if (!/^0[0-9]{9}$/.test(phone)) {
            showError('phone', 'เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก และขึ้นต้นด้วย 0');
            isValid = false; firstInvalid = firstInvalid || 'phone';
        }

        // รหัสผ่าน
        if (password === '') {
            showError('password', 'กรุณากรอกรหัสผ่าน');
            isValid = false; firstInvalid = firstInvalid || 'password';
        } else if (password.length < 6) {
            showError('password', 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
            isValid = false; firstInvalid = firstInvalid || 'password';
        }

        // ยืนยันรหัสผ่าน
        if (passwordConfirm === '') {
            showError('password_confirmation', 'กรุณายืนยันรหัสผ่าน');
            isValid = false; firstInvalid = firstInvalid || 'password_confirmation';
        } else if (passwordConfirm !== password) {
            showError('password_confirmation', 'รหัสผ่านยืนยันไม่ตรงกับรหัสผ่านที่กรอก');
            isValid = false; firstInvalid = firstInvalid || 'password_confirmation';
        }

        if (firstInvalid) {
            fields[firstInvalid].focus();
        }

        return isValid;
    }

    // ทุกครั้งที่กด submit: preventDefault เสมอ แล้วค่อยตัดสินใจตามผล validate
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!validateForm()) {
            return; // มีช่องไม่ถูกต้อง ไม่เปิด modal และไม่ submit
        }

        if (!hasConsented) {
            modal.classList.add('show');
        } else {
            form.submit();
        }
    });

    // ปุ่มยืนยันใน modal จะกดได้ก็ต่อเมื่อติ๊กยอมรับข้อกำหนดแล้วเท่านั้น
    checkbox.addEventListener('change', function () {
        confirmBtn.disabled = !this.checked;
    });

    confirmBtn.addEventListener('click', function () {
        if (!checkbox.checked) return;
        hasConsented = true;
        modal.classList.remove('show');
        showMailLoadingOverlay('กำลังส่งรหัส OTP ไปยังอีเมลของคุณ...');
        confirmBtn.disabled = true;

        form.submit();
    });

    cancelBtn.addEventListener('click', function () {
        modal.classList.remove('show');
    });

    // ปิด modal เมื่อคลิกพื้นหลังนอกกล่อง
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            modal.classList.remove('show');
        }
    });
})();
</script>
@endsection
