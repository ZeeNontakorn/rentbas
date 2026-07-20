@extends('layouts.app')

@section('title', 'ข้อมูลผู้ใช้: ' . $user->name)

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

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-6 max-w-5xl">

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
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                {{-- Avatar --}}
                <div class="w-14 h-14 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
                    <span class="text-orange-600 text-xl font-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                </div>
                <div class="flex-1">
                    <h1 class="text-xl font-semibold text-gray-800">{{ $user->name }}</h1>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1 text-sm text-gray-500">
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            {{ $user->email }}
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            สมัครเมื่อ {{ \Carbon\Carbon::parse($user->created_at)->format('d/m/Y') }}
                        </span>
                        @if($user->is_verified)
                            <span class="inline-flex items-center gap-1 text-green-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                ยืนยัน OTP แล้ว
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-red-500">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                ยังไม่ยืนยัน OTP
                            </span>
                        @endif

                        @if(in_array($user->role, ['admin', 'superadmin'], true))
                            <span class="inline-flex items-center gap-1 text-gray-500 font-semibold">
                                {{ $user->role === 'superadmin' ? 'แอดมิน' : 'แอดมิน' }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-orange-600 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M5.121 17.804A12.055 12.055 0 0112 15c2.21 0 4.21.635 5.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ $user->membershipTypeLabel() }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Quick Actions (Tabs) --}}
                <div class="flex gap-2 shrink-0">
                    <button id="tab-current" class="flex items-center gap-1.5 bg-blue-50 text-blue-600 border border-blue-200 px-4 py-2 rounded-lg text-sm font-medium transition cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                            <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                        </svg>
                        ปัจจุบัน ({{ $currentBookings->count() }})
                    </button>
                    <button id="tab-past" class="flex items-center gap-1.5 bg-gray-100 text-gray-700 border border-gray-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                            <path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/>
                        </svg>
                        ประวัติ ({{ $pastBookings->count() }})
                    </button>
                </div>
            </div>
        </div>

        {{-- ─── CURRENT BOOKINGS ─── --}}
        <section id="current-bookings" class="mb-6 scroll-mt-24 transition-opacity duration-300">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    <h2 class="font-medium text-gray-700 text-sm">คำขอจองปัจจุบัน</h2>
                    <span class="ml-auto text-xs bg-green-50 text-green-600 border border-green-200 px-2.5 py-0.5 rounded-full font-medium">
                        {{ $currentBookings->count() }} รายการ
                    </span>
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
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                    <h2 class="font-medium text-gray-700 text-sm">ประวัติการปฏิเสธ / ยกเลิก / ที่ผ่านมา</h2>
                    <span class="ml-auto text-xs bg-gray-50 text-gray-600 border border-gray-200 px-2.5 py-0.5 rounded-full font-medium">
                        {{ $pastBookings->count() }} รายการ
                    </span>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabCurrent = document.getElementById('tab-current');
    const tabPast = document.getElementById('tab-past');
    const secCurrent = document.getElementById('current-bookings');
    const secPast = document.getElementById('past-bookings');

    tabCurrent.addEventListener('click', function() {
        secCurrent.classList.remove('hidden');
        secPast.classList.add('hidden');

        tabCurrent.className = "flex items-center gap-1.5 bg-blue-50 text-blue-600 border border-blue-200 px-4 py-2 rounded-lg text-sm font-medium transition cursor-pointer";
        tabPast.className = "flex items-center gap-1.5 bg-gray-100 text-gray-700 border border-gray-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition cursor-pointer";
    });

    tabPast.addEventListener('click', function() {
        secCurrent.classList.add('hidden');
        secPast.classList.remove('hidden');

        tabPast.className = "flex items-center gap-1.5 bg-blue-50 text-blue-600 border border-blue-200 px-4 py-2 rounded-lg text-sm font-medium transition cursor-pointer";
        tabCurrent.className = "flex items-center gap-1.5 bg-gray-100 text-gray-700 border border-gray-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition cursor-pointer";
    });
});
</script>
@endsection
