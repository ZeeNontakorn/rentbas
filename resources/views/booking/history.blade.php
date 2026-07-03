@extends('layouts.app')

@section('title', 'ประวัติการจอง - Booking History')

@section('content')
<div class="bg-white min-h-screen text-[#111827]">

<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');

.hist-main { font-family: 'Sarabun', 'Kanit', sans-serif; }
.hist-main h1, .hist-main h2, .hist-main h3 { font-family: 'Kanit', sans-serif; }

/* ─── CARD STYLES ─── */
.b-card {
    background: #fff;
    border-radius: 8px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    border: 1px solid #e5e7eb;
    transition: border-color 0.2s;
}
@media(min-width: 768px) {
    .b-card {
        flex-direction: row;
        align-items: center;
        padding: 16px 20px;
    }
}
.b-card:hover {
    border-color: #d1d5db;
}

.badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 6px;
    font-size: 11px; font-weight: 600; font-family: 'Kanit', sans-serif;
}
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-approved { background: #ecfdf5; color: #047857; }
.badge-rejected { background: #fef2f2; color: #b91c1c; }
.badge-cancelled { background: #f3f4f6; color: #4b5563; }

/* ─── CANCEL BUTTON ─── */
.btn-cancel {
    background: #fff;
    color: #ef4444;
    border: 1px solid #fecaca;
    font-size: 12px; font-weight: 600;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
}
.btn-cancel:hover { background: #fef2f2; }

/* ─── TABS ─── */
.tab-btn {
    padding: 8px 16px;
    font-size: 15px; font-weight: 500;
    color: #64748b;
    border-bottom: 2px solid transparent;
    cursor: pointer;
}
.tab-btn.active {
    color: #111827;
    border-bottom-color: #87D068;
}
.tab-btn:hover:not(.active) { color: #334155; }
</style>

@php
    $thMonths = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];

    function getStatusDetails($status) {
        return match($status) {
            'pending' => ['st-pending', 'badge-pending', 'รอการอนุมัติ', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />'],
            'approved' => ['st-approved', 'badge-approved', 'อนุมัติแล้ว', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />'],
            'rejected' => ['st-rejected', 'badge-rejected', 'ถูกปฏิเสธ', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />'],
            'cancelled' => ['st-cancelled', 'badge-cancelled', 'ยกเลิก', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />'],
            default => ['st-cancelled', 'badge-cancelled', $status, ''],
        };
    }
@endphp

<div class="hist-main max-w-[1200px] mx-auto px-4 py-8" x-data="{ tab: 'current' }" data-aos="fade-up">

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col md:flex-row justify-between items-baseline gap-4" data-aos="fade-right">
        <div class="flex items-baseline gap-4">
            <h1 class="text-[28px] font-semibold text-gray-800 tracking-tight">My Bookings</h1>
            <span class="text-gray-600 text-[15px] hidden sm:inline">ประวัติการจองสนามบาสเกตบอล</span>
        </div>
        <a href="{{ route('booking.index') }}" class="bg-[#87D068] hover:bg-[#76bc5a] text-white font-medium px-4 py-2 rounded-lg transition text-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            จองสนามเพิ่ม
        </a>
    </div>

    {{-- Banner Image --}}
    <div class="w-full h-[220px] rounded-[16px] overflow-hidden mb-8 shadow-sm relative group" data-aos="zoom-in" data-aos-delay="100">
        <img src="https://images.unsplash.com/photo-1546519638-68e109498ffc?q=80&w=1400&auto=format&fit=crop" 
             style="filter: grayscale(80%) sepia(10%);" 
             alt="History Banner" class="w-full h-full object-cover transition duration-500 group-hover:scale-105 group-hover:grayscale-0">
        
        <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/40 to-transparent"></div>
        <div class="absolute inset-y-0 left-0 flex flex-col justify-center px-10 text-white">
            <svg class="w-10 h-10 mb-3 text-[#87D068]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <h2 class="text-2xl font-bold tracking-wide">HISTORY RECORD</h2>
            <p class="text-gray-300 text-sm mt-1 max-w-md">ตรวจสอบรายการจองปัจจุบันและประวัติการเล่นที่ผ่านมาของคุณได้เลย</p>
        </div>
    </div>

    {{-- TABS --}}
    <div class="flex border-b border-gray-200 mb-6 overflow-x-auto hide-scrollbar">
        <button @click="tab = 'current'" :class="tab === 'current' ? 'active' : ''" class="tab-btn whitespace-nowrap">
            รายการปัจจุบัน <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-[10px]">{{ $current->count() }}</span>
        </button>
        <button @click="tab = 'past'" :class="tab === 'past' ? 'active' : ''" class="tab-btn whitespace-nowrap">
            ประวัติ <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-[10px]">{{ $past->count() }}</span>
        </button>
    </div>

    {{-- WRAPPER --}}
    <div class="relative min-h-[400px]">

        {{-- ─── CURRENT BOOKINGS ─── --}}
        <div x-show="tab === 'current'" x-transition.opacity.duration.400ms>
            @if($current->isEmpty())
                <div class="border-2 border-dashed border-gray-300 rounded-2xl py-20 text-center flex flex-col items-center">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>                   
                 </div>
                    <h3 class="text-base font-medium text-gray-600">ไม่มีรายการจองปัจจุบัน</h3>
                    <p class="text-sm text-gray-500 mt-1">คุณยังไม่มีตารางเล่นบาสในเร็วๆ นี้</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($current as $b)
                        @php 
                            [$stClass, $bdgClass, $stLabel, $stIcon] = getStatusDetails($b->status); 
                            $bDate = \Carbon\Carbon::parse($b->booking_date);
                        @endphp
                        
                        <div class="b-card">
                            {{-- 1. Details --}}
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-1">
                                    <h3 class="text-[14px] font-medium text-gray-700">{{ $b->court->name }}</h3>
                                    <span class="text-xs text-gray-400">ID: #{{ str_pad($b->id, 5, '0', STR_PAD_LEFT) }}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[13px] text-gray-600">
                                    <div class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2v-5H3v5a2 2 0 002 2z"/></svg>
                                        {{ $bDate->day }} {{ $thMonths[$bDate->month] }} '{{ substr($bDate->year + 543, 2, 2) }}
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <span>{{ substr($b->start_time, 0, 5) }} - {{ substr($b->end_time, 0, 5) }} น.</span>
                                    </div>
                                </div>
                            </div>

                            {{-- 3. Status Badge --}}
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 md:gap-4 mt-2 md:mt-0 border-t md:border-none border-gray-100 pt-3 md:pt-0">
                                <span class="badge {{ $bdgClass }}">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $stIcon !!}</svg>
                                    {{ $stLabel }}
                                </span>
                                
                                @if(!$b->isStarted())
                                    <form method="POST" action="{{ route('booking.cancel', $b) }}" onsubmit="return confirm('ยืนยันยกเลิกการจองนี้?');" class="md:ml-auto block">
                                        @csrf
                                        <button type="submit" class="btn-cancel w-full md:w-auto text-center">ยกเลิก</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ─── PAST BOOKINGS ─── --}}
        <div x-show="tab === 'past'" x-transition.opacity.duration.400ms style="display: none;">
            @if($past->isEmpty())
                <div class="border-2 border-dashed border-gray-300 rounded-2xl py-20 text-center flex flex-col items-center">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <h3 class="text-base font-medium text-gray-600">ไม่มีประวัติการจอง</h3>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($past as $b)
                        @php 
                            [$stClass, $bdgClass, $stLabel, $stIcon] = getStatusDetails($b->status);
                            $bDate = \Carbon\Carbon::parse($b->booking_date);
                        @endphp
                        
                        <div class="b-card opacity-80 hover:opacity-100">
                            {{-- 2. Details --}}
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-1">
                                    <h3 class="text-[14px] font-medium text-gray-600">{{ $b->court->name }}</h3>
                                    <span class="text-xs text-gray-400">ID: #{{ str_pad($b->id, 5, '0', STR_PAD_LEFT) }}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[13px] text-gray-500">
                                    <div class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2v-5H3v5a2 2 0 002 2z"/></svg>
                                        {{ $bDate->day }} {{ $thMonths[$bDate->month] }} '{{ substr($bDate->year + 543, 2, 2) }}
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <span class="font-medium">{{ substr($b->start_time, 0, 5) }} - {{ substr($b->end_time, 0, 5) }} น.</span>
                                    </div>
                                    @if($b->reject_reason)
                                    <div class="flex items-center gap-1 text-red-500 text-[12px] bg-red-50 px-2 py-0.5 rounded">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        เหตุผล: {{ $b->reject_reason }}
                                    </div>
                                    @endif
                                </div>
                            </div>

                            {{-- 3. Status Badge --}}
                            <div class="flex flex-col md:items-end justify-center mt-2 md:mt-0 border-t md:border-none border-gray-100 pt-3 md:pt-0">
                                <span class="badge {{ $bdgClass }}">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $stIcon !!}</svg>
                                    {{ $stLabel }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

</div>
@endsection