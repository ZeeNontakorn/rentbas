@extends('layouts.app')

@section('title', 'ให้คะแนนการใช้บริการ')

@section('content')
<div class="bg-white min-h-screen text-[#111827]">

<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');

.rv-main { font-family: 'Sarabun', 'Kanit', sans-serif; }
.rv-main h1, .rv-main h2, .rv-main h3 { font-family: 'Kanit', sans-serif; }
.form-error { font-size: 12px; color: #dc2626; margin-top: 4px; }
</style>

@php
    $thMonths = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    $d = $booking->booking_date;
@endphp

<div class="rv-main max-w-[760px] mx-auto px-4 py-8" data-aos="fade-up">

    {{-- HEADER --}}
    <div class="mb-6">
        <a href="{{ route('history') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            กลับไปหน้าประวัติการจอง
        </a>
        <h1 class="mt-3 text-[28px] font-semibold text-gray-800 tracking-tight">ให้คะแนนการใช้บริการ</h1>
        <p class="text-gray-500 text-[15px] mt-1">ความเห็นของคุณช่วยให้เราปรับปรุงบริการให้ดีขึ้น</p>
    </div>

    {{-- สรุปการจองที่กำลังรีวิว --}}
    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 mb-6 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
        <div class="flex items-center gap-2 font-semibold text-gray-800">
            <svg class="w-4 h-4 text-[#87D068]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            {{ $booking->court->name ?? 'สนาม' }}
        </div>
        <div class="text-gray-600">
            {{ $d->day }} {{ $thMonths[$d->month] }} {{ $d->year + 543 }}
        </div>
        <div class="text-gray-600">
            {{ substr($booking->start_time, 0, 5) }} - {{ substr($booking->end_time, 0, 5) }} น.
        </div>
        <div class="text-gray-400 ml-auto">#{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</div>
    </div>

    <form method="POST" action="{{ route('reviews.store', $booking) }}">
        @csrf

        {{-- คะแนน 5 หมวด --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 mb-5">
            <h2 class="font-semibold text-gray-800 mb-1">ให้คะแนนแต่ละด้าน</h2>
            <p class="text-xs text-gray-400 mb-2">แตะที่ดาวเพื่อให้คะแนน 1-5 ดาว</p>

            <div class="divide-y divide-gray-100">
                @foreach ($categories as $category => $label)
                    @include('reviews._star-input', ['category' => $category, 'label' => $label])
                @endforeach
            </div>
        </div>

        {{-- ความคิดเห็น --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 mb-5">
            <label for="comment" class="block font-semibold text-gray-800 mb-1">ความคิดเห็นเพิ่มเติม</label>
            <p class="text-xs text-gray-400 mb-3">ไม่บังคับ — อยากบอกอะไรเพิ่มเติมเขียนได้เลย</p>
            <textarea id="comment" name="comment" rows="4" maxlength="1000"
                      placeholder="เช่น พื้นสนามลื่นช่วงเย็น, ห้องน้ำสะอาดมาก ฯลฯ"
                      class="w-full resize-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-[#87D068]">{{ old('comment') }}</textarea>
            @error('comment') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="flex gap-3">
            <a href="{{ route('history') }}"
               class="w-1/3 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-center text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                ยกเลิก
            </a>
            <button type="submit"
                    class="w-2/3 rounded-lg bg-[#87D068] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#76bc5a]">
                ส่งคะแนน
            </button>
        </div>
    </form>
</div>
</div>
@endsection
