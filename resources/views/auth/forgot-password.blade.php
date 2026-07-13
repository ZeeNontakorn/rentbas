@extends('layouts.app')
@section('title', 'ลืมรหัสผ่าน')

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
                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <h1 class="text-white text-xl font-semibold">ลืมรหัสผ่าน</h1>
                <p class="text-orange-100 text-sm mt-1">กรอกอีเมลเพื่อรับรหัส OTP รีเซ็ตรหัสผ่าน</p>
            </div>

            {{-- BODY --}}
            <div class="px-8 py-8">

                {{-- Status message --}}
                @if (session('status'))
                    <div class="flex items-start gap-3 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-6 text-sm">
                        <svg class="w-5 h-5 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        {{ session('status') }}
                    </div>
                @endif

                {{-- Error --}}
                @if ($errors->any())
                    <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-6 text-sm">
                        <svg class="w-5 h-5 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div>@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">อีเมลของคุณ</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                </svg>
                            </div>
                            <input id="email" type="email" name="email" value="{{ old('email') }}"
                                required autofocus autocomplete="email"
                                placeholder="your@email.com"
                                class="block w-full pl-10 pr-4 py-2.5 bg-white text-gray-900 border @error('email') border-red-400 bg-red-50 @else border-gray-300 @enderror rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition">
                        </div>
                    </div>

                    <button type="submit" id="submitBtn"
                        class="w-full py-3 px-4 bg-orange-500 hover:bg-orange-600 active:bg-orange-700 text-white font-medium rounded-xl text-sm transition-all duration-200 shadow-md hover:shadow-orange-200 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                        <svg id="submitIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <svg id="submitSpinner" class="w-4 h-4 animate-spin hidden" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span id="submitText">ส่งรหัส OTP ไปยังอีเมล</span>
                    </button>
                </form>

                <p class="text-center text-sm text-gray-500 mt-6">
                    จำรหัสผ่านได้แล้ว?
                    <a href="{{ route('login') }}" class="text-orange-500 font-medium hover:text-orange-600 hover:underline">เข้าสู่ระบบ</a>
                </p>

            </div>
        </div>

        {{-- STEPS --}}
        <div class="flex items-center justify-center gap-3 mt-6">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-orange-500 text-white text-xs flex items-center justify-center font-semibold">1</div>
                <span class="text-xs text-orange-600 font-medium">กรอกอีเมล</span>
            </div>
            <div class="w-8 h-px bg-gray-300"></div>
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-gray-200 text-gray-500 text-xs flex items-center justify-center font-semibold">2</div>
                <span class="text-xs text-gray-500">ยืนยัน OTP</span>
            </div>
            <div class="w-8 h-px bg-gray-300"></div>
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-gray-200 text-gray-500 text-xs flex items-center justify-center font-semibold">3</div>
                <span class="text-xs text-gray-500">ตั้งรหัสใหม่</span>
            </div>
        </div>

    </div>
</div>

<script>
    document.querySelector('form[action="{{ route('password.email') }}"]').addEventListener('submit', function (e) {
        const emailInput = document.getElementById('email');
        const btn = document.getElementById('submitBtn');

        // ถ้าช่องอีเมลว่างเปล่า ปล่อยให้ browser validate ตามปกติ (required) ไม่ต้อง disable ปุ่ม
        if (!emailInput.value.trim()) {
            return;
        }

        // กันกด submit ซ้ำ (spam) ระหว่างรอโหลด
        btn.disabled = true;
        document.getElementById('submitIcon').classList.add('hidden');
        document.getElementById('submitSpinner').classList.remove('hidden');
        document.getElementById('submitText').innerText = 'กำลังส่งรหัส OTP...';
    });

    // เผื่อ user กด back มาที่หน้านี้ (bfcache) ปุ่มจะได้ไม่ค้าง disabled จากรอบก่อน
    window.addEventListener('pageshow', function (event) {
        const btn = document.getElementById('submitBtn');
        if (!btn) return;
        btn.disabled = false;
        document.getElementById('submitIcon').classList.remove('hidden');
        document.getElementById('submitSpinner').classList.add('hidden');
        document.getElementById('submitText').innerText = 'ส่งรหัส OTP ไปยังอีเมล';
    });
</script>
@endsection
