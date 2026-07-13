@extends('layouts.app')

@section('title', 'จัดการการจอง')

@php
    $statusMap = [
        'pending' => ['label' => 'รออนุมัติ', 'color' => 'orange', 'bg' => 'bg-orange-100', 'text' => 'text-orange-500'],
        'approved' => ['label' => 'อนุมัติแล้ว', 'color' => 'green', 'bg' => 'bg-green-100', 'text' => 'text-green-500'],
        'rejected' => ['label' => 'ปฏิเสธแล้ว', 'color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-500'],
        'cancelled' => ['label' => 'ยกเลิก', 'color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-500'],
    ];

    $selectedBookingId = request('selected_booking_id');
    $selectedBooking = $selectedBookingId ? $bookings->firstWhere('id', $selectedBookingId) : $bookings->first();
@endphp

@section('content')
    <div class="bg-slate-50 text-gray-900 min-h-screen py-8">
        <div class="container mx-auto px-6 max-w-7xl">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-semibold text-gray-800">จัดการการจอง</h1>
                <p class="text-sm text-gray-500">ดูสถิติ จัดการสนาม และดูการจองทั้งหมด</p>
            </div>

            <!-- Summary Cards -->
            @include('admin.partials.summary_cards')

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- Column 1: Date/Court filter & Bookings List -->
                <div class="lg:col-span-4 flex flex-col gap-4">

                    <!-- Filters -->
                    <form method="GET" action="{{ route('admin.bookings') }}" class="flex gap-4">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <div class="flex-1">
                            <label class="text-xs text-blue-500 font-medium ml-2">Booking Date</label>
                            <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                                class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:border-blue-500 outline-none bg-white">
                            <div class="text-[10px] text-gray-400 mt-1 ml-2">MM/DD/YYYY</div>
                        </div>
                        <div class="flex-1">
                            <label class="text-xs text-blue-500 font-medium ml-2">สนาม</label>
                            <select name="court_id" onchange="this.form.submit()"
                                class="w-full border border-gray-300 rounded-lg py-2.5 px-3 text-sm appearance-none bg-white">
                                <option value="">ทุกสนาม</option>
                                @foreach($courts as $court)
                                    <option value="{{ $court->id }}" @selected($court_id == $court->id)>{{ $court->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="text-[10px] mt-1 ml-2 invisible">x</div>
                        </div>
                    </form>

                    <!-- Bookings List -->
                    <div>
                        <h3 class="font-medium text-sm mb-3">
                            @if($status === 'pending')
                                คำขอจอง (รอดำเนินการ)
                            @elseif($status === 'approved')
                                การจองที่อนุมัติแล้ว (ใช้งานอยู่)
                            @elseif($status === 'cancelled')
                                การจองที่ถูกยกเลิก/ปฏิเสธ
                            @else
                                รายการจองทั้งหมด
                            @endif
                        </h3>

                        {{-- ===================== Bulk Action Toolbar ===================== --}}
                        @php $hasPendingInList = $bookings->contains('status', 'pending'); @endphp
                        @if($hasPendingInList)
                            <div
                                class="flex items-center justify-between bg-white border border-gray-200 rounded-xl px-3 py-2 mb-3">
                                <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer select-none">
                                    <input type="checkbox" id="selectAllPending"
                                        class="w-4 h-4 rounded border-gray-300 text-orange-500 focus:ring-orange-400">
                                    เลือกทั้งหมด
                                    <span id="selectedCount" class="text-gray-400">(0)</span>
                                </label>
                                <div id="bulkActionButtons" class="hidden gap-2">
                                    <button type="button" onclick="bulkReject()"
                                        class="bg-red-500 hover:bg-red-600 text-white text-xs px-3 py-1.5 rounded transition">
                                        ปฏิเสธที่เลือก
                                    </button>
                                    <button type="button" onclick="bulkApprove()"
                                        class="bg-green-500 hover:bg-green-600 text-white text-xs px-3 py-1.5 rounded transition">
                                        อนุมัติที่เลือก
                                    </button>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2">
                            @forelse($bookings as $b)
                                @php $sInfo = $statusMap[$b->status] ?? $statusMap['pending']; @endphp
                                <a href="{{ request()->fullUrlWithQuery(['selected_booking_id' => $b->id]) }}"
                                    class="block bg-white border {{ $selectedBooking?->id === $b->id ? 'border-orange-500 shadow-md' : 'border-gray-200' }} rounded-xl p-4 hover:border-orange-300 transition">

                                    <div class="flex justify-between items-center mb-2">
                                        <div class="flex items-center gap-2">
                                            @if($b->status === 'pending')
                                                <input type="checkbox"
                                                    class="booking-checkbox w-4 h-4 rounded-md border-gray-300 text-orange-500 focus:ring-orange-400 focus:ring-2 cursor-pointer"
                                                    value="{{ $b->id }}" onclick="event.stopPropagation()"
                                                    onchange="updateBulkToolbar()">
                                            @endif
                                            <span
                                                class="text-xs px-2 py-0.5 border border-gray-300 rounded">{{ $b->court->name }}</span>
                                            <span
                                                class="text-xs px-2 py-0.5 {{ $sInfo['text'] }} {{ $sInfo['bg'] }} rounded flex items-center">
                                                <span class="w-1.5 h-1.5 rounded-full bg-current mr-1"></span>
                                                {{ $sInfo['label'] }}
                                            </span>
                                        </div>
                                        @if($b->status === 'pending')
                                            <form method="POST" action="{{ route('admin.bookings.approve', $b) }}"
                                                class="flex gap-1 m-0 p-0" onclick="event.stopPropagation()"
                                                onsubmit="showProcessingToast()">
                                                @csrf
                                                <button type="button"
                                                    onclick="openRejectModal(event, '{{ route('admin.bookings.reject', $b) }}')"
                                                    class="bg-red-500 text-white text-xs px-3 py-1 rounded cursor-pointer transition duration-200 hover:scale-105 hover:bg-red-600">ปฏิเสธ
                                                </button>

                                                <button type="submit"
                                                    class="bg-green-500 text-white text-xs px-3 py-1 rounded cursor-pointer transition duration-200 hover:scale-105 hover:bg-green-600">อนุมัติ
                                                </button>
                                            </form>
                                        @else
                                            <!-- If not pending, it shows status like 'ยกเลิก' in red pill in Figma -->
                                            <div class="bg-{{ $sInfo['color'] }}-500 text-white text-xs px-3 py-1 rounded">
                                                {{ $sInfo['label'] }}
                                            </div>
                                        @endif
                                    </div>

                                    <div
                                        class="grid grid-cols-2 text-xs text-gray-600 mt-4 border-t border-gray-100 pt-3 gap-2">
                                        <div>
                                            <div class="text-gray-400 mb-1">วัน / เวลา : ที่จอง</div>
                                            <div class="font-medium text-gray-800">
                                                {{ $b->booking_date->translatedFormat('d F Y') }}
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-gray-400 mb-1">&nbsp;</div> <!-- spacing -->
                                            <div class="font-medium text-gray-800">{{ substr($b->start_time, 0, 5) }} -
                                                {{ substr($b->end_time, 0, 5) }}
                                            </div>
                                        </div>

                                        <div class="mt-2">
                                            <div class="text-gray-400 mb-1">Email / ชื่อผู้ใช้ :</div>
                                            <div class="font-medium text-gray-800 truncate">{{ $b->user->email }}</div>
                                        </div>
                                        <div class="mt-2 text-right">
                                            <div class="text-gray-400 mb-1">&nbsp;</div>
                                            <div class="font-medium text-gray-800">{{ $b->user->name }}</div>
                                        </div>

                                        <div class="col-span-2 mt-2">
                                            <div class="text-gray-400">วันที่ดำเนินการ :
                                                {{ $b->created_at->translatedFormat('d F Y') }} <span
                                                    class="float-right">{{ $b->created_at->format('H:i:s') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="text-center py-10 text-gray-400 bg-white rounded-xl border border-gray-200">
                                    ไม่มีรายการจอง</div>
                            @endforelse
                        </div>
                        <div class="mt-4">
                            {{ $bookings->links() }}
                        </div>
                    </div>
                </div>

                <!-- Column 2: Booking Details -->
                <div class="lg:col-span-8">
                    @if($selectedBooking)
                        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 sticky top-4">
                            <!-- Title -->
                            <div class="flex items-center justify-between mb-5 border-b border-gray-100 pb-3">
                                <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                                    <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    รายละเอียดการจอง
                                </h3>
                                <span
                                    class="text-[10px] bg-gray-100 text-gray-600 px-2 py-1 rounded font-bold tracking-wider">#{{ str_pad($selectedBooking->id, 4, '0', STR_PAD_LEFT) }}</span>
                            </div>

                            <!-- User Informaion (Secondary Data) -->
                            <div class="space-y-3 text-xs mb-5 px-1">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500 font-medium">ผู้จอง</span>
                                    <span class="text-gray-900 font-semibold">{{ $selectedBooking->user->name }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500 font-medium">อีเมล</span>
                                    <span class="text-gray-800">{{ $selectedBooking->user->email }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500 font-medium">ติดต่อ</span>
                                    <span class="text-gray-800">{{ $selectedBooking->user->phone ?? '081-xxx-xxxx' }}</span>
                                </div>
                            </div>

                            <!-- Core Booking Data (Primary Data Highlight) -->
                            <div class="bg-orange-50/50 border border-orange-100 rounded-lg p-4 mb-6 space-y-3">
                                <div>
                                    <div class="text-orange-600/80 font-medium mb-1 text-[11px] uppercase tracking-wide">
                                        สนามที่จอง</div>
                                    <div class="text-orange-950 font-semibold text-base">{{ $selectedBooking->court->name }}
                                    </div>
                                </div>
                                <div class="h-px w-full bg-orange-100"></div>
                                <div>
                                    <div class="text-orange-600/80 font-medium mb-1 text-[11px] uppercase tracking-wide">
                                        เวลาระบุ</div>
                                    <div class="text-orange-950 font-semibold text-base">
                                        {{ substr($selectedBooking->start_time, 0, 5) }} -
                                        {{ substr($selectedBooking->end_time, 0, 5) }}
                                    </div>
                                    <div class="text-orange-800/80 mt-1 text-xs">
                                        {{ $selectedBooking->booking_date->translatedFormat('d M Y') }}
                                    </div>
                                </div>
                            </div>

                            <!-- User Behavior Context -->
                            <h3 class="font-medium text-gray-700 text-xs mb-3 border-b border-gray-100 pb-2">
                                ความน่าเชื่อถือของผู้ใช้</h3>
                            <div class="space-y-2.5 text-xs bg-gray-50 rounded-lg p-3 border border-gray-100">
                                @php
                                    $userStats = [
                                        'success' => \App\Models\Booking::where('user_id', $selectedBooking->user_id)->where('status', 'approved')->count(),
                                        'self_cancel' => \App\Models\Booking::where('user_id', $selectedBooking->user_id)->where('status', 'cancelled')->count(),
                                        'admin_reject' => \App\Models\Booking::where('user_id', $selectedBooking->user_id)->where('status', 'rejected')->count(),
                                    ];
                                @endphp
                                <div class="flex justify-between items-center">
                                    <div class="text-gray-600">ใช้งานสำเร็จ</div>
                                    <div class="font-bold text-green-600">{{ $userStats['success'] }}</div>
                                </div>
                                <div class="flex justify-between items-center">
                                    <div class="text-gray-600">ยกเลิกรายการด้วยตัวเอง</div>
                                    <div
                                        class="font-bold {{ $userStats['self_cancel'] > 2 ? 'text-orange-500' : 'text-gray-700' }}">
                                        {{ $userStats['self_cancel'] }}
                                    </div>
                                </div>
                                <div class="flex justify-between items-center">
                                    <div class="text-gray-600">ถูกปฏิเสธคำขอจอง</div>
                                    <div
                                        class="font-bold {{ $userStats['admin_reject'] > 0 ? 'text-red-500' : 'text-gray-700' }}">
                                        {{ $userStats['admin_reject'] }}
                                    </div>
                                </div>
                            </div>

                            @if($selectedBooking->status === 'pending')
                                <div class="mt-6 flex gap-3">
                                    <button type="button"
                                        onclick="openRejectModal(event, '{{ route('admin.bookings.reject', $selectedBooking) }}')"
                                        class="flex-1 w-full flex items-center justify-center gap-1 bg-white border-2 border-red-100 text-red-500 px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-50 hover:border-red-200 transition cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        ปฏิเสธ
                                    </button>
                                    <form method="POST" action="{{ route('admin.bookings.approve', $selectedBooking) }}"
                                        class="flex-1" onsubmit="showProcessingToast()">
                                        @csrf
                                        <button
                                            class="w-full flex items-center justify-center gap-1 bg-green-500 border-2 border-green-500 text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-green-600 hover:border-green-600 transition shadow-sm shadow-green-200 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            อนุมัติ
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @else
                        <div
                            class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 text-center text-gray-400 text-sm italic">
                            คลิกที่รายการจองเพื่อดูรายละเอียด
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- ===================== Modal: ปฏิเสธรายการเดียว (เดิม) ===================== --}}
    <div id="rejectModal"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6 mx-4 transform transition-all">
            <h2 class="text-lg font-bold text-gray-800 mb-2">ระบุเหตุผลการปฏิเสธ</h2>
            <p class="text-xs text-gray-500 mb-4">ข้อความนี้จะถูกบันทึกและแสดงให้ผู้จองสนามเห็น</p>

            <form id="rejectForm" method="POST" action="" onsubmit="showProcessingToast()">
                @csrf
                <textarea name="reject_reason" id="rejectReasonInput" rows="3"
                    class="w-full border border-gray-300 rounded-lg p-3 text-sm text-gray-900  bg-gray-50"
                    placeholder="เช่น สนามปิดปรับปรุง, รายละเอียดไม่ครบถ้วน..." required></textarea>

                <div class="flex justify-end gap-3 mt-5">
                    <button type="button" onclick="closeRejectModal()"
                        class="px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition cursor-pointer">
                        ยกเลิก
                    </button>
                    <button type="submit"
                        class="px-4 py-2.5 bg-red-500 text-white rounded-lg text-sm font-semibold hover:bg-red-600 transition shadow-sm shadow-red-200 cursor-pointer">
                        ยืนยันการปฏิเสธ
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== Modal: ปฏิเสธหลายรายการพร้อมกัน (ใหม่) ===================== --}}
    <div id="bulkRejectModal"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6 mx-4 transform transition-all">
            <h2 class="text-lg font-bold text-gray-800 mb-2">ปฏิเสธรายการที่เลือก
                <span id="bulkRejectCount" class="text-red-500"></span> รายการ
            </h2>
            <p class="text-xs text-gray-500 mb-4">ข้อความนี้จะถูกบันทึกและแสดงให้ผู้จองทุกคนที่เลือกเห็นเหมือนกัน</p>

            <textarea id="bulkRejectReasonInput" rows="3"
                class="w-full border border-gray-300 rounded-lg p-3 text-sm text-gray-900 bg-gray-50"
                placeholder="เช่น สนามปิดปรับปรุง, รายละเอียดไม่ครบถ้วน..." required></textarea>

            <div class="flex justify-end gap-3 mt-5">
                <button type="button" onclick="closeBulkRejectModal()"
                    class="px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition cursor-pointer">
                    ยกเลิก
                </button>
                <button type="button" onclick="submitBulkReject()"
                    class="px-4 py-2.5 bg-red-500 text-white rounded-lg text-sm font-semibold hover:bg-red-600 transition shadow-sm shadow-red-200 cursor-pointer">
                    ยืนยันปฏิเสธทั้งหมด
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // 1. สร้าง Toast Instance ก่อนอันดับแรก (เพื่อไม่ให้ฟังก์ชันอื่นหาไม่เจอ)
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });

        // 2. ฟังก์ชันแสดง Loading State (ทำงานเมื่อกด Submit Form)
        function showProcessingToast() {
            Toast.fire({
                icon: 'info',
                title: 'กำลังดำเนินการ กรุณารอซักครู่...'
            });
        }

        // 3. ฟังก์ชัน Modal ปฏิเสธ (รายการเดียว)
        function openRejectModal(event, actionUrl) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            document.getElementById('rejectForm').action = actionUrl;
            document.getElementById('rejectReasonInput').value = '';
            document.getElementById('rejectModal').classList.remove('hidden');
            setTimeout(() => document.getElementById('rejectReasonInput').focus(), 100);
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }

        // ===================== 4. Bulk Actions (อนุมัติ/ปฏิเสธหลายรายการ) =====================

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
            || '{{ csrf_token() }}';

        function getSelectedIds() {
            return Array.from(document.querySelectorAll('.booking-checkbox:checked'))
                .map(cb => cb.value);
        }

        // อัปเดตสถานะแถบ toolbar (จำนวนที่เลือก / โชว์-ซ่อนปุ่ม)
        function updateBulkToolbar() {
            const ids = getSelectedIds();
            const countEl = document.getElementById('selectedCount');
            const buttonsEl = document.getElementById('bulkActionButtons');
            if (countEl) countEl.textContent = `(${ids.length})`;
            if (buttonsEl) buttonsEl.classList.toggle('hidden', ids.length === 0);
            buttonsEl?.classList.toggle('flex', ids.length > 0);

            // sync checkbox "เลือกทั้งหมด"
            const all = document.querySelectorAll('.booking-checkbox');
            const selectAll = document.getElementById('selectAllPending');
            if (selectAll) selectAll.checked = all.length > 0 && ids.length === all.length;
        }

        document.getElementById('selectAllPending')?.addEventListener('change', function () {
            document.querySelectorAll('.booking-checkbox').forEach(cb => cb.checked = this.checked);
            updateBulkToolbar();
        });

        // --- อนุมัติที่เลือกทั้งหมด ---
        function bulkApprove() {
            const ids = getSelectedIds();
            if (ids.length === 0) return;

            Swal.fire({
                title: `ยืนยันอนุมัติ ${ids.length} รายการ?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'อนุมัติทั้งหมด',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#22c55e',
            }).then((result) => {
                if (!result.isConfirmed) return;

                showProcessingToast();

                fetch('{{ route('admin.bookings.bulkApprove') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ booking_ids: ids }),
                }).then(res => {
                    if (!res.ok) throw new Error('failed');
                    window.location.reload();
                }).catch(() => {
                    Toast.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด กรุณาลองใหม่' });
                });
            });
        }

        // --- ปฏิเสธที่เลือกทั้งหมด: เปิด modal ให้กรอกเหตุผล ---
        function bulkReject() {
            const ids = getSelectedIds();
            if (ids.length === 0) return;

            document.getElementById('bulkRejectCount').textContent = ids.length;
            document.getElementById('bulkRejectReasonInput').value = '';
            document.getElementById('bulkRejectModal').classList.remove('hidden');
            setTimeout(() => document.getElementById('bulkRejectReasonInput').focus(), 100);
        }

        function closeBulkRejectModal() {
            document.getElementById('bulkRejectModal').classList.add('hidden');
        }

        function submitBulkReject() {
            const ids = getSelectedIds();
            const reason = document.getElementById('bulkRejectReasonInput').value.trim();

            if (!reason) {
                document.getElementById('bulkRejectReasonInput').focus();
                return;
            }

            closeBulkRejectModal();
            showProcessingToast();

            fetch('{{ route('admin.bookings.bulkReject') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ booking_ids: ids, reject_reason: reason }),
            }).then(res => {
                if (!res.ok) throw new Error('failed');
                window.location.reload();
            }).catch(() => {
                Toast.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด กรุณาลองใหม่' });
            });
        }

        // 5. ดักจับ Flash Session เมื่อโหลดหน้าเว็บเสร็จ
        document.addEventListener('DOMContentLoaded', function () {

            // จัดการ Pending Toast (ถ้ามี)
            try {
                const pending = sessionStorage.getItem('pendingToast');
                if (pending) {
                    sessionStorage.removeItem('pendingToast');
                    Toast.fire(JSON.parse(pending));
                }
            } catch (e) { }

            // ดักจับจาก Controller: return back()->with('success', '...')
            @if (session()->has('success'))
                Toast.fire({
                    icon: 'success',
                    title: @js(session('success'))
                });
            @endif

            // ดักจับจาก Controller: return back()->with('error', '...')
            @if (session()->has('error'))
                Toast.fire({
                    icon: 'error',
                    title: @js(session('error'))
                });
            @endif

            // ดักจับจาก Controller: return back()->withErrors([...])
            @if ($errors->any())
                Toast.fire({
                    icon: 'error',
                    title: @js($errors->first())
                });
            @endif
        });
    </script>
@endsection