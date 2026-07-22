@extends('layouts.app')

@section('title', 'เทรนเนอร์ส่วนตัว')

@php
    // เก็บ Config ข้อมูล Status ไว้สำหรับแปลงค่าและแสดงสี
    $statusMap = [
        'pending'  => ['label' => 'รออนุมัติ', 'bg' => 'bg-orange-100', 'text' => 'text-orange-600'],
        'approved' => ['label' => 'อนุมัติแล้ว', 'bg' => 'bg-green-100', 'text' => 'text-green-600'],
        'rejected' => ['label' => 'ถูกปฏิเสธ', 'bg' => 'bg-red-100', 'text' => 'text-red-600'],
        'canceled' => ['label' => 'ยกเลิก', 'bg' => 'bg-gray-100', 'text' => 'text-gray-600'],
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
            <form method="GET" action="{{ route('private-training.index') }}" class="flex w-full md:max-w-md flex-shrink-0 md:ml-auto">
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="ค้นหาชื่อโค้ช..."
                    class="w-full min-w-0 border border-gray-300 rounded-l-lg px-4 py-2.5 text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2.5 rounded-r-lg text-sm font-medium transition flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/>
                    </svg>
                    ค้นหา
                </button>
            </form>
        </div>

        {{-- คำขอจองของฉัน (แสดงเฉพาะเมื่อมีข้อมูล) --}}
        @if(isset($myRequests) && $myRequests->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="font-medium text-gray-700 text-sm">คำขอจองเทรนเนอร์ของฉัน</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($myRequests as $r)
                        {{-- ดึงข้อมูลสีและข้อความตามสถานะ ถ้าไม่มีให้ fallback เป็นสีเทาแทน --}}
                        @php 
                            $sInfo = $statusMap[$r->status] ?? ['label' => $r->status, 'bg' => 'bg-gray-100', 'text' => 'text-gray-600']; 
                        @endphp
                        <div class="px-6 py-3 flex items-center justify-between flex-wrap gap-2">
                            <div class="text-sm text-gray-700">
                                <span class="font-medium">โค้ช {{ $r->coach->name }}</span>
                                <span class="text-gray-400 mx-1">•</span>
                                {{-- เรียก format() ได้เลย เพราะสมมติฐานว่าเราใส่ 'date' => 'date' ใน $casts ของ Model แล้ว --}}
                                {{ $r->date->format('d/m/Y') }}
                                <span class="text-gray-400 mx-1">•</span>
                                {{ substr($r->start_time, 0, 5) }} - {{ substr($r->end_time, 0, 5) }} น.
                            </div>
                            <div class="flex items-center gap-2">
                                {{-- Badge สถานะ --}}
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $sInfo['bg'] }} {{ $sInfo['text'] }}">
                                    {{ $sInfo['label'] }}
                                </span>
                                
                                {{-- ปุ่มยกเลิก จะแสดงเฉพาะเมื่อสถานะเป็น pending เท่านั้น --}}
                                @if($r->status === 'pending')
                                    <form method="POST" action="{{ route('private-training.cancel', $r) }}" class="cancel-form">
                                        @csrf
                                        <button type="button" class="btn-cancel-request text-xs text-red-500 hover:text-red-600 font-medium cursor-pointer transition">ยกเลิก</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- รายชื่อโค้ช --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($coaches as $coach)
                <a href="{{ route('private-training.show', $coach->id) }}"
                    class="bg-white rounded-2xl border border-gray-200 p-5 hover:border-orange-300 hover:shadow-md transition group block cursor-pointer">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0 border-2 border-white shadow-sm">
                            <span class="text-orange-600 text-xl font-bold">{{ mb_strtoupper(mb_substr($coach->name, 0, 1)) }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-800 truncate group-hover:text-orange-600 transition">{{ $coach->name }}</p>
                            <span class="inline-block mt-1 px-2 py-0.5 text-[11px] rounded-full font-medium bg-blue-100 text-blue-700">ผู้ฝึกสอน (Coach)</span>
                        </div>
                    </div>
                    <div class="mt-4 text-xs text-gray-500 space-y-1">
                        <p><span class="font-medium text-gray-600">ความเชี่ยวชาญ:</span> {{ $coach->staffProfile->specialty ?? 'ผู้ช่วยฝึกสอนเบสิค' }}</p>
                        <p class="line-clamp-2"><span class="font-medium text-gray-600">แนะนำตัว:</span> {{ $coach->staffProfile->bio ?? 'ไม่มีข้อมูล' }}</p>
                    </div>
                    <div class="mt-4 inline-flex items-center gap-1.5 text-xs font-medium text-orange-600">
                        ดูตารางว่างและจองเวลา
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </a>
            @empty
                <div class="col-span-full">
                    <div class="bg-white rounded-xl border border-gray-200 py-16 text-center shadow-sm">
                        <p class="text-gray-400 font-medium text-sm">ยังไม่มีข้อมูลโค้ชในระบบ</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // ตั้งค่าพื้นฐานสำหรับ Toast Notification (ใช้สำหรับแสดงข้อความสำเร็จ/ผิดพลาด)
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });

    document.addEventListener('DOMContentLoaded', function () {
        // ตรวจสอบ Flash Session และแสดง Toast แบบเดียวกับฝั่งแอดมิน
        @if (session()->has('success')) 
            Toast.fire({ icon: 'success', title: @js(session('success')) }); 
        @endif

        @if ($errors->any()) 
            Toast.fire({ icon: 'error', title: @js($errors->first()) }); 
        @endif

        // ดักจับการคลิกปุ่มยกเลิกคำขอจอง
        document.querySelectorAll('.btn-cancel-request').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const form = this.closest('form');
                
                // แสดง Popup ยืนยันก่อน Submit Form
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
                    // ถ้าผู้ใช้กด 'ใช่' ให้สั่ง Submit ฟอร์มที่ปุ่มนี้อยู่ข้างใน
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endsection