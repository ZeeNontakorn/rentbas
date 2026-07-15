@extends('layouts.app')

@section('title', 'ข้อมูลบุคลากร: ' . $staff->name)

@php
    $startHour = 8;
    $endHour = 22;
    $today = now()->toDateString();
    $sortedCourts = $courts->sortBy('name', SORT_NATURAL);
    $allServices = collect($upcomingAvailabilities)->merge($pastServices ?? collect());

    $timelineGrid = [];
    foreach ($sortedCourts as $court) {
        $slots = [];
        for ($h = $startHour; $h < $endHour; $h++) {
            $timeStart = sprintf('%02d:00', $h);

            $record = $allServices->first(function ($item) use ($today, $timeStart, $court) {
                return $item->date === $today
                    && substr($item->start_time, 0, 5) === $timeStart
                    && (int) $item->court_id === (int) $court->id;
            });

            $slots[] = [
                'hour' => $h,
                'time_start' => $timeStart,
                'time_end' => sprintf('%02d:00', $h + 1),
                'status' => $record ? $record->status : 'available',
                'detail' => $record->detail ?? ''
            ];
        }
        $timelineGrid[] = [
            'court' => $court,
            'slots' => $slots
        ];
    }
@endphp

@section('content')
    <style>
        .tooltip-content {
            visibility: hidden;
            position: fixed;
            background-color: #1f2937;
            color: #ffffff;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            pointer-events: none;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.2s;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }

        .group:hover .tooltip-content {
            visibility: visible;
            opacity: 1;
        }

        .no-select {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .slot-selected {
            border: 2px solid #3b82f6 !important;
            transform: scale(0.95);
            border-radius: 4px;
            z-index: 10;
        }
    </style>

    <div class="bg-slate-50 text-gray-900 min-h-screen py-8">
        <div class="container mx-auto px-6 max-w-7xl">

            <a href="{{ route('admin.staffs.index') }}"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-orange-500 mb-6 transition font-medium group">
                <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                กลับไปหน้าจัดการผู้ช่วยสนาม
            </a>

            <div id="global-tooltip" class="tooltip-content">
                <div id="tt-title" class="font-bold border-b border-gray-700 pb-1 mb-1"></div>
                <div id="tt-time" class="text-xs text-blue-300"></div>
                <div id="tt-detail" class="text-xs text-gray-300 mt-1 pt-1 border-t border-gray-700/50"></div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-start gap-6">
                    <div
                        class="w-20 h-20 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0 border-2 border-white shadow-sm">
                        <span class="text-orange-600 text-3xl font-bold">{{ strtoupper(substr($staff->name, 0, 1)) }}</span>
                    </div>

                    <div class="flex-1">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <h1 class="text-2xl font-bold text-gray-800">{{ $staff->name }}</h1>
                                <span
                                    class="px-3 py-1 text-xs rounded-full font-medium {{ $staff->role === 'coach' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                    {{ $staff->role === 'coach' ? 'ผู้ฝึกสอน (Coach)' : 'ผู้ช่วยสนาม (Staff)' }}
                                </span>
                            </div>

                            <button type="button" onclick="openStaffProfileModal()"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-xs font-medium transition cursor-pointer">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                แก้ไขข้อมูลส่วนตัว
                            </button>
                        </div>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-y-3 text-sm text-gray-600">
                            <p class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                อีเมล: {{ $staff->email }}
                            </p>
                            <p class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                เบอร์โทรศัพท์: {{ $staff->phone ?? 'ไม่ระบุ' }}
                            </p>
                            <div class="col-span-1 md:col-span-2 mt-2">
                                <p><strong>ความเชี่ยวชาญ:</strong> {{ $staffProfile->specialty ?? 'ผู้ช่วยฝึกสอนเบสิค' }}
                                </p>
                                <p class="mt-1"><strong>แนะนำตัว (Bio):</strong> {{ $staffProfile->bio ?? 'ไม่มีข้อมูล' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6 no-select" id="timeline-container">
                <div class="mb-4">
                    <h3 class="font-bold text-gray-800 text-lg">Staff Availability Timeline</h3>
                    <p class="text-xs text-gray-400 mt-0.5">ตารางงานของ {{ $staff->name }} วันนี้ (08:00–22:00 น.)</p>
                </div>

                <div class="flex items-center justify-center gap-6 text-sm font-medium text-gray-700 mb-8 mt-2">
                    <div class="flex items-center gap-2"><span class="w-4 h-4 bg-[#10b981]"></span> ว่าง</div>
                    <div class="flex items-center gap-2"><span class="w-4 h-4 bg-[#f97316]"></span> ไม่ว่าง</div>
                </div>

                <div class="overflow-x-auto pb-8">
                    <div class="min-w-[900px]">
                        @foreach($timelineGrid as $row)
                            <div class="flex items-stretch w-full mb-[2px]">
                                <div class="w-16 shrink-0 flex items-center justify-end pr-3 text-xs font-medium text-gray-600">
                                    {{ $row['court']->name }}
                                </div>

                                <div class="flex-1 flex gap-[2px]">
                                    @foreach($row['slots'] as $slot)
                                        @php
                                            $isBooked = $slot['status'] === 'booked';
                                        @endphp
                                        <div class="flex-1 relative group">
                                            <div class="time-slot h-10 w-full {{ $isBooked ? 'bg-[#f97316]' : 'bg-[#10b981]' }} cursor-pointer hover:opacity-85 transition-all relative border-2 border-transparent"
                                                data-court-id="{{ $row['court']->id }}" data-court-name="{{ $row['court']->name }}"
                                                data-hour="{{ $slot['hour'] }}" data-time-start="{{ $slot['time_start'] }}"
                                                data-time-end="{{ $slot['time_end'] }}"
                                                data-status="{{ $isBooked ? 'ไม่ว่าง' : 'ว่าง' }}"
                                                data-detail="{{ $slot['detail'] }}">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <div class="flex items-center text-xs text-gray-500 mt-2">
                            <div class="w-16 shrink-0"></div>
                            <div class="flex-1 flex justify-between px-[2px]">
                                @foreach($timelineGrid[0]['slots'] as $slot)
                                    <div class="w-full text-left -ml-4">{{ $slot['time_start'] }}</div>
                                @endforeach
                                <div class="text-right -mr-4">22:00</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div id="dragActionModal"
        class="fixed inset-0 z-[60] hidden bg-gray-900/60 backdrop-blur-sm items-center justify-center p-4 transition-all">
        <div
            class="bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden transform border border-gray-100 flex flex-col mx-auto">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-800">จัดการสถานะการทำงาน</h3>
            </div>

            <form action="{{ route('admin.staffs.availabilities.store', $staff->id) }}" method="POST"
                class="p-6 space-y-4 bg-white">
                @csrf
                <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-center mb-2">
                    <p class="text-xs text-blue-500 font-semibold mb-1">ประจำอยู่ที่</p>
                    <p class="text-sm font-bold text-blue-700 mb-1" id="modal-court-name">สนาม X</p>
                    <div class="text-lg font-extrabold text-blue-700" id="modal-time-range">00:00 - 00:00 น.</div>
                </div>

                <input type="hidden" name="date" value="{{ $today }}">
                <input type="hidden" name="court_id" id="modal-court-id">
                <input type="hidden" name="start_time" id="modal-time-start">
                <input type="hidden" name="end_time" id="modal-time-end">

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-2">สถานะผู้ช่วยสนาม/โค้ช</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="status" value="available" id="status-available" class="peer sr-only"
                                required>
                            <div
                                class="text-center px-2 py-2 border border-gray-200 rounded-lg peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 transition hover:bg-gray-50 text-sm font-medium flex items-center justify-center">
                                <span class="w-2.5 h-2.5 inline-block bg-[#10b981] rounded-sm mr-1"></span>
                                <span class="text-gray-700">ว่าง</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="status" value="booked" id="status-booked" class="peer sr-only"
                                required>
                            <div
                                class="text-center px-2 py-2 border border-gray-200 rounded-lg peer-checked:border-orange-500 peer-checked:bg-orange-50 peer-checked:text-orange-700 transition hover:bg-gray-50 text-sm font-medium flex items-center justify-center">
                                <span class="w-2.5 h-2.5 inline-block bg-[#f97316] rounded-sm mr-1"></span>
                                <span class="text-gray-700">ไม่ว่าง</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div id="detail-container" class="transition-all duration-300">
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">รายละเอียด / กิจกรรม (ถ้ามี)</label>
                    <input type="text" id="modal-detail-input" name="detail" placeholder="เก็บบาส, เคลียร์สนาม"
                        class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition text-gray-900 bg-white">
                </div>

                <div class="pt-2 flex gap-2">
                    <button type="button" onclick="closeDragModal()"
                        class="w-1/2 px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition font-medium">ยกเลิก</button>
                    <button type="submit"
                        class="w-1/2 px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition font-medium shadow-sm">บันทึกสถานะ</button>
                </div>
            </form>
        </div>
    </div>

    <div id="staffProfileModal"
        class="fixed inset-0 z-50 hidden bg-gray-900/60 backdrop-blur-sm items-center justify-center p-4 transition-all">
        <div
            class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden transform border border-gray-100 flex flex-col mx-auto">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-800">แก้ไขข้อมูลโปรไฟล์</h3>
                <button type="button" onclick="closeStaffProfileModal()"
                    class="text-gray-400 hover:text-gray-600 transition text-xl font-bold leading-none">&times;</button>
            </div>
            <form action="{{ route('admin.staffs.profile.update', $staff->id) }}" method="POST"
                class="p-6 space-y-4 bg-white">
                @csrf @method('PUT')
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">ชื่อ-นามสกุล <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $staff->name) }}" required
                        class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" value="{{ old('phone', $staff->phone ?? '') }}"
                        class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div class="pt-4 flex justify-end gap-2 border-t border-gray-100 mt-6">
                    <button type="button" onclick="closeStaffProfileModal()"
                        class="px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium">ยกเลิก</button>
                    <button type="submit"
                        class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-medium shadow-sm">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function openStaffProfileModal() {
            document.getElementById('staffProfileModal').classList.replace('hidden', 'flex');
        }
        function closeStaffProfileModal() {
            document.getElementById('staffProfileModal').classList.replace('flex', 'hidden');
        }
            @if($errors->any()) openStaffProfileModal(); @endif

        // Drag & Select Timeline System
        let isDragging = false;
        let selectedSlots = [];
        let currentDraggingCourt = null;
        const allSlots = document.querySelectorAll('.time-slot');

        function clearSelection() {
            allSlots.forEach(s => s.classList.remove('slot-selected'));
            selectedSlots = [];
            currentDraggingCourt = null;
        }

        function addSlotToSelection(slotElement) {
            if (!selectedSlots.includes(slotElement)) {
                selectedSlots.push(slotElement);
                slotElement.classList.add('slot-selected');
            }
        }

        allSlots.forEach(slot => {
            slot.addEventListener('mousedown', function (e) {
                e.preventDefault();
                isDragging = true;
                clearSelection();
                currentDraggingCourt = this.getAttribute('data-court-id');
                addSlotToSelection(this);
            });

            slot.addEventListener('mouseenter', function () {
                if (isDragging && this.getAttribute('data-court-id') === currentDraggingCourt) {
                    addSlotToSelection(this);
                }
            });
        });

        window.addEventListener('mouseup', function () {
            if (isDragging) {
                isDragging = false;
                if (selectedSlots.length > 0) {
                    selectedSlots.sort((a, b) => parseInt(a.getAttribute('data-hour')) - parseInt(b.getAttribute('data-hour')));
                    const firstSlot = selectedSlots[0];
                    const lastSlot = selectedSlots[selectedSlots.length - 1];
                    openDragModal(
                        firstSlot.getAttribute('data-court-id'),
                        firstSlot.getAttribute('data-court-name'),
                        firstSlot.getAttribute('data-time-start'),
                        lastSlot.getAttribute('data-time-end')
                    );
                }
            }
        });

        function toggleDetailInput(status) {
            const container = document.getElementById('detail-container');
            const input = document.getElementById('modal-detail-input');
            const isBooked = status === 'booked';

            container.classList.toggle('hidden', !isBooked);
            input.disabled = !isBooked;
            if (!isBooked) input.value = '';
        }

        document.querySelectorAll('input[name="status"]').forEach(radio => {
            radio.addEventListener('change', function () {
                toggleDetailInput(this.value);
            });
        });

        function openDragModal(courtId, courtName, timeStart, timeEnd) {
            document.getElementById('modal-court-name').innerText = courtName;
            document.getElementById('modal-time-range').innerText = `${timeStart} - ${timeEnd} น.`;
            document.getElementById('modal-court-id').value = courtId;
            document.getElementById('modal-time-start').value = timeStart;
            document.getElementById('modal-time-end').value = timeEnd;

            const isBooked = selectedSlots[0].classList.contains('bg-[#f97316]');
            document.getElementById('status-booked').checked = isBooked;
            document.getElementById('status-available').checked = !isBooked;

            toggleDetailInput(isBooked ? 'booked' : 'available');
            document.getElementById('dragActionModal').classList.replace('hidden', 'flex');
        }

        function closeDragModal() {
            document.getElementById('dragActionModal').classList.replace('flex', 'hidden');
            clearSelection();
        }

        document.addEventListener('click', function (e) {
            const profileModal = document.getElementById('staffProfileModal');
            const dragModal = document.getElementById('dragActionModal');
            if (e.target === profileModal) closeStaffProfileModal();
            if (e.target === dragModal) closeDragModal();
        });

        // Hover Tooltip
        allSlots.forEach(slot => {
            slot.addEventListener('mousemove', function (e) {
                const tooltip = document.getElementById('global-tooltip');
                if (tooltip) {
                    tooltip.style.left = (e.clientX + 15) + 'px';
                    tooltip.style.top = (e.clientY + 15) + 'px';
                    tooltip.style.visibility = 'visible';
                    tooltip.style.opacity = '1';

                    const courtName = this.getAttribute('data-court-name');
                    const timeStart = this.getAttribute('data-time-start');
                    const timeEnd = this.getAttribute('data-time-end');
                    const statusText = this.getAttribute('data-status');
                    const detailText = this.getAttribute('data-detail');

                    tooltip.querySelector('#tt-title').innerText = `${courtName} (${statusText})`;
                    tooltip.querySelector('#tt-time').innerText = `เวลา: ${timeStart} - ${timeEnd} น.`;
                    tooltip.querySelector('#tt-detail').innerText = detailText ? `รายละเอียด: ${detailText}` : 'ไม่มีรายละเอียด/งานว่าง';
                }
            });

            slot.addEventListener('mouseleave', function () {
                const tooltip = document.getElementById('global-tooltip');
                if (tooltip) {
                    tooltip.style.visibility = 'hidden';
                    tooltip.style.opacity = '0';
                }
            });
        });

        // SweetAlert2 Toast Notifications
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            @if (session()->has('success'))
                Toast.fire({ icon: 'success', title: @js(session('success')) });
            @endif

            @if (session()->has('error'))
                Toast.fire({ icon: 'error', title: @js(session('error')) });
            @endif

            @if ($errors->any())
                Toast.fire({ icon: 'error', title: @js($errors->first()) });
            @endif
            });
    </script>
@endsection