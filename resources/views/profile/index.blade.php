@extends('layouts.app')

@section('title', 'บัญชีของฉัน · BCBS')

@section('content')
<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');
.pf-main { font-family:'Sarabun','Kanit',sans-serif; }
.pf-main h1,.pf-main h2,.pf-main h3 { font-family:'Kanit',sans-serif; }
.info-row { display:flex; align-items:center; gap:14px; padding:14px 0; border-bottom:1px solid #f3f4f6; }
.info-row:last-child { border-bottom:none; }
.info-icon { width:36px; height:36px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.info-label { font-size:11px; color:#9ca3af; font-weight:600; letter-spacing:.05em; text-transform:uppercase; margin-bottom:2px; }
.info-value { font-size:14px; color:#111827; font-weight:500; }
</style>

<div class="pf-main bg-white min-h-screen text-[#111827]">
<div class="max-w-[860px] mx-auto px-4 py-8">
    @php
        $canManageProfileImage = in_array($user->membership_type, ['coach', 'court_assistant'], true);
        $coachProfileImage = $user->staffProfile?->profile_image;
    @endphp

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-baseline gap-4">
        <div>
            <h1 class="text-[26px] font-semibold text-gray-800 tracking-tight">บัญชีของฉัน</h1>
            <p class="text-gray-500 text-[14px] mt-0.5">ข้อมูลสมาชิก Thata Homecourt</p>
        </div>
        <a href="{{ route('profile.edit') }}"
           class="inline-flex items-center gap-2 bg-[#e86c2a] hover:bg-[#d05a1a] text-white font-medium px-4 py-2 rounded-lg transition text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            แก้ไขข้อมูล
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
            <h2 class="text-[22px] font-bold tracking-wide">{{ $user->name }}</h2>
            <p class="text-gray-300 text-[13px] mt-1">{{ $user->email }}</p>
        </div>
    </div>

    {{-- INFO CARD --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm mb-6 flex">
        <div class="flex-1 min-w-0">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-[#e86c2a]/10 flex items-center justify-center border border-[#e86c2a]/20">
                    <svg class="w-4 h-4 text-[#e86c2a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[14px] font-semibold text-gray-800" style="font-family:'Kanit',sans-serif;">ข้อมูลบัญชี</div>
                    <div class="text-[12px] text-gray-400">ข้อมูลที่บันทึกในระบบ</div>
                </div>
            </div>

            <div class="px-5">

                <div class="info-row">
                    <div class="info-icon">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="info-label">ชื่อผู้ใช้</div>
                        <div class="info-value">{{ $user->name }}</div>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-icon">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="info-label">อีเมล</div>
                        <div class="info-value">{{ $user->email }}</div>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-icon">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="info-label">เบอร์โทรศัพท์</div>
                        <div class="info-value">{{ $user->phone ?: '—' }}</div>
                    </div>
                </div>

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

        @if ($canManageProfileImage && $coachProfileImage)
            <div class="flex-shrink-0 w-82 p-5">
                <img src="{{ route('storage.local', ['path' => $coachProfileImage]) }}"
                    alt="Staff profile"
                     class="w-full h-full object-cover">
            </div>
        @endif
    </div>


</div>
</div>
@endsection
