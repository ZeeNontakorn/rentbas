@extends('layouts.app')

@section('title', 'เข้าสู่ระบบ')

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
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
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
.form-group { margin-bottom: 16px; }
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
.form-input::placeholder { color: #9ca3af; }
.form-input:focus {
    border-color: #e86c2a;
    box-shadow: 0 0 0 3px rgba(232,108,42,0.12);
}

.form-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    margin-top: -4px;
}
.form-remember {
    display: flex; align-items: center; gap: 6px;
    font-size: 13px; color: #374151;
}
.form-remember input[type=checkbox] {
    width: 15px; height: 15px;
    accent-color: #e86c2a;
    cursor: pointer;
}
.form-forgot {
    font-size: 13px;
    color: #e86c2a;
    font-weight: 500;
    text-decoration: none;
}
.form-forgot:hover { text-decoration: underline; }

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
.btn-submit:hover { background: #222; transform: translateY(-1px); }
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
</style>

<div class="auth-page">

    {{-- Brand --}}
    <div class="auth-brand">
        <div class="auth-brand-ball">🏀</div>
        <span class="auth-brand-name">Basketball Court Booking</span>
    </div>

    {{-- Title --}}
    <h1 class="auth-title">ยินดีต้อนรับ</h1>
    <p class="auth-subtitle">เข้าสู่ระบบเพื่อจองสนามบาสเกตบอล</p>

    {{-- Tabs --}}
    <div class="auth-tabs">
        <a href="{{ route('login') }}" class="auth-tab active">เข้าสู่ระบบ</a>
        <a href="{{ route('register') }}" class="auth-tab">สมัครสมาชิก</a>
    </div>

    {{-- Card --}}
    <div class="auth-card">
        <p class="auth-card-title">ใช้บัญชีของคุณเพื่อเข้าสู่ระบบ</p>

        @if (session('status') || session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('status') ?? session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="auth-error">{{ $errors->first() }}</div>
        @endif


        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label class="form-label">อีเมล</label>
                <input type="email" name="email" value="{{ old('email') }}"
                    placeholder="example@email.com" required autofocus
                    class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">รหัสผ่าน</label>
                <input type="password" name="password"
                    placeholder="••••••••" required
                    class="form-input">
            </div>

            <div class="form-footer">
                <label class="form-remember">
                    <input type="checkbox" name="remember">
                    จดจำฉัน
                </label>
                <a href="{{ route('password.request') }}" class="form-forgot">ลืมรหัสผ่าน / รีเซ็ตรหัสผ่าน</a>
            </div>

            <button type="submit" class="btn-submit">เข้าสู่ระบบ</button>
        </form>

        
    </div>

</div>
@endsection
