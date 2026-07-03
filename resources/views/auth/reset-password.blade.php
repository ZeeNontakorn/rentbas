@extends('layouts.app')
@section('title', 'ตั้งรหัสผ่านใหม่')

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
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1 class="text-white text-xl font-semibold">ตั้งรหัสผ่านใหม่</h1>
                <p class="text-orange-100 text-sm mt-1">กรอกรหัสผ่านใหม่สำหรับบัญชีของคุณ</p>
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

                <form method="POST" action="{{ route('password.reset') }}" class="space-y-5" id="resetForm">
                    @csrf

                    {{-- Hidden email --}}
                    <input type="hidden" name="email" value="{{ $email }}">

                    {{-- Email display (read-only) --}}
                    <div class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 flex items-center gap-3">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                        </svg>
                        <span class="text-sm text-gray-600">{{ $email }}</span>
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">รหัสผ่านใหม่</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input id="password" type="password" name="password"
                                required minlength="6"
                                placeholder="อย่างน้อย 6 ตัวอักษร"
                                class="block w-full pl-10 pr-10 py-2.5 bg-white text-gray-900 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition">
                            <button type="button" onclick="togglePwd('password','eye1')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600">
                                <svg id="eye1" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Confirm Password --}}
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">ยืนยันรหัสผ่านใหม่</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <input id="password_confirmation" type="password" name="password_confirmation"
                                required
                                placeholder="กรอกรหัสผ่านอีกครั้ง"
                                class="block w-full pl-10 pr-10 py-2.5 bg-white text-gray-900 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition">
                            <button type="button" onclick="togglePwd('password_confirmation','eye2')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600">
                                <svg id="eye2" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        <p id="match-warning" class="text-xs text-red-500 mt-1 hidden">รหัสผ่านไม่ตรงกัน</p>
                    </div>

                    {{-- Strength indicator --}}
                    <div>
                        <div class="flex gap-1 h-1.5">
                            <div class="flex-1 rounded-full bg-gray-200" id="s1"></div>
                            <div class="flex-1 rounded-full bg-gray-200" id="s2"></div>
                            <div class="flex-1 rounded-full bg-gray-200" id="s3"></div>
                            <div class="flex-1 rounded-full bg-gray-200" id="s4"></div>
                        </div>
                        <p id="strength-label" class="text-xs text-gray-400 mt-1"></p>
                    </div>

                    <button type="submit" id="submitBtn"
                        class="w-full py-3 px-4 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-xl text-sm transition shadow-md flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        บันทึกรหัสผ่านใหม่
                    </button>
                </form>

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
                <div class="w-7 h-7 rounded-full bg-green-500 text-white text-xs flex items-center justify-center font-semibold">✓</div>
                <span class="text-xs text-green-600">ยืนยัน OTP</span>
            </div>
            <div class="w-8 h-px bg-gray-300"></div>
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-orange-500 text-white text-xs flex items-center justify-center font-semibold">3</div>
                <span class="text-xs text-orange-600 font-medium">ตั้งรหัสใหม่</span>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
function togglePwd(id, eyeId) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

// Password strength
document.getElementById('password').addEventListener('input', function () {
    const val = this.value;
    const bars = ['s1','s2','s3','s4'];
    const colors = ['bg-red-400','bg-orange-400','bg-yellow-400','bg-green-500'];
    const labels = ['','อ่อน','พอใช้','ดี','แข็งแกร่ง'];

    let score = 0;
    if (val.length >= 6) score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val) || /[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    bars.forEach((id, i) => {
        const el = document.getElementById(id);
        el.className = 'flex-1 rounded-full ' + (i < score ? colors[score - 1] : 'bg-gray-200');
    });
    document.getElementById('strength-label').textContent = labels[score] ? `ความปลอดภัย: ${labels[score]}` : '';
});

// Match check
const pwd = document.getElementById('password');
const conf = document.getElementById('password_confirmation');
const warn = document.getElementById('match-warning');

conf.addEventListener('input', () => {
    warn.classList.toggle('hidden', pwd.value === conf.value || conf.value === '');
});

// Prevent submit if mismatch
document.getElementById('resetForm').addEventListener('submit', (e) => {
    if (pwd.value !== conf.value) {
        e.preventDefault();
        warn.classList.remove('hidden');
        conf.focus();
    }
});
</script>
@endpush
@endsection
