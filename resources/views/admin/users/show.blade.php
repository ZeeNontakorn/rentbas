@extends('layouts.app')

@section('title', 'ข้อมูลผู้ใช้: ' . $user->us_name)

@php
    function getStatusDetails($status) {
        return match($status) {
            'pending'   => ['bg-yellow-100 text-yellow-700', 'bg-yellow-500', 'รออนุมัติ'],
            'approved'  => ['bg-green-100 text-green-700', 'bg-green-500', 'อนุมัติ'],
            'rejected'  => ['bg-red-100 text-red-700', 'bg-red-500', 'ปฏิเสธ'],
            'cancelled' => ['bg-gray-100 text-gray-600', 'bg-gray-400', 'ยกเลิก'],
            'expired'   => ['bg-gray-100 text-gray-600', 'bg-gray-500', 'เลยกำหนด'],
            default     => ['bg-gray-100 text-gray-700', 'bg-gray-400', $status],
        };
    }
@endphp

{{-- ─── INCLUDE SWEETALERT & STYLES ─── --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');
.pf-edit { font-family:'Sarabun','Kanit',sans-serif; }
.pf-edit h1, .pf-edit h2, .pf-edit h3, .pf-edit .font-kanit { font-family:'Kanit',sans-serif; }
.form-label { display:block; font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.06em; margin-bottom:6px; }
.form-input {
    width:100%; padding:10px 13px; font-size:14px; font-family:'Sarabun',sans-serif;
    color:#111827; background:#fafafa; border:1.5px solid #e5e7eb; border-radius:8px;
    outline:none; transition:border-color .2s, box-shadow .2s, background .2s;
}
.form-input:disabled { background: #f3f4f6; color: #9ca3af; cursor: not-allowed; }
.form-input::placeholder { color:#d1d5db; }
.form-input:focus:not(:disabled) { border-color:#e86c2a; background:#fff; box-shadow:0 0 0 3px rgba(232,108,42,.08); }
.form-group { margin-bottom:16px; }

.otp-notice {
    background:#fffbeb; border:1px solid #fde68a; border-radius:10px;
    padding:12px 14px; margin-bottom:16px; display:flex; gap:10px; align-items:flex-start;
}
.otp-notice-text { font-size:13px; color:#78350f; line-height:1.6; }
.otp-notice-text strong { display:block; font-weight:700; margin-bottom:2px; }
.otp-msg { margin-top:10px; padding:10px 14px; border-radius:8px; font-size:13px; display:none; }
.otp-msg.ok  { display:flex; align-items:center; gap:8px; background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
.otp-msg.err { display:flex; align-items:center; gap:8px; background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }

.section-divider {
    display:flex; align-items:center; gap:10px; margin:20px 0 16px; color:#d1d5db; 
    font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.08em;
}
.section-divider::before,.section-divider::after { content:''; flex:1; height:1px; background:#f1f3f5; }
</style>

@if ($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const msgs = {!! json_encode($errors->all()) !!};
        Swal.fire({ icon:'error', title:'กรุณาตรวจสอบข้อมูล', html: msgs.join('<br>'), confirmButtonText:'ตกลง', confirmButtonColor:'#e86c2a' });
        if(typeof openUserProfileModal === 'function') openUserProfileModal();
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

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-4 sm:px-6 max-w-7xl">

        {{-- Breadcrumb --}}
        <a href="{{ route('admin.users.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-orange-500 mb-6 transition font-medium group">
            <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            กลับไปหน้าจัดการผู้ใช้งาน
        </a>

        {{-- User Profile Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6 border-t-4 border-t-orange-500">
            <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                {{-- Avatar --}}
                @php
                    // เช็คว่ามีรูปใน User หรือ รูปจากโปรไฟล์พนักงาน/โค้ช หรือไม่
                    $displayAvatarUrl = null;
                    
                    // เพิ่มการเช็คว่ามีค่าใน DB และ ไฟล์ต้องมีอยู่จริงในโฟลเดอร์ storage 
                    if (!empty($user->avatar) && \Storage::disk('public')->exists($user->avatar)) {
                        $displayAvatarUrl = asset('storage/' . $user->avatar);
                    } elseif (!empty($user->staffProfile?->profile_image)) {
                        $displayAvatarUrl = $user->staffProfile->profile_image_url;
                    }
                @endphp

                <div class="w-20 h-20 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center flex-shrink-0 border-2 border-white shadow-sm overflow-hidden">
                    @if(!empty($displayAvatarUrl))
                        <img src="{{ $displayAvatarUrl }}" alt="{{ $user->name ?? $user->us_name }}" class="w-full h-full object-cover">
                    @else
                        {{-- ถ้าไม่มีรูป หรือรูปถูกลบไปแล้ว ให้แสดงตัวอักษรตัวแรก --}}
                        <span class="text-3xl font-bold">{{ mb_strtoupper(mb_substr($user->name ?? $user->us_name, 0, 1)) }}</span>
                    @endif
                </div>
                
                <div class="flex-1 w-full">
                    {{-- แถวบน: ชื่อ, สถานะ OTP และ ปุ่มจัดการ --}}
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div class="flex items-center flex-wrap gap-3">
                            <h1 class="text-xl font-semibold text-gray-800">{{ $user->name ?? $user->us_name }}</h1>
                            
                            {{-- สถานะ OTP --}}
                            @if($user->is_verified)
                                <span class="inline-flex items-center gap-1 text-sm font-medium text-green-600 bg-green-50 px-2.5 py-1 rounded-full border border-green-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                    ยืนยัน OTP แล้ว
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-sm font-medium text-red-500 bg-red-50 px-2.5 py-1 rounded-full border border-red-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    ยังไม่ยืนยัน OTP
                                </span>
                            @endif

                            {{-- บทบาท (Role) Pill --}}
                            @php
                                $roleLabel = match($user->role) {
                                    'superadmin' => 'Super Admin',
                                    'admin'      => 'ผู้ดูแลระบบ',
                                    'staff'      => 'พนักงาน',
                                    default      => 'ผู้ใช้งาน',
                                };
                                $roleColor = match($user->role) {
                                    'superadmin' => 'bg-red-100 text-red-700 border-red-200',
                                    'admin'      => 'bg-purple-100 text-purple-700 border-purple-200',
                                    'staff'      => 'bg-blue-100 text-blue-700 border-blue-200',
                                    default      => 'bg-gray-100 text-gray-700 border-gray-200',
                                };
                            @endphp
                            <span class="inline-flex items-center text-sm font-medium px-2.5 py-1 rounded-full border {{ $roleColor }}">
                                {{ $roleLabel }}
                            </span>
                        </div>

                        {{-- Quick Actions --}}
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <a href="{{ route('admin.credits.show', $user) }}" class="flex items-center gap-1.5 bg-emerald-50 text-emerald-600 border border-emerald-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-100 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                    <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                                </svg>
                                จัดการเครดิต ({{ number_format($user->credit_balance / 100, 0) }} บาท)
                            </a>
                            <button type="button" onclick="openUserProfileModal()" class="flex items-center gap-1.5 bg-gray-100 text-gray-700 border border-gray-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                แก้ไขข้อมูลส่วนตัว
                            </button>
                        </div>
                    </div>

                    {{-- รายละเอียดผู้ใช้ (Grid) --}}
                    <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-gray-600">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400">ชื่อบัญชีผู้ใช้</div>
                            <div class="mt-1 flex items-center gap-2 font-medium text-gray-700">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-orange-100 text-[10px] font-bold text-orange-600">@</span>
                                {{ $user->us_name }}
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400">ประเภทลูกค้า</div>
                            <div class="mt-1 font-medium text-gray-700">
                                {{ $user->membershipTypeLabel() }}
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400">อีเมล</div>
                            <div class="mt-1 flex items-center gap-2 font-medium text-gray-700 break-all">
                                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                {{ $user->email }}
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400">สมัครเมื่อ</div>
                            <div class="mt-1 flex items-center gap-2 font-medium text-gray-700">
                                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                {{ \Carbon\Carbon::parse($user->created_at)->format('d/m/Y') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── CURRENT BOOKINGS ─── --}}
        <section id="current-bookings" class="mb-6 scroll-mt-24 transition-opacity duration-300">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        <h2 class="font-medium text-gray-700 text-sm">คำขอจองปัจจุบัน</h2>
                    </div>

                    <div class="ml-auto flex items-center gap-2">
                        <button type="button" data-booking-tab="current" class="booking-tab flex items-center gap-1.5 bg-blue-50 text-blue-600 border border-blue-200 px-3 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                            </svg>
                            ปัจจุบัน ({{ $currentBookings->count() }})
                        </button>
                        <button type="button" data-booking-tab="past" class="booking-tab flex items-center gap-1.5 bg-gray-100 text-gray-700 border border-gray-200 px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-gray-200 transition cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                <path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/>
                            </svg>
                            ประวัติ ({{ $pastBookings->count() }})
                        </button>
                        <span class="text-xs bg-green-50 text-green-600 border border-green-200 px-2.5 py-0.5 rounded-full font-medium">
                            {{ $currentBookings->count() }} รายการ
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-gray-400 text-xs uppercase tracking-wide border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 font-medium">วันที่</th>
                                <th class="px-6 py-3 font-medium">เวลา</th>
                                <th class="px-6 py-3 font-medium">สนาม</th>
                                <th class="px-6 py-3 font-medium">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @if($currentBookings->isEmpty())
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-400 text-sm">ไม่พบข้อมูลคำขอจองปัจจุบัน</td>
                                </tr>
                            @else
                                @foreach($currentBookings as $b)
                                    @php
                                        $bookingEndTime = \Carbon\Carbon::parse($b->booking_date)->setTimeFromTimeString($b->end_time);
                                        $currentStatus = ($b->status === 'pending' && $bookingEndTime->isPast()) ? 'expired' : $b->status;
                                        [$badgeClass, $dotClass, $statusLabel] = getStatusDetails($currentStatus);
                                    @endphp
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="px-6 py-4 text-gray-600">{{ \Carbon\Carbon::parse($b->booking_date)->format('d/m/Y') }}</td>
                                        <td class="px-6 py-4 text-gray-800">{{ substr($b->start_time, 0, 5) }} - {{ substr($b->end_time, 0, 5) }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $b->court->name }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $badgeClass }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $dotClass }}"></span>
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- ─── PAST BOOKINGS ─── --}}
        <section id="past-bookings" class="scroll-mt-24 hidden transition-opacity duration-300">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                        <h2 class="font-medium text-gray-700 text-sm">ประวัติการปฏิเสธ / ยกเลิก / ที่ผ่านมา</h2>
                    </div>

                    <div class="ml-auto flex items-center gap-2">
                        <button type="button" data-booking-tab="current" class="booking-tab flex items-center gap-1.5 bg-gray-100 text-gray-700 border border-gray-200 px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-gray-200 transition cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                            </svg>
                            ปัจจุบัน ({{ $currentBookings->count() }})
                        </button>
                        <button type="button" data-booking-tab="past" class="booking-tab flex items-center gap-1.5 bg-blue-50 text-blue-600 border border-blue-200 px-3 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                <path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/>
                            </svg>
                            ประวัติ ({{ $pastBookings->count() }})
                        </button>
                        <span class="text-xs bg-gray-50 text-gray-600 border border-gray-200 px-2.5 py-0.5 rounded-full font-medium">
                            {{ $pastBookings->count() }} รายการ
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-gray-400 text-xs uppercase tracking-wide border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 font-medium">วันที่</th>
                                <th class="px-6 py-3 font-medium">เวลา</th>
                                <th class="px-6 py-3 font-medium">สนาม</th>
                                <th class="px-6 py-3 font-medium">สถานะ</th>
                                <th class="px-6 py-3 font-medium">เหตุผล</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @if($pastBookings->isEmpty())
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">ไม่พบข้อมูลประวัติการจอง</td>
                                </tr>
                            @else
                                @foreach($pastBookings as $b)
                                    @php
                                        $bookingEndTime = \Carbon\Carbon::parse($b->booking_date)->setTimeFromTimeString($b->end_time);
                                        $currentStatus = ($b->status === 'pending' && $bookingEndTime->isPast()) ? 'expired' : $b->status;
                                        [$badgeClass, $dotClass, $statusLabel] = getStatusDetails($currentStatus);
                                    @endphp
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="px-6 py-4 text-gray-600">{{ \Carbon\Carbon::parse($b->booking_date)->format('d/m/Y') }}</td>
                                        <td class="px-6 py-4 text-gray-800">{{ substr($b->start_time, 0, 5) }} - {{ substr($b->end_time, 0, 5) }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $b->court->name }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $badgeClass }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $dotClass }}"></span>
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $b->reject_reason ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </div>
</div>

<div id="userProfileModal" class="fixed inset-0 z-[60] hidden bg-gray-900/60 backdrop-blur-sm items-center justify-center p-4 transition-all">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden border border-gray-100">
        <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-6 py-4">
            <h3 class="text-lg font-bold text-gray-800">แก้ไขข้อมูลส่วนตัว</h3>
        </div>

        <form action="{{ route('admin.users.profile.update', $user) }}" method="POST" enctype="multipart/form-data" class="space-y-4 bg-white p-6">
            @csrf
            @method('PUT')

            {{-- ช่องอัปโหลดรูปโปรไฟล์ --}}
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-xs font-medium text-gray-600">รูปโปรไฟล์</label>
                <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-xl border border-gray-200">
                    <div class="w-16 h-16 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center shrink-0 border border-gray-200 overflow-hidden relative">
                        <img id="avatarPreview" src="{{ $displayAvatarUrl ?? '' }}" class="{{ $displayAvatarUrl ? '' : 'hidden' }} w-full h-full object-cover">
                        <span id="avatarFallback" class="{{ $displayAvatarUrl ? 'hidden' : '' }} text-2xl font-bold">{{ mb_strtoupper(mb_substr($user->us_name, 0, 1)) }}</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <input type="file" name="avatar" id="avatarInput" accept="image/png, image/jpeg, image/jpg, image/webp" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100 cursor-pointer">
                            
                            {{-- ปุ่มลบภาพ --}}
                            <button type="button" id="removeAvatarBtn" class="{{ $user->avatar ? '' : 'hidden' }} shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                ลบรูป
                            </button>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1.5">รองรับ JPG, PNG, WEBP ขนาดไม่เกิน 2MB</p>
                        
                        {{-- Input สำหรับส่งค่าไปบอก Backend ว่าให้ลบรูป --}}
                        <input type="hidden" name="remove_avatar" id="removeAvatarInput" value="0">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 outline-none transition focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600">ชื่อบัญชีผู้ใช้ <span class="text-red-500">*</span></label>
                    <input type="text" name="us_name" value="{{ old('us_name', $user->us_name) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 outline-none transition focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600">บทบาท (Role) <span class="text-red-500">*</span></label>
                    
                    @if(auth()->id() === $user->id)
                        <input type="hidden" name="role" value="{{ $user->role }}">
                        <select id="roleSelect" disabled class="w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-500 outline-none">
                            <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>ผู้ใช้งาน (User)</option>
                            <option value="staff" {{ $user->role === 'staff' ? 'selected' : '' }}>พนักงาน (Staff)</option>
                            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>ผู้ดูแลระบบ (Admin)</option>
                            <option value="superadmin" {{ $user->role === 'superadmin' ? 'selected' : '' }}>ผู้ดูแลระบบสูงสุด (Super Admin)</option>
                        </select>
                    @else
                        <select name="role" id="roleSelect" required class="w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 outline-none transition focus:ring-2 focus:ring-orange-500">
                            <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>ผู้ใช้งาน (User)</option>
                            <option value="staff" {{ old('role', $user->role) === 'staff' ? 'selected' : '' }}>พนักงาน (Staff)</option>
                            <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>ผู้ดูแลระบบ (Admin)</option>
                            
                            @if(auth()->user()->role === 'superadmin')
                                <option value="superadmin" {{ old('role', $user->role) === 'superadmin' ? 'selected' : '' }}>ผู้ดูแลระบบสูงสุด (Super Admin)</option>
                            @endif
                        </select>
                    @endif
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600">ประเภทสมาชิก <span class="text-red-500">*</span></label>
                    <select name="membership_type" id="membershipSelect" required class="w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 outline-none transition focus:ring-2 focus:ring-orange-500 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                        
                    </select>
                </div>

                <div class="md:col-span-2 mt-2">
                    <div class="flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400 after:h-px after:flex-1 after:bg-gray-100">
                        ข้อมูลติดต่อ
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600">อีเมล <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="emailInput" data-original-email="{{ $user->email }}" value="{{ old('email', $user->email) }}" required placeholder="example@email.com" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 outline-none transition focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600">เบอร์โทรศัพท์ <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}" maxlength="10" pattern="[0-9]{10}" oninput="this.value = this.value.replace(/[^0-9]/g, '');" required placeholder="0812345678" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 outline-none transition focus:ring-2 focus:ring-orange-500">
                </div>

                {{-- OTP Section --}}
                <div id="otpSection" class="md:col-span-2" style="display:none;">
                    <div class="otp-notice mb-3">
                        <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <div class="otp-notice-text">
                            <strong>ต้องยืนยัน OTP เพื่อเปลี่ยนอีเมล</strong>
                            กดปุ่ม "ส่ง OTP" แล้วนำรหัสมาตรวจสอบก่อนบันทึก
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-600">รหัส OTP (6 หลัก)</label>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <input type="text" name="otp" id="otpInput" maxlength="6" pattern="[0-9]{6}" placeholder="000000" class="w-full sm:w-1/2 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 outline-none transition focus:ring-2 focus:ring-orange-500">
                            <button type="button" onclick="requestOtp()" class="w-full sm:w-1/2 py-2 text-[13px] font-semibold border border-green-200 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 cursor-pointer transition font-kanit">
                                ส่ง OTP ไปยังอีเมลใหม่
                            </button>
                        </div>
                        <div id="otpMessage" class="otp-msg"></div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                <button type="button" onclick="closeUserProfileModal()" class="cursor-pointer rounded-lg border border-gray-200 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-200">ยกเลิก</button>
                <button type="submit" class="cursor-pointer rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-orange-700">บันทึกข้อมูล</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ─── ระบบ Tab สลับการแสดงผล Bookings ───
    const secCurrent = document.getElementById('current-bookings');
    const secPast = document.getElementById('past-bookings');
    const tabs = document.querySelectorAll('.booking-tab');

    function setBookingTab(tabName) {
        if (!secCurrent || !secPast) return;

        const isCurrent = tabName === 'current';
        secCurrent.classList.toggle('hidden', !isCurrent);
        secPast.classList.toggle('hidden', isCurrent);

        tabs.forEach(function (tab) {
            const active = tab.dataset.bookingTab === tabName;
            tab.className = active
                ? 'booking-tab flex items-center gap-1.5 bg-blue-50 text-blue-600 border border-blue-200 px-3 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer'
                : 'booking-tab flex items-center gap-1.5 bg-gray-100 text-gray-700 border border-gray-200 px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-gray-200 transition cursor-pointer';
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            setBookingTab(tab.dataset.bookingTab);
        });
    });

    setBookingTab('current');

    // ─── ระบบจัดการ Role & Membership Type (Dynamic Options) ───
    const roleSelect = document.getElementById('roleSelect');
    const membershipSelect = document.getElementById('membershipSelect');

    // ดึง Constants จาก PHP Model มาทำเป็น JS Objects
    const userTypes = @json(\App\Models\User::MEMBERSHIP_TYPES);
    const staffTypes = @json(\App\Models\User::STAFF_TYPES);
    const adminTypes = { 'admin': 'ผู้ดูแลระบบ' };

    // ค่าเริ่มต้นของผู้ใช้ที่ดึงจาก Database
    let initialValue = @json(old('membership_type', $user->membership_type));

    function updateMembershipOptions(isRoleChangedByUser = false) {
        if (!roleSelect || !membershipSelect) return;

        const selectedRole = roleSelect.value;
        let optionsMap = {};

        // 1. เลือกชุดตัวเลือกให้ตรงกับ Role
        if (selectedRole === 'admin' || selectedRole === 'superadmin') {
            optionsMap = adminTypes;
            membershipSelect.disabled = true;
        } else if (selectedRole === 'staff') {
            optionsMap = staffTypes;
            membershipSelect.disabled = false;
        } else {
            // role === 'user'
            optionsMap = userTypes;
            membershipSelect.disabled = false;
        }

        // 2. เคลียร์ตัวเลือกเดิมใน Select ทั้งหมด
        membershipSelect.innerHTML = '';

        // 3. สร้าง <option> ขึ้นมาใหม่ตาม Role
        Object.entries(optionsMap).forEach(([value, label]) => {
            const opt = document.createElement('option');
            opt.value = value;
            opt.textContent = label;

            // หากเป็นค่าเดิมของผู้ใช้ ให้เลือกตัวเลือกนี้
            if (!isRoleChangedByUser && value === initialValue) {
                opt.selected = true;
            }

            membershipSelect.appendChild(opt);
        });

        // หากผู้ใช้คลิกเปลี่ยน Role เอง ให้เลือก Option แรกของ Role นั้นเป็นค่าเริ่มต้น
        if (isRoleChangedByUser && membershipSelect.options.length > 0) {
            membershipSelect.value = membershipSelect.options[0].value;
        }
    }

    if (roleSelect && membershipSelect) {
        roleSelect.addEventListener('change', function () {
            updateMembershipOptions(true); // แจ้งว่าเกิดจากการคลิกเปลี่ยน Role
        });

        // ทำงานทันทีตอนโหลดหน้า เพื่อสร้าง Option ชุดแรก
        updateMembershipOptions(false);
    }

    // ─── ระบบตรวจสอบการเปลี่ยนอีเมล (OTP) ───
    const emailInput = document.getElementById('emailInput');
    const otpSection = document.getElementById('otpSection');
    const otpInput = document.getElementById('otpInput');
    const originalEmail = emailInput ? emailInput.getAttribute('data-original-email') : '';

    if (emailInput && otpSection && otpInput) {
        emailInput.addEventListener('input', function() {
            const currentEmail = this.value.trim();
            if (currentEmail !== '' && currentEmail !== originalEmail) {
                otpSection.style.display = 'block';
                otpInput.required = true;
            } else {
                otpSection.style.display = 'none';
                otpInput.required = false;
                otpInput.value = '';
            }
        });
    }
});

