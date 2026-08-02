@extends('layouts.app')

@section('title', 'รีวิวและคะแนน')

@section('content')
<div class="bg-slate-50 min-h-screen py-8">
    <div class="container mx-auto max-w-6xl px-4 sm:px-6">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">รีวิวและคะแนนจากลูกค้า</h1>
            <p class="mt-1 text-sm text-gray-500">คะแนนการใช้บริการทั้ง 6 หมวด</p>
        </div>

        {{-- สรุปค่าเฉลี่ยรายหมวด --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="font-bold text-gray-800">ค่าเฉลี่ยรายหมวด</h3>
                    <p class="text-xs text-gray-400">คะแนนเต็ม 5 ดาว</p>
                </div>
                <span class="text-xs font-semibold text-orange-600 bg-orange-50 px-2.5 py-1 rounded-full">
                    เฉลี่ยรวม: {{ number_format($overallAverage, 1) }}
                </span>
            </div>

            <div class="space-y-1">
                @foreach ($averages as $key => $row)
                    <div class="flex items-center justify-between rounded-xl px-3 py-2.5 hover:bg-slate-50 transition">
                        <span class="text-sm text-gray-700">{{ $row['label'] }}</span>
                        <div class="flex items-center gap-3">
                            @include('reviews._stars', ['score' => $row['avg'], 'size' => 'h-4 w-4'])
                            <span class="w-8 text-right text-sm font-bold text-gray-800">
                                {{ $row['count'] ? number_format($row['avg'], 1) : '-' }}
                            </span>
                            <span class="w-16 text-right text-xs text-gray-400">{{ $row['count'] }} รีวิว</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ตัวกรอง --}}
        <form method="GET" action="{{ route('admin.reviews.index') }}"
              class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 mb-6 flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">หมวด</label>
                <select name="category" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">ทุกหมวด</option>
                    @foreach ($categories as $key => $label)
                        <option value="{{ $key }}" @selected($category === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">ช่วงเวลา</label>
                <select name="days" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="0" @selected($days === 0)>ทั้งหมด</option>
                    <option value="7" @selected($days === 7)>7 วันล่าสุด</option>
                    <option value="30" @selected($days === 30)>30 วันล่าสุด</option>
                    <option value="90" @selected($days === 90)>90 วันล่าสุด</option>
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-600">
                กรอง
            </button>
            @if ($category || $days)
                <a href="{{ route('admin.reviews.index') }}" class="text-sm text-gray-500 hover:text-gray-700 px-2 py-2">ล้างตัวกรอง</a>
            @endif
        </form>

        {{-- รายการรีวิว --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-start justify-between mb-4">
                <h3 class="font-bold text-gray-800">รีวิวทั้งหมด</h3>
                <span class="text-xs font-semibold text-gray-600 bg-gray-100 px-2.5 py-1 rounded-full">{{ $reviews->count() }} รายการ</span>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse ($reviews as $review)
                    <div class="py-4 first:pt-0">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            @if ($review->isCoachReview())
                                <span class="rounded-full bg-orange-100 px-2.5 py-1 text-[11px] font-medium text-orange-700">โค้ช</span>
                                <span class="text-sm font-medium text-gray-700">{{ $review->coach->name ?? '-' }}</span>
                            @else
                                <span class="rounded-full bg-green-100 px-2.5 py-1 text-[11px] font-medium text-green-700">สถานที่</span>
                                <span class="text-sm font-medium text-gray-700">{{ $review->booking?->court?->name ?? 'สนาม' }}</span>
                            @endif

                            <span class="text-xs text-gray-400">โดย {{ $review->user->name ?? 'ผู้ใช้' }}</span>
                            <span class="text-xs text-gray-300">·</span>
                            <span class="text-xs text-gray-400">{{ $review->created_at->format('d/m/Y H:i') }}</span>

                            <span class="ml-auto flex items-center gap-1.5">
                                @include('reviews._stars', ['score' => $review->averageScore(), 'size' => 'h-4 w-4'])
                                <span class="text-sm font-bold text-gray-800">{{ number_format($review->averageScore(), 1) }}</span>
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                            @foreach ($review->scores as $score)
                                <span>{{ $categories[$score->category] ?? $score->category }}:
                                    <strong class="text-gray-700">{{ $score->score }}</strong>
                                </span>
                            @endforeach
                        </div>

                        @if ($review->comment)
                            <p class="mt-2 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-600">{{ $review->comment }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-8">ยังไม่มีรีวิว</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
