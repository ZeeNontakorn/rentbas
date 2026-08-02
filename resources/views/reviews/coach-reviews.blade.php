@extends('layouts.app')

@section('title', 'คะแนนของฉัน')

@section('content')
<div class="bg-white min-h-screen text-[#111827]">

<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');

.rv-main { font-family: 'Sarabun', 'Kanit', sans-serif; }
.rv-main h1, .rv-main h2, .rv-main h3 { font-family: 'Kanit', sans-serif; }
</style>

<div class="rv-main max-w-[900px] mx-auto px-4 py-8" data-aos="fade-up">

    <div class="mb-6">
        <h1 class="text-[28px] font-semibold text-gray-800 tracking-tight">คะแนนของฉัน</h1>
        <p class="text-gray-500 text-[15px] mt-1">คะแนนและความเห็นที่ผู้เรียนให้ไว้</p>
    </div>

    {{-- สรุปคะแนนรวม --}}
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm mb-6 flex flex-wrap items-center gap-6">
        <div>
            <div class="text-4xl font-bold text-gray-800">{{ number_format($average, 1) }}</div>
            <div class="mt-1">
                @include('reviews._stars', ['score' => $average, 'size' => 'h-5 w-5'])
            </div>
        </div>
        <div class="text-sm text-gray-500">
            จากทั้งหมด <span class="font-semibold text-gray-700">{{ $reviews->count() }}</span> รีวิว
        </div>
    </div>

    {{-- แจ้งให้ชัดว่าเป็น anonymous --}}
    <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 mb-6 flex items-start gap-2 text-sm text-blue-800">
        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        รีวิวแสดงแบบไม่ระบุตัวตน คุณจะไม่เห็นว่าใครเป็นคนให้คะแนน
    </div>

    @forelse ($reviews as $review)
        <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4">
            <div class="flex flex-wrap items-center gap-3">
                @include('reviews._stars', ['score' => $review->averageScore(), 'size' => 'h-4 w-4'])
                <span class="text-sm font-semibold text-gray-700">{{ number_format($review->averageScore(), 1) }}</span>
                <span class="text-xs text-gray-400">{{ $review->created_at->format('d/m/Y') }}</span>
            </div>

            @if ($review->comment)
                <p class="mt-2 text-sm text-gray-600">{{ $review->comment }}</p>
            @else
                <p class="mt-2 text-sm text-gray-300">ไม่มีความเห็นเพิ่มเติม</p>
            @endif
        </div>
    @empty
        <div class="border-2 border-dashed border-gray-300 rounded-2xl py-20 text-center flex flex-col items-center">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                <svg class="w-10 h-10 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.362-1.118l-3.977-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
            </div>
            <h3 class="text-base font-medium text-gray-600">ยังไม่มีใครให้คะแนน</h3>
            <p class="text-sm text-gray-400 mt-1">คะแนนจะขึ้นที่นี่หลังผู้เรียนรีวิวคาบเรียนที่จบไปแล้ว</p>
        </div>
    @endforelse
</div>
</div>
@endsection