// ─── ฟังก์ชันจัดการ Modal ───
function openUserProfileModal() {
    const modal = document.getElementById('userProfileModal');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeUserProfileModal() {
    const modal = document.getElementById('userProfileModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

const userProfileModal = document.getElementById('userProfileModal');
if (userProfileModal) {
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !userProfileModal.classList.contains('hidden')) {
            closeUserProfileModal();
        }
    });
}

// ─── ฟังก์ชันส่งขอรหัส OTP ───
function requestOtp() {
    const emailInput = document.getElementById('emailInput');
    const email = emailInput.value.trim();
    const originalEmail = emailInput.getAttribute('data-original-email');
    const box = document.getElementById('otpMessage');
    
    if (email === originalEmail) {
        box.textContent = 'อีเมลไม่มีการเปลี่ยนแปลง';
        box.className = 'otp-msg err'; 
        return;
    }
    
    const fd = new FormData();
    fd.append('email', email);
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if(csrfToken) fd.append('_token', csrfToken.getAttribute('content'));
    
    box.style.display = 'flex';
    box.innerHTML = 'กำลังส่ง OTP...';
    box.className = 'otp-msg ok';
    
    fetch('{{ route('profile.request-otp-email') }}', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                box.innerHTML = `ส่ง OTP ไปยัง <strong>${email}</strong> แล้ว`;
                box.className = 'otp-msg ok';
            } else {
                box.textContent = d.message || 'ไม่สามารถส่ง OTP ได้';
                box.className = 'otp-msg err';
            }
        })
        .catch(() => { 
            box.textContent = 'เกิดข้อผิดพลาดในการเชื่อมต่อ'; 
            box.className = 'otp-msg err'; 
        });
}

