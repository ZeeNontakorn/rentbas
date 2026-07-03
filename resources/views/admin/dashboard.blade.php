@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-6 max-w-7xl">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-gray-800">DashBoard</h1>
            <p class="text-sm text-gray-500">ดูสถิติ จัดการสนาม และดูการจองทั้งหมด</p>
        </div>

        <!-- Summary Cards -->
        @include('admin.partials.summary_cards')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Left Column: Manage Courts -->
            <div class="lg:col-span-2">
                <h2 class="text-lg font-bold mb-4">จัดการสนาม และ การจอง</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($courts as $court)
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 relative">

                        <!-- Court name + status badge -->
                        <div class="flex justify-between items-center mb-4">
                            <div class="px-3 py-1 border border-gray-300 rounded text-sm text-gray-700 bg-white">
                                {{ $court->name }}
                            </div>
                            @if($court->court_status === 'open' && !$court->isClosedAt(now(), now()->addMinute()))
                                <div class="px-2 py-1 text-xs text-orange-500 border border-orange-200 rounded flex items-center bg-white">
                                    <div class="w-2 h-2 rounded-full bg-orange-500 mr-1.5"></div>
                                    พร้อมให้บริการ
                                </div>
                            @else
                                <div class="px-2 py-1 text-xs text-red-500 border border-red-200 rounded flex items-center bg-white">
                                    <div class="w-2 h-2 rounded-full bg-red-500 mr-1.5"></div>
                                    ปิดให้บริการ
                                </div>
                            @endif
                        </div>

                        <!-- Court status row -->
                        <div class="mb-3">
                            <label class="text-xs text-gray-500 block mb-1">สถานะสนาม</label>
                            <form action="{{ route('admin.courts.status', $court) }}" method="POST">
                                @csrf
                                <select name="court_status" onchange="this.form.submit()" 
                                        class="w-full border border-gray-200 rounded p-2 text-sm text-gray-600 flex items-center bg-white cursor-pointer focus:border-orange-500 outline-none transition">
                                    <option value="open" @selected($court->court_status === 'open')>พร้อมให้บริการ</option>
                                    <option value="closed" @selected($court->court_status === 'closed')>ปิดให้บริการ (Closed)</option>
                                </select>
                            </form>
                        </div>

                        <!-- Booking stats -->
                        <div class="mb-4">
                            <label class="text-xs text-gray-500 block mb-1">จัดการการจอง</label>
                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('admin.bookings', ['court_id' => $court->id, 'date' => now()->toDateString()]) }}"
                                   class="border border-gray-200 rounded p-2 flex justify-between items-center bg-white hover:border-blue-300 hover:bg-blue-50 transition cursor-pointer">
                                    <span class="text-xs text-gray-600 flex items-center">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-1.5"></span>
                                        รายการจองทั้งหมด
                                    </span>
                                    <span class="text-xs text-blue-500 font-medium">{{ $court->total_today ?? 0 }}</span>
                                </a>
                                <a href="{{ route('admin.bookings', ['court_id' => $court->id, 'status' => 'approved', 'date' => now()->toDateString()]) }}"
                                   class="border border-gray-200 rounded p-2 flex justify-between items-center bg-white hover:border-green-300 hover:bg-green-50 transition cursor-pointer">
                                    <span class="text-xs text-gray-600 flex items-center">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5"></span>
                                        ใช้งานอยู่
                                    </span>
                                    <span class="text-xs text-green-500 font-medium">{{ $court->active_now ?? 0 }}</span>
                                </a>
                                <a href="{{ route('admin.bookings', ['court_id' => $court->id, 'status' => 'pending', 'date' => now()->toDateString()]) }}"
                                   class="border border-gray-200 rounded p-2 flex justify-between items-center bg-white hover:border-orange-300 hover:bg-orange-50 transition cursor-pointer">
                                    <span class="text-xs text-gray-600 flex items-center">
                                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500 mr-1.5"></span>
                                        คำขอจอง
                                    </span>
                                    <span class="text-xs text-orange-500 font-medium">{{ $court->pending_count ?? 0 }}</span>
                                </a>
                                <a href="{{ route('admin.bookings', ['court_id' => $court->id, 'status' => 'cancelled', 'date' => now()->toDateString()]) }}"
                                   class="border border-gray-200 rounded p-2 flex justify-between items-center bg-white hover:border-red-300 hover:bg-red-50 transition cursor-pointer">
                                    <span class="text-xs text-gray-600 flex items-center">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span>
                                        ถูกยกเลิก
                                    </span>
                                    <span class="text-xs text-red-500 font-medium">{{ $court->cancelled_count ?? 0 }}</span>
                                </a>
                            </div>
                        </div>

                        <!-- Court detail link -->
                        <div>
                            <label class="text-xs text-gray-500 block mb-1">จัดการสถานะรายชั่วโมง</label>
                            <a href="{{ route('admin.courts', ['court_id' => $court->id]) }}"
                               class="block w-full text-center border border-orange-200 bg-orange-50 rounded py-2 text-xs text-orange-600 hover:bg-orange-100 transition font-bold">
                                จัดการสถานะช่วงเวลา
                            </a>
                        </div>

                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Right Column: Charts -->
            <div class="lg:col-span-1 flex flex-col gap-6">

                <!-- Bar Chart 1: Members -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 flex flex-col">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">สถิติผู้สมัครสมาชิก (Users)</h3>
                    <p class="text-xs text-gray-400 mb-6">เปรียบเทียบสัปดาห์นี้ กับ ยอดรวมเดือนนี้</p>

                    @php
                        $mMax     = max($usersMonth, 1);
                        $mMonthPct = min(($usersMonth / $mMax) * 100, 100);
                        $mWeekPct  = min(($usersWeek  / $mMax) * 100, 100);
                    @endphp

                    <div class="flex items-end justify-around h-32 border-b border-gray-100 pb-2">
                        <div class="w-16 flex flex-col items-center justify-end h-full group">
                            <span class="text-xs font-bold text-blue-600 mb-1 opacity-0 group-hover:opacity-100 transition-opacity">{{ number_format($usersWeek) }}</span>
                            <div class="w-full bg-blue-400/80 hover:bg-blue-500 rounded-t-sm transition-all duration-700" style="height: {{ max($mWeekPct, 10) }}%;"></div>
                        </div>
                        <div class="w-16 flex flex-col items-center justify-end h-full group">
                            <span class="text-xs font-bold text-green-600 mb-1 opacity-0 group-hover:opacity-100 transition-opacity">{{ number_format($usersMonth) }}</span>
                            <div class="w-full bg-green-400/80 hover:bg-green-500 rounded-t-sm transition-all duration-700" style="height: {{ max($mMonthPct, 10) }}%;"></div>
                        </div>
                    </div>

                    <div class="flex justify-around mt-2">
                        <div class="text-center">
                            <span class="block text-[10px] text-gray-500">สัปดาห์นี้</span>
                            <span class="block text-sm font-medium text-gray-700">{{ number_format($usersWeek) }}</span>
                        </div>
                        <div class="text-center">
                            <span class="block text-[10px] text-gray-500">เดือนนี้</span>
                            <span class="block text-sm font-bold text-gray-800">{{ number_format($usersMonth) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Bar Chart 2: Visitors -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 flex flex-col">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">สถิติผู้เข้าชมเว็บไซต์ (Visits)</h3>
                    <p class="text-xs text-gray-400 mb-6">เปรียบเทียบสัปดาห์นี้ กับ ยอดรวมเดือนนี้</p>

                    @php
                        $vMax      = max($visitsMonth, 1);
                        $vMonthPct = min(($visitsMonth / $vMax) * 100, 100);
                        $vWeekPct  = min(($visitsWeek  / $vMax) * 100, 100);
                    @endphp

                    <div class="flex items-end justify-around h-32 border-b border-gray-100 pb-2">
                        <div class="w-16 flex flex-col items-center justify-end h-full group">
                            <span class="text-xs font-bold text-blue-600 mb-1 opacity-0 group-hover:opacity-100 transition-opacity">{{ number_format($visitsWeek) }}</span>
                            <div class="w-full bg-blue-400/80 hover:bg-blue-500 rounded-t-sm transition-all duration-700" style="height: {{ max($vWeekPct, 10) }}%;"></div>
                        </div>
                        <div class="w-16 flex flex-col items-center justify-end h-full group">
                            <span class="text-xs font-bold text-green-600 mb-1 opacity-0 group-hover:opacity-100 transition-opacity">{{ number_format($visitsMonth) }}</span>
                            <div class="w-full bg-green-400/80 hover:bg-green-500 rounded-t-sm transition-all duration-700" style="height: {{ max($vMonthPct, 10) }}%;"></div>
                        </div>
                    </div>

                    <div class="flex justify-around mt-2">
                        <div class="text-center">
                            <span class="block text-[10px] text-gray-500">สัปดาห์นี้</span>
                            <span class="block text-sm font-bold text-gray-800">{{ number_format($visitsWeek) }}</span>
                        </div>
                        <div class="text-center">
                            <span class="block text-[10px] text-gray-500">เดือนนี้</span>
                            <span class="block text-sm font-bold text-gray-800">{{ number_format($visitsMonth) }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Notifications: pending booking requests -->
        <div class="mt-8">
            <h2 class="text-base font-semibold text-gray-700 mb-4 flex items-center">
                แจ้งเตือน รายการคำขอการจอง
                <svg class="w-5 h-5 ml-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </h2>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="space-y-3">
                    @forelse(auth()->user()->notifications()->latest()->take(3)->get() as $n)
                    <div class="border border-red-200 rounded-lg p-3 text-sm flex items-center justify-between text-gray-700">
                        <div class="flex items-center">
                            <div class="text-green-500 mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                            </div>
                            <span class="mr-4"><strong>{{ $n->title }}</strong>: {{ explode('|', $n->message)[0] ?? '' }}</span>
                        </div>
                        <div class="text-gray-500 flex gap-8">
                            <span>วันที่ทำรายการ: {{ $n->created_at->translatedFormat('d F Y') }}</span>
                            <span>เวลา: {{ $n->created_at->format('H:i') }}</span>
                            <span>{{ explode('|', $n->message)[1] ?? '' }}</span>
                        </div>
                    </div>
                    @empty
                        <div class="text-gray-500 text-center py-4">ไม่มีแจ้งเตือนใหม่</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
