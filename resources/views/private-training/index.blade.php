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

                @if($canViewCoaches)
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
                @endif
            </div>

            {{-- Layout หลัก: รายชื่อโค้ช/popup ซื้อแพ็กเกจ (ซ้าย) + คำขอของฉัน (ขวา) แสดงเสมอ --}}
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">

                {{-- ฝั่งซ้าย: รายชื่อโค้ช ถ้ามีแพ็กเกจ / popup ซื้อแพ็กเกจ ถ้าไม่มี --}}
                <div class="lg:col-span-3">
                    @if($canViewCoaches)
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                            @forelse($coaches as $coach)
                                <a href="{{ route('private-training.show', $coach->id) }}"
                                    class="bg-white rounded-2xl border border-gray-200 overflow-hidden hover:border-orange-300 hover:shadow-md transition group flex flex-col">

                                    <div class="w-full h-70 bg-orange-50 flex items-center justify-center overflow-hidden border-b border-orange-100 flex-shrink-0">
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
                                        <span class="inline-block mt-1 w-fit px-2 py-0.5 text-[11px] rounded-full font-medium bg-blue-100 text-blue-700">ผู้ฝึกสอน (Coach)</span>

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
                        @if(!$hasValidPackage)
                            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                                คุณใช้สิทธิ์แพ็กเกจครบแล้ว หากต้องการจองเทรนเนอร์เพิ่ม กรุณาซื้อแพ็กเกจใหม่
                                <button type="button" id="btn-need-package" class="ml-1 font-semibold underline hover:text-amber-900">ซื้อแพ็กเกจ</button>
                            </div>
                        @endif
                    @else
                        {{-- ไม่มีแพ็กเกจที่ใช้ได้ --}}
                        <div class="bg-white rounded-xl border border-gray-200 py-20 text-center">
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-orange-100">
                                <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <p class="text-gray-700 font-medium">คุณยังไม่มีแพ็กเกจที่ใช้จองเทรนเนอร์ได้</p>
                            <p class="text-sm text-gray-400 mt-1">กรุณาซื้อแพ็กเกจก่อนเพื่อเริ่มจองเวลาเรียนกับโค้ช</p>
                            <button type="button" id="btn-need-package"
                                    class="mt-5 inline-flex items-center gap-2 rounded-lg bg-orange-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-orange-600">
                                ซื้อแพ็กเกจ
                            </button>
                        </div>
                    @endif
                </div>

                {{-- ฝั่งขวา: คำขอของฉัน — แสดงเสมอ ไม่ว่าจะมีแพ็กเกจเหลือหรือไม่ --}}
                <div class="lg:col-span-1 lg:grid-cols-4 gap-6 lg:top-6">
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
                                        <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $sInfo['bg'] }} {{ $sInfo['text'] }}">{{ $sInfo['label'] }}</span>
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
        document.getElementById('btn-need-package')?.addEventListener('click', function () {
            window.location.href = "{{ route('home') }}#packages";
        });

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
