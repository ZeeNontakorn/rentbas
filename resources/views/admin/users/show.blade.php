@extends('layouts.app')

@section('title', 'ข้อมูลผู้ใช้: ' . $user->name)

@php
    $badge = function ($s) {
        return match($s) {
            'pending'   => 'bg-yellow-100 text-yellow-700',
            'approved'  => 'bg-green-100 text-green-700',
            'rejected'  => 'bg-red-100 text-red-700',
            'cancelled' => 'bg-gray-100 text-gray-600',
            default     => 'bg-gray-100 text-gray-700',
        };
    };
    $dot = function ($s) {
        return match($s) {
            'pending'   => 'bg-yellow-500',
            'approved'  => 'bg-green-500',
            'rejected'  => 'bg-red-500',
            'cancelled' => 'bg-gray-400',
            default     => 'bg-gray-400',
        };
    };
    $label = function ($s) {
        return match($s) {
            'pending'   => 'รออนุมัติ',
            'approved'  => 'อนุมัติ',
            'rejected'  => 'ปฏิเสธ',
            'cancelled' => 'ยกเลิก',
            default     => $s,
        };
    };
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
                            สมัครเมื่อ {{ $user->created_at->format('d/m/Y') }}
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
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="flex gap-2 shrink-0">
                    <a href="#current-bookings"
                       class="flex items-center gap-1.5 bg-blue-50 text-blue-600 border border-blue-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-100 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        ปัจจุบัน ({{ $currentBookings->count() }})
                    </a>
                    <a href="#past-bookings"
                       class="flex items-center gap-1.5 bg-gray-100 text-gray-700 border border-gray-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        ประวัติ ({{ $pastBookings->count() }})
                    </a>
                </div>
            </div>
        </div>

        {{-- Current Bookings --}}
        <section id="current-bookings" class="mb-6 scroll-mt-24">
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
                            @forelse($currentBookings as $b)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 text-gray-600">{{ $b->booking_date->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4 text-gray-800">{{ substr($b->start_time, 0, 5) }} - {{ substr($b->end_time, 0, 5) }}</td>
                                    <td class="px-6 py-4 text-gray-500">{{ $b->court->name }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $badge($b->status) }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $dot($b->status) }}"></span>
                                            {{ $label($b->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-400 text-sm">ไม่พบข้อมูลคำขอจองปัจจุบัน</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- Past Bookings --}}
        <section id="past-bookings" class="scroll-mt-24">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                    <h2 class="font-medium text-gray-700 text-sm">ประวัติการปฏิเสธการจอง</h2>
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
                            @forelse($pastBookings as $b)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 text-gray-600">{{ $b->booking_date->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4 text-gray-800">{{ substr($b->start_time, 0, 5) }} - {{ substr($b->end_time, 0, 5) }}</td>
                                    <td class="px-6 py-4 text-gray-500">{{ $b->court->name }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $badge($b->status) }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $dot($b->status) }}"></span>
                                            {{ $label($b->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 text-xs">{{ $b->reject_reason ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">ไม่พบข้อมูลประวัติการจอง</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </div>
</div>
@endsection