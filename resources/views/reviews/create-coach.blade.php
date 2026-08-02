@extends('layouts.app')

@section('title', 'ให้คะแนนโค้ช')

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
    $d = $booking->date;
@endphp

<div class="rv-main max-w-[760px] mx-auto px-4 py-8" data-aos="fade-up">

    {{-- HEADER --}}
    <div class="mb-6">
        <a href="{{ route('private-training.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            กลับไปหน้า Private Training
        </a>
        <h1 class="mt-3 text-[28px] font-semibold text-gray-800 tracking-tight">ให้คะแนนโค้ช</h1>
        <p class="text-gray-500 text-[15px] mt-1">บอกเราหน่อยว่าการเรียนคาบนี้เป็นยังไงบ้าง</p>
    </div>

    {{-- โค้ชที่กำลังรีวิว --}}
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm mb-6 flex items-center gap-4">
        @if ($coach->staffProfile?->profile_image_url)
            <img src="{{ $coach->staffProfile->profile_image_url }}" alt="{{ $coach->name }}"
                 class="h-16 w-16 rounded-full object-cover">
        @else
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-orange-100 text-2xl font-bold text-orange-600">
                {{ mb_substr($coach->name, 0, 1) }}
            </div>
        @endif

        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-lg font-bold text-gray-800">{{ $coach->name }}</h2>
                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">ผู้ฝึกสอน (Coach)</span>
            </div>
            <p class="mt-1 text-sm text-gray-500">
                {{ $d->day }} {{ $thMonths[$d->month] }} {{ $d->year + 543 }}
                · {{ substr($booking->start_time, 0, 5) }} - {{ substr($booking->end_time, 0, 5) }} น.
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('reviews.store-coach', $booking) }}">
        @csrf

        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm mb-5">
            <h2 class="font-semibold text-gray-800 mb-1">คะแนนโค้ชผู้สอน</h2>
            <p class="text-xs text-gray-400 mb-2">แตะที่ดาวเพื่อให้คะแนน 1-5 ดาว</p>

            @include('reviews._star-input', ['category' => 'coach', 'label' => 'โค้ชผู้สอน Private'])
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm mb-5">
            <label for="comment" class="block font-semibold text-gray-800 mb-1">ความคิดเห็นเพิ่มเติม</label>
            <p class="text-xs text-gray-400 mb-3">ไม่บังคับ — โค้ชจะเห็นความเห็นนี้ แต่จะไม่เห็นว่าใครเป็นคนเขียน</p>
            <textarea id="comment" name="comment" rows="4" maxlength="1000"
                      placeholder="เช่น สอนละเอียด อธิบายเข้าใจง่าย ฯลฯ"
                      class="w-full resize-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-orange-500">{{ old('comment') }}</textarea>
            @error('comment') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="flex gap-3">
            <a href="{{ route('private-training.index') }}"
               class="w-1/3 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-center text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                ยกเลิก
            </a>
            <button type="submit"
                    class="w-2/3 rounded-lg bg-orange-500 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-orange-600">
                ส่งคะแนน
            </button>
        </div>
    </form>
</div>
</div>
@endsection
