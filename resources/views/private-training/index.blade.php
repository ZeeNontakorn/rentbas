@extends('layouts.app')

@section('title', 'เทรนเนอร์ส่วนตัว')

@php
    $statusMap = [
        'pending' => ['label' => 'รออนุมัติ', 'bg' => 'bg-orange-100', 'text' => 'text-orange-600'],
        'awaiting_court' => ['label' => 'รอจัดสนาม', 'bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
        'confirmed' => ['label' => 'ยืนยันแล้ว', 'bg' => 'bg-green-100', 'text' => 'text-green-600'],
        'rejected' => ['label' => 'ถูกปฏิเสธ', 'bg' => 'bg-red-100', 'text' => 'text-red-600'],
        'cancelled' => ['label' => 'ยกเลิกแล้ว', 'bg' => 'bg-gray-100', 'text' => 'text-gray-600'],

    ];
@endphp

@section('content')
    <div class="bg-slate-50 text-gray-900 min-h-screen py-8">
        <div class="container mx-auto px-6 max-w-7xl">

            {{-- ส่วน Title และ ช่องค้นหา --}}
            <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl font-semibold text-gray-800">เทรนเนอร์ส่วนตัว (Private Training)</h1>
                    <p class="text-sm text-gray-500 mt-1">เลือกดูโปรไฟล์และตารางว่างของโค้ช เพื่อจองเวลาเรียนส่วนตัว</p>
                </div>

                {{-- ค้นหา --}}
                <form method="GET" action="{{ route('private-training.index') }}"
                    class="flex w-full md:w-110 flex-shrink-0 md:ml-auto">
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="ค้นหาชื่อโค้ช..."
                        class="w-full min-w-0 border border-gray-300 rounded-l-lg px-4 py-2.5 text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition">
                    <button type="submit"
                        class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2.5 rounded-r-lg text-sm font-medium transition flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z" />
                        </svg>
                        ค้นหา
                    </button>
                </form>
            </div>

            {{-- รายชื่อโค้ช (ซ้าย) + ประวัติคำขอของฉัน --}}
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">

                {{-- รายชื่อโค้ช --}}
                <div class="lg:col-span-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                        @forelse($coaches as $coach)
                            <a href="{{ route('private-training.show', $coach->id) }}"
                                class="bg-white rounded-2xl border border-gray-200 overflow-hidden hover:border-orange-300 hover:shadow-md transition group flex flex-col">

                                {{-- รูปโปรไฟล์โค้ช --}}
                                <div
                                    class="w-full h-70 bg-orange-50 flex items-center justify-center overflow-hidden border-b border-orange-100 flex-shrink-0">
                                    @if($coach->staffProfile?->profile_image_url)
                                        <img src="{{ $coach->staffProfile->profile_image_url }}" alt="{{ $coach->name }}"
                                            class="w-full h-full object-cover object-top">
                                    @else
                                        @php
                                            $defaultCoachImage = match ($coach->staffProfile?->gender) {
                                                'male' => asset('images/defaults/coach-male.png'),
                                                'female' => asset('images/defaults/coach-female.png'),
                                                default => asset('images/defaults/coach-default.svg'),
                                            };
                                        @endphp
                                        <img src="{{ $defaultCoachImage }}" alt="{{ $coach->name }}"
                                            class="w-full h-full object-cover object-top">
                                    @endif
                                </div>

                                <div class="p-5 flex flex-col flex-1">
                                    <div class="flex items-center gap-3">
                                        <p class="font-semibold text-gray-800 truncate group-hover:text-orange-600 transition">
                                            {{ $coach->name }}
                                        </p>
                                    </div>
                                    <span
                                        class="inline-block mt-1 w-fit px-2 py-0.5 text-[11px] rounded-full font-medium bg-blue-100 text-blue-700">ผู้ฝึกสอน
                                        (Coach)</span>

                                    {{-- ดาวเฉลี่ยจากรีวิวของลูกค้า --}}
                                    <div class="mt-2 flex items-center gap-1.5 text-[11px] text-gray-500">
                                        @if($coach->rating_count)
                                            @include('reviews._stars', ['score' => $coach->rating_avg, 'size' => 'h-3.5 w-3.5'])
                                            <span class="font-semibold text-gray-700">{{ number_format((float) $coach->rating_avg, 1) }}</span>
                                            <span>({{ $coach->rating_count }} รีวิว)</span>
                                        @else
                                            <span class="text-gray-400">ยังไม่มีรีวิว</span>
                                        @endif
                                    </div>

                                    <div class="mt-4 text-xs text-gray-500 space-y-1">
                                        <p><span class="font-medium text-gray-600">ความเชี่ยวชาญ:</span>
                                            {{ $coach->staffProfile?->specialty ?? 'ผู้ช่วยฝึกสอนเบสิค' }}</p>
                                        <p class="line-clamp-2"><span class="font-medium text-gray-600">แนะนำตัว:</span>
                                            {{ $coach->staffProfile?->bio ?? 'ไม่มีข้อมูล' }}</p>
                                    </div>

                                    <div class="mt-4 inline-flex items-center gap-1.5 text-xs font-medium text-orange-600">
                                        ดูตารางว่างและจองเวลา
                                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="col-span-full">
                                <div class="bg-white rounded-xl border border-gray-200 py-16 text-center">
                                    <p class="text-gray-400 font-medium text-sm">ยังไม่มีข้อมูลโค้ชในระบบ</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Sidebar: คำขอของฉัน --}}
                <div class="lg:col-span-1 lg:grid-cols-4 gap-6 lg:top-6">

                    {{-- คาบที่เรียนจบแล้วแต่ยังไม่ได้ให้คะแนนโค้ช --}}
                    @if($reviewable->isNotEmpty())
                        <div class="bg-white rounded-xl shadow-sm border border-orange-200 overflow-hidden mb-6">
                            <div class="px-5 py-4 border-b border-orange-100 bg-orange-50">
                                <h2 class="font-medium text-orange-800 text-sm flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.362-1.118l-3.977-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                    รอให้คะแนนโค้ช
                                </h2>
                            </div>
                            <div class="divide-y divide-gray-100">
                                @foreach($reviewable as $r)
                                    <div class="px-5 py-3">
                                        <p class="text-sm font-medium text-gray-700">โค้ช {{ $r->coach->name }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            {{ \Carbon\Carbon::parse($r->date)->format('d/m/Y') }}
                                            <span class="text-gray-300 mx-1">•</span>
                                            {{ substr($r->start_time, 0, 5) }} - {{ substr($r->end_time, 0, 5) }} น.
                                        </p>
                                        <a href="{{ route('reviews.create-coach', $r) }}"
                                           class="mt-2 inline-flex items-center gap-1 rounded-lg bg-orange-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-orange-600">
                                            ให้คะแนน
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100">
                            <h2 class="font-medium text-gray-700 text-sm">คำขอจองเทรนเนอร์ของฉัน</h2>
                        </div>
                        <div class="divide-y divide-gray-100 max-h-[70vh] overflow-y-auto">
                            @forelse($myRequests as $r)
                                @php $sInfo = $statusMap[$r->status] ?? $statusMap['pending']; @endphp
                                <div class="px-5 py-3">
                                    <p class="text-sm font-medium text-gray-700">โค้ช {{ $r->coach->name }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ \Carbon\Carbon::parse($r->date)->format('d/m/Y') }}
                                        <span class="text-gray-300 mx-1">•</span>
                                        {{ substr($r->start_time, 0, 5) }} - {{ substr($r->end_time, 0, 5) }} น.
                                    </p>
                                    <div class="flex items-center justify-between mt-2">
                                        <span
                                            class="text-xs px-2.5 py-1 rounded-full font-medium {{ $sInfo['bg'] }} {{ $sInfo['text'] }}">{{ $sInfo['label'] }}</span>
                                        @if(in_array($r->status, ['pending', 'awaiting_court'], true))
                                            <form method="POST" action="{{ route('private-training.cancel', $r) }}"
                                                class="cancel-form">
                                                @csrf
                                                <button type="button"
                                                    class="btn-cancel-request text-xs text-red-500 hover:text-red-600 font-medium">ยกเลิก</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="px-5 py-8 text-center">
                                    <p class="text-gray-400 text-xs">ยังไม่มีคำขอจองเทรนเนอร์</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.querySelectorAll('.btn-cancel-request').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const form = this.closest('form');
                Swal.fire({
                    title: 'ยกเลิกคำขอจองนี้?',
                    text: 'คุณจะไม่สามารถกู้คืนคำขอนี้ได้',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#9ca3af',
                    confirmButtonText: 'ใช่, ยกเลิกเลย',
                    cancelButtonText: 'ปิด',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            });
        });
    </script>
@endsection
