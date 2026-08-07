@extends('layouts.app')

@section('title', 'จัดการเทรนเนอร์ส่วนตัว')

@php
    // Config ข้อมูล Status
    $statusMap = [
        'pending' => ['label' => 'รออนุมัติ', 'bg' => 'bg-orange-100', 'text' => 'text-orange-500', 'pill' => 'bg-orange-500'],
        'awaiting_court' => ['label' => 'รอจัดสนาม', 'bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'pill' => 'bg-purple-500'],
        'confirmed' => ['label' => 'ยืนยันแล้ว', 'bg' => 'bg-green-100', 'text' => 'text-green-600', 'pill' => 'bg-green-500'],
        'rejected' => ['label' => 'ปฏิเสธแล้ว', 'bg' => 'bg-red-100', 'text' => 'text-red-500', 'pill' => 'bg-red-500'],
        'cancelled' => ['label' => 'ยกเลิกแล้ว', 'bg' => 'bg-gray-100', 'text' => 'text-gray-500', 'pill' => 'bg-gray-500'],
        'expired' => ['label' => 'เลยกำหนด', 'bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'pill' => 'bg-gray-500'],
    ];

    // แสดง Tabs ด้านบน
    $tabs = [
        'pending' => 'รออนุมัติ',
        'awaiting_court' => 'รอจัดสนาม',
        'confirmed' => 'ยืนยันแล้ว',
        'expired' => 'เลยกำหนด',
        'rejected' => 'ปฏิเสธแล้ว',
        'all' => 'ทั้งหมด',
    ];
@endphp

@section('content')
    <div class="bg-slate-50 text-gray-900 min-h-screen py-8">
        <div class="container mx-auto px-6 max-w-6xl">

            {{-- 1. ดึง Loading Overlay Component มาใส่ --}}
            @include('components.mail-loading-overlay')

            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-800">จัดการเทรนเนอร์ส่วนตัว</h1>
                <p class="text-sm text-gray-500 mt-1">ตรวจสอบและอนุมัติคำขอจองเทรนเนอร์ส่วนตัวของลูกค้า</p>
            </div>

            {{-- ส่วนแสดง Tabs สถานะ --}}
            <div class="flex gap-2 -mb-px overflow-x-auto select-none border-b border-gray-200 mb-6">
                @foreach($tabs as $key => $label)
                    <a href="{{ route('admin.private-training.index', ['status' => $key]) }}"
                        class="px-5 py-2.5 text-sm font-medium border-b-2 transition-all cursor-pointer {{ $status === $key ? 'border-orange-500 text-orange-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="divide-y divide-gray-100">
                    @forelse($bookings as $b)
                        @php $effectiveStatus = $b->effective_status; $sInfo = $statusMap[$effectiveStatus] ?? $statusMap['pending']; @endphp
                        <div class="p-5">
                            <div class="flex justify-between items-start flex-wrap gap-3">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs px-2 py-0.5 border border-gray-300 rounded">โค้ช
                                            {{ $b->coach->name }}</span>
                                        <span
                                            class="text-xs px-2 py-0.5 {{ $sInfo['text'] }} {{ $sInfo['bg'] }} rounded flex items-center">
                                            <span class="w-1.5 h-1.5 rounded-full bg-current mr-1"></span>
                                            {{ $sInfo['label'] }}
                                        </span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-800">ลูกค้า: {{ $b->user->name }}
                                        ({{ $b->user->email }})</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        วันที่ {{ $b->date->format('d/m/Y') }}
                                        &nbsp;•&nbsp; เวลา {{ substr($b->start_time, 0, 5) }} - {{ substr($b->end_time, 0, 5) }}
                                        น.
                                    </p>
                                    @if($b->note)
                                        <p class="text-xs text-gray-500 mt-1">หมายเหตุ: {{ $b->note }}</p>
                                    @endif
                                    @if($b->reject_reason)
                                        <p class="text-xs text-red-500 mt-1">เหตุผลที่ปฏิเสธ: {{ $b->reject_reason }}</p>
                                    @endif
                                    @if($b->court)
                                        <p class="mt-1 text-xs font-medium text-green-700">
                                            สนาม: {{ $b->court->name }}{{ $b->courtSection ? ' — '.$b->courtSection->name : '' }}
                                        </p>
                                    @endif
                                </div>

                                @if($effectiveStatus === 'expired')
                                    <span class="rounded bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700">
                                        เลยกำหนดแล้ว
                                    </span>
                                @elseif($b->status === 'pending')
                                    <div class="flex gap-2">
                                        <button type="button"
                                            onclick="openRejectModal('{{ route('admin.private-training.reject', $b) }}')"
                                            class="bg-red-500 text-white text-xs px-3 py-1.5 rounded cursor-pointer transition duration-200 hover:scale-105 hover:bg-red-600">ปฏิเสธ</button>

                                        <form method="POST" action="{{ route('admin.private-training.approve', $b) }}">
                                            @csrf
                                            <button type="submit"
                                                class="bg-green-500 text-white text-xs px-3 py-1.5 rounded cursor-pointer transition duration-200 hover:scale-105 hover:bg-green-600">อนุมัติ</button>
                                        </form>
                                    </div>
                                @elseif($b->status === 'awaiting_court')
                                    <div class="flex gap-2">
                                        <button type="button"
                                            onclick="openRejectModal('{{ route('admin.private-training.reject', $b) }}')"
                                            class="rounded bg-red-500 px-3 py-1.5 text-xs text-white transition hover:bg-red-600 cursor-pointer">ปฏิเสธ</button>
                                        <button type="button"
                                            data-action="{{ route('admin.private-training.assign-court', $b) }}"
                                            data-date="{{ $b->date->format('d/m/Y') }}"
                                            data-time="{{ substr($b->start_time, 0, 5) }}–{{ substr($b->end_time, 0, 5) }}"
                                            onclick="openCourtModal(this)"
                                            class="rounded bg-purple-600 px-3 py-1.5 text-xs text-white transition hover:bg-purple-700 cursor-pointer">
                                            จัดสนาม
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-16 text-center">
                            <p class="text-gray-400 font-medium text-sm">ไม่พบคำขอจองเทรนเนอร์ส่วนตัวในหมวดหมู่นี้</p>
                        </div>
                    @endforelse
                </div>

                @if($bookings->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 bg-slate-50">
                        {{ $bookings->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal จัดสนาม --}}
    <div id="courtModal"
        class="fixed inset-0 z-[60] hidden items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm">
        <div class="w-full max-w-lg sm:max-w-xl overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-2xl">
            <div class="border-b border-gray-100 bg-gray-50 px-8 py-5">
                <h3 class="text-base sm:text-lg font-bold text-gray-800">จัดสนามสำหรับ Private Training</h3>
            </div>
            <form id="courtForm" method="POST" class="space-y-6 p-8"
                  onsubmit="showMailLoadingOverlay('กำลังดำเนินการจัดสนามและส่งอีเมลแจ้งลูกค้า...'); this.querySelector('button[type=submit]').disabled = true;">
                @csrf
                <div class="rounded-xl border border-purple-100 bg-purple-50/80 p-5 text-center">
                    <p id="courtBookingDate" class="text-base font-semibold text-purple-800 mb-1"></p>
                    <p id="courtBookingTime" class="text-xl sm:text-2xl font-bold text-purple-700"></p>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-700">เลือกสนามและส่วนสนาม</label>
                    <select name="court_section_id" required
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none">
                        <option value="">กรุณาเลือกสนาม</option>
                        @foreach($courts as $court)
                            <optgroup label="{{ $court->name }}">
                                @foreach($court->sections as $section)
                                    <option value="{{ $section->id }}">{{ $court->name }} — {{ $section->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs sm:text-sm text-gray-400">ระบบจะตรวจสอบเวลาชนอีกครั้งก่อนยืนยัน</p>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeCourtModal()"
                        class="w-1/2 rounded-xl bg-gray-100 px-5 py-3 text-base font-medium text-gray-600 hover:bg-gray-200 transition cursor-pointer">ยกเลิก</button>
                    <button type="submit"
                        class="w-1/2 rounded-xl bg-purple-600 px-5 py-3 text-base font-semibold text-white hover:bg-purple-700 transition cursor-pointer shadow-md">ยืนยันจัดสนาม</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal เหตุผลการปฏิเสธ --}}
    <div id="rejectModal"
        class="fixed inset-0 z-[60] hidden bg-gray-900/60 backdrop-blur-sm items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden border border-gray-100">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-800">ระบุเหตุผลที่ปฏิเสธ</h3>
            </div>
            <form id="rejectForm" method="POST" class="p-6 space-y-4"
                  onsubmit="showMailLoadingOverlay('กำลังดำเนินการปฏิเสธและส่งอีเมลแจ้งลูกค้า...'); this.querySelector('button[type=submit]').disabled = true;">
                @csrf
                <textarea name="reject_reason" rows="3" required placeholder="เช่น โค้ชไม่ว่างในช่วงเวลานี้"
                    class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none text-gray-900 bg-white resize-none"></textarea>
                <div class="flex gap-2">
                    <button type="button" onclick="closeRejectModal()"
                        class="w-1/2 px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition font-medium">ยกเลิก</button>
                    <button type="submit"
                        class="w-1/2 px-4 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded-lg transition font-medium shadow-sm">ยืนยันปฏิเสธ</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function openRejectModal(actionUrl) {
            const form = document.getElementById('rejectForm');
            form.setAttribute('action', actionUrl);

            const modal = document.getElementById('rejectModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeRejectModal() {
            const modal = document.getElementById('rejectModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openCourtModal(button) {
            document.getElementById('courtForm').action = button.dataset.action;
            document.getElementById('courtBookingDate').textContent = 'วันที่ ' + button.dataset.date;
            document.getElementById('courtBookingTime').textContent = button.dataset.time + ' น.';
            const modal = document.getElementById('courtModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeCourtModal() {
            const modal = document.getElementById('courtModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
@endsection
