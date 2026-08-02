@extends('layouts.app')

@section('title', 'รีวิวของฉัน')

@section('content')
<div class="bg-white min-h-screen text-[#111827]">

<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');

.rv-main { font-family: 'Sarabun', 'Kanit', sans-serif; }
.rv-main h1, .rv-main h2, .rv-main h3 { font-family: 'Kanit', sans-serif; }
</style>

@php
    $categoryLabels = \App\Models\ReviewScore::allCategories();
@endphp

<div class="rv-main max-w-[900px] mx-auto px-4 py-8" data-aos="fade-up">

    <div class="mb-6">
        <h1 class="text-[28px] font-semibold text-gray-800 tracking-tight">รีวิวของฉัน</h1>
        <p class="text-gray-500 text-[15px] mt-1">คะแนนทั้งหมดที่คุณเคยให้ไว้</p>
    </div>

    @forelse ($reviews as $review)
        <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4">

            {{-- หัวการ์ด: รีวิวอะไร เมื่อไหร่ --}}
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3 pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    @if ($review->isCoachReview())
                        <span class="rounded-full bg-orange-100 px-2.5 py-1 text-[11px] font-medium text-orange-700">รีวิวโค้ช</span>
                        <span class="text-sm font-medium text-gray-700">โค้ช {{ $review->coach->name ?? '-' }}</span>
                    @else
                        <span class="rounded-full bg-green-100 px-2.5 py-1 text-[11px] font-medium text-green-700">รีวิวสถานที่</span>
                        <span class="text-sm font-medium text-gray-700">{{ $review->booking?->court?->name ?? 'สนาม' }}</span>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    @include('reviews._stars', ['score' => $review->averageScore(), 'size' => 'h-4 w-4'])
                    <span class="text-sm font-semibold text-gray-700">{{ number_format($review->averageScore(), 1) }}</span>
                    <span class="text-xs text-gray-400">{{ $review->created_at->format('d/m/Y') }}</span>
                </div>
            </div>

            {{-- คะแนนรายหมวด --}}
            <div class="grid gap-x-6 gap-y-2 sm:grid-cols-2">
                @foreach ($review->scores as $score)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">{{ $categoryLabels[$score->category] ?? $score->category }}</span>
                        @include('reviews._stars', ['score' => $score->score, 'size' => 'h-4 w-4'])
                    </div>
                @endforeach
            </div>

            @if ($review->comment)
                <p class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-600">{{ $review->comment }}</p>
            @endif
        </div>
    @empty
        <div class="border-2 border-dashed border-gray-300 rounded-2xl py-20 text-center flex flex-col items-center">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                <svg class="w-10 h-10 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.362-1.118l-3.977-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
            </div>
            <h3 class="text-base font-medium text-gray-600">คุณยังไม่เคยให้คะแนน</h3>
            <p class="text-sm text-gray-400 mt-1">หลังใช้บริการเสร็จแล้วจะให้คะแนนได้จากหน้าประวัติการจอง</p>
            <a href="{{ route('history') }}" class="mt-4 rounded-lg bg-[#87D068] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#76bc5a]">
                ไปหน้าประวัติการจอง
            </a>
        </div>
    @endforelse
</div>
</div>
@endsection
