@extends('layouts.app')

@section('title', $court->name.' — ตารางเวลา')

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-10">
    <div class="w-full px-6 md:px-12 lg:px-20">

        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-10 gap-6">
            <div>
                <h2 class="text-4xl font-black uppercase italic text-gray-900 tracking-tighter">
                    {{ $court->name }} <span class="text-orange-500">SCHEDULE</span>
                </h2>
                <p class="text-gray-500 mt-2 font-medium">
                    ตารางการจองประจำวันที่ <span class="text-gray-900 font-bold">{{ $date }}</span>
                </p>
            </div>

            <a href="{{ route('booking.index', ['date' => $date]) }}"
               class="inline-flex items-center text-sm font-bold text-gray-500 hover:text-orange-500 transition uppercase tracking-widest">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                กลับหน้ารวมสนาม
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:gap-10">

            {{-- LEFT COLUMN: CONTROL & LEGEND --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- DATE PICKER --}}
                <div class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm">
                    <form method="GET" class="space-y-4">
                        <label class="block text-xs font-black uppercase tracking-[0.2em] text-gray-500">
                            เปลี่ยนวันที่จอง
                        </label>
                        <input type="date"
                               name="date"
                               value="{{ $date }}"
                               min="{{ now()->toDateString() }}"
                               class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-gray-900 focus:ring-2 focus:ring-orange-500 outline-none transition">

                        <button type="submit"
                                class="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-xl font-black uppercase tracking-widest transition shadow-md hover:shadow-lg shadow-orange-500/20">
                            อัปเดตตาราง
                        </button>
                    </form>
                </div>

                {{-- LEGEND --}}
                <div class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm">
                    <h4 class="text-xs font-black uppercase tracking-[0.2em] text-gray-500 mb-6">สัญลักษณ์สี</h4>
                    <div class="grid grid-cols-1 gap-4">
                        <div class="flex items-center group">
                            <span class="w-10 h-10 rounded-xl bg-green-50 border border-green-200 flex items-center justify-center mr-4">
                                <span class="w-2 h-2 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.6)]"></span>
                            </span>
                            <span class="text-sm font-bold text-gray-700">ว่าง / พร้อมจอง</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-10 h-10 rounded-xl bg-yellow-50 border border-yellow-200 flex items-center justify-center mr-4">
                                <span class="w-2 h-2 rounded-full bg-yellow-400"></span>
                            </span>
                            <span class="text-sm font-bold text-gray-700">รอการอนุมัติ</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-10 h-10 rounded-xl bg-red-50 border border-red-200 flex items-center justify-center mr-4">
                                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            </span>
                            <span class="text-sm font-bold text-gray-700">จองแล้ว</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-10 h-10 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-center mr-4">
                                <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                            </span>
                            <span class="text-sm font-bold text-gray-500">ไม่เปิด / ผ่านมาแล้ว</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: TIME GRID --}}
            <div class="lg:col-span-3">
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($slots as $slot)
                        @php
                            $statusClasses = match($slot['status']) {
                                'available' => 'bg-white border-green-200 hover:border-green-500 hover:bg-green-50 text-green-600 shadow-sm hover:shadow-md',
                                'pending' => 'bg-yellow-50 border-yellow-200 text-yellow-600 cursor-not-allowed',
                                'approved' => 'bg-red-50 border-red-200 text-red-500 cursor-not-allowed',
                                'closed', 'past' => 'bg-gray-50 border-gray-200 text-gray-400 cursor-not-allowed',
                                default => 'bg-gray-100 border-gray-200 text-gray-400',
                            };

                            $statusLabel = match($slot['status']) {
                                'available' => 'กดเพื่อจอง',
                                'pending' => 'รออนุมัติ',
                                'approved' => 'จองแล้ว',
                                'closed' => 'ปิดบริการ',
                                'past' => 'หมดเวลา',
                                default => '',
                            };

                            $isAvailable = $slot['status'] === 'available';
                        @endphp

                        @if ($isAvailable)
                            <form method="POST" action="{{ route('booking.store') }}"
                                  onsubmit="return confirm('ยืนยันการจองช่วงเวลา {{ $slot['label'] }} ?');">
                                @csrf
                                <input type="hidden" name="court_id" value="{{ $court->id }}">
                                <input type="hidden" name="booking_date" value="{{ $date }}">
                                <input type="hidden" name="start_time" value="{{ substr($slot['start'], 0, 5) }}">
                                <input type="hidden" name="end_time" value="{{ substr($slot['end'], 0, 5) }}">

                                <button type="submit" class="w-full border-2 rounded-3xl p-5 text-center transition-all duration-300 group {{ $statusClasses }}">
                                    <div class="text-xl font-black tracking-tighter">{{ $slot['label'] }}</div>
                                    <div class="text-[10px] font-bold uppercase mt-1 tracking-widest opacity-80 group-hover:opacity-100 group-hover:text-green-700">
                                        {{ $statusLabel }}
                                    </div>
                                </button>
                            </form>
                        @else
                            <div class="border-2 rounded-3xl p-5 text-center transition-all {{ $statusClasses }}">
                                <div class="text-xl font-black tracking-tighter">{{ $slot['label'] }}</div>
                                <div class="text-[10px] font-bold uppercase mt-1 tracking-widest opacity-60">
                                    {{ $statusLabel }}
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