// พรีวิวและลบรูปภาพก่อนบันทึก
const avatarInput = document.getElementById('avatarInput');
const avatarPreview = document.getElementById('avatarPreview');
const avatarFallback = document.getElementById('avatarFallback');
const removeAvatarBtn = document.getElementById('removeAvatarBtn');
const removeAvatarInput = document.getElementById('removeAvatarInput');

if (avatarInput) {
    avatarInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // เช็คขนาดไฟล์ไม่เกิน 2MB
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({
                    icon: 'error',
                    title: 'ไฟล์ใหญ่เกินไป',
                    text: 'กรุณาอัปโหลดรูปภาพขนาดไม่เกิน 2MB'
                });
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                avatarPreview.src = e.target.result;
                avatarPreview.classList.remove('hidden');
                if (avatarFallback) avatarFallback.classList.add('hidden');
                
                if (removeAvatarBtn) removeAvatarBtn.classList.remove('hidden');
                if (removeAvatarInput) removeAvatarInput.value = '0';
            }
            reader.readAsDataURL(file);
        }
    });
}

if (removeAvatarBtn) {
    removeAvatarBtn.addEventListener('click', function() {
        avatarPreview.classList.add('hidden');
        avatarPreview.src = '';
        if (avatarFallback) avatarFallback.classList.remove('hidden');
        
        if (avatarInput) avatarInput.value = '';
        this.classList.add('hidden');
        
        // ส่งค่าไปบอกระบบหลังบ้านให้ลบไฟล์เดิม
        if (removeAvatarInput) removeAvatarInput.value = '1';
    });
}
</script>
@endsection