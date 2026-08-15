@extends('layouts.app')

@section('title', 'ข้อมูลบุคลากร: ' . $staff->name)

@php
    $startHour = 8;
    $endHour = 22;
    $today = now()->toDateString();
    $sortedCourts = $courts->sortBy('name', SORT_NATURAL);
    $allServices = collect($upcomingAvailabilities)->merge($pastServices ?? collect());

    $isCoach = $staff->membership_type === 'coach';
    $roleLabel = $isCoach ? 'ผู้ฝึกสอน (Coach)' : 'ผู้ช่วยสนาม (Staff)';
    $roleTitle = $isCoach ? 'Coach' : 'Staff';
    $badgeClass = $isCoach ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700';

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
    @include('private-training._calendar-theme')
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

        .slot-selected {
            border: 2px solid #3b82f6 !important;
            transform: scale(0.95);
            border-radius: 4px;
            z-index: 10;
        }

        /* overlay "เลยกำหนด" (สีเทา) ให้อยู่เหนือพื้นหลังปกติ แต่ต่ำกว่า event กำหนดการจริง */
        #staff-schedule-calendar .fc-bg-event {
            z-index: 1 !important;
        }
        #staff-schedule-calendar .fc-timegrid-event-harness {
            z-index: 3 !important;
        }
    </style>

    <div class="bg-slate-50 text-gray-900 min-h-screen py-8">
        <div class="container mx-auto px-4 sm:px-6 max-w-7xl">

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
                        class="w-20 h-20 rounded-full {{ $isCoach ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600' }} flex items-center justify-center flex-shrink-0 border-2 border-white shadow-sm overflow-hidden">
                        @if($staffProfile?->profile_image)
                            <img src="{{ $staffProfile->profile_image_url }}"
                                alt="รูปโปรไฟล์ของ {{ $staff->name }}" class="h-full w-full object-cover">
                        @else
                            <span class="text-3xl font-bold">{{ mb_strtoupper(mb_substr($staff->name, 0, 1)) }}</span>
                        @endif
                    </div>

                    <div class="flex-1">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <h1 class="text-2xl font-bold text-gray-800">{{ $staff->name }}</h1>
                                <span class="px-3 py-1 text-xs rounded-full font-medium {{ $badgeClass }}">
                                    {{ $roleLabel }}
                                </span>
                            </div>

                            <button type="button" onclick="toggleModal('staffProfileModal', true)"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-xs font-medium transition cursor-pointer">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                แก้ไขข้อมูลส่วนตัว
                            </button>
                        </div>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-y-3 gap-x-4 text-sm text-gray-600">
                            <p class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                อีเมล: {{ $staff->email }}
                            </p>
                            <p class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                เบอร์โทรศัพท์: {{ $staff->phone ?? 'ไม่ระบุ' }}
                            </p>

                            <p class="flex items-center gap-2 md:col-span-2">
                                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                เพศ:
                                {{ match($staffProfile?->gender) {
                                    'male' => 'ชาย',
                                    'female' => 'หญิง',
                                    default => 'ไม่ระบุ'
                                } }}
                            </p>

                            <p><strong>ความเชี่ยวชาญ:</strong> {{ $staffProfile?->specialty ?? 'ผู้ช่วยฝึกสอนเบสิค' }}</p>
                            <p><strong>แนะนำตัว (Bio):</strong> {{ $staffProfile?->bio ?? 'ไม่มีข้อมูล' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
                <div class="mb-5 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">{{ $roleTitle }} Schedule Calendar</h3>
                        <p class="text-xs text-gray-500 mt-0.5">ตารางงานของ {{ $staff->name }} แบบรายเดือน รายสัปดาห์ และรายวัน</p>
                    </div>
                    <a href="{{ route('admin.private-schedule.index', ['staff_id' => $staff->id]) }}"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                        จัดการ Schedule
                    </a>
                </div>

                <div class="mb-4 flex flex-wrap gap-4 text-xs text-gray-600">
                    <span class="flex items-center gap-1.5"><i class="h-3 w-3 rounded-sm bg-slate-500"></i>ไม่ว่าง</span>
                    @if($isCoach)
                        <span class="flex items-center gap-1.5"><i class="h-3 w-3 rounded-sm bg-orange-500"></i>คำขอ Private</span>
                        <span class="flex items-center gap-1.5"><i class="h-3 w-3 rounded-sm bg-violet-600"></i>ยืนยันแล้ว</span>
                    @endif
                </div>

                <div id="staff-schedule-calendar" class="private-calendar-theme"></div>
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
                    <p class="text-xs text-blue-500 font-semibold mb-1">วันที่และเวลาที่เลือก</p>
                    <p class="text-sm font-bold text-blue-700 mb-1" id="modal-court-name">สนาม X</p>
                    <div class="text-lg font-extrabold text-blue-700" id="modal-time-range">00:00 - 00:00 น.</div>
                </div>

                <input type="hidden" name="date" id="modal-date" value="{{ $today }}">
                <input type="hidden" name="court_id" id="modal-court-id">
                <input type="hidden" name="start_time" id="modal-time-start">
                <input type="hidden" name="end_time" id="modal-time-end">
                <input type="hidden" name="status" id="status-booked" value="booked">

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-2">สถานะ</label>
                    <div class="flex items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700">
                        <span class="mr-2 inline-block h-2.5 w-2.5 rounded-sm bg-slate-500"></span>
                        ช่วงเวลานี้ไม่ว่าง
                    </div>
                </div>

                <div id="detail-container" class="transition-all duration-300">
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">รายละเอียด / กิจกรรม (ถ้ามี)</label>
                    <input type="text" id="modal-detail-input" name="detail" placeholder="เก็บบาส, เคลียร์สนาม"
                        class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition text-gray-900 bg-white">
                </div>

                <div class="pt-2 flex gap-2">
                    <button type="button" onclick="toggleModal('dragActionModal', false)"
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
            class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[calc(100vh-2rem)] overflow-hidden transform border border-gray-100 flex flex-col mx-auto">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-800">แก้ไขข้อมูลโปรไฟล์</h3>
            </div>

            <form action="{{ route('admin.staffs.profile.update', $staff->id) }}" method="POST"
                enctype="multipart/form-data"
                class="grid grid-cols-1 gap-4 overflow-y-auto bg-white p-6 md:grid-cols-2">
                @csrf @method('PUT')

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">ชื่อ-นามสกุล <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $staff->name) }}" required
                        class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none text-gray-700">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">ตำแหน่ง (Role) <span
                            class="text-red-500">*</span></label>
                    <select name="membership_type" required
                        class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none text-gray-700 bg-white">
                        <option value="court_assistant" {{ old('membership_type', $staff->membership_type) === 'court_assistant' ? 'selected' : '' }}>ผู้ช่วยสนาม (Staff)</option>
                        <option value="coach" {{ old('membership_type', $staff->membership_type) === 'coach' ? 'selected' : '' }}>ผู้ฝึกสอน (Coach)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">อีเมล <span
                            class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $staff->email) }}" required
                        class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none text-gray-700">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" value="{{ old('phone', $staff->phone ?? '') }}" maxlength="10"
                        pattern="[0-9]{10}" title="กรุณากรอกเบอร์โทรศัพท์ตัวเลข 10 หลัก"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                        class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none text-gray-700">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">เพศ</label>
                    <select name="gender"
                        class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none text-gray-700 bg-white">
                        <option value="">ไม่ระบุ</option>
                        <option value="male" {{ old('gender', $staffProfile?->gender) === 'male' ? 'selected' : '' }}>ชาย
                        </option>
                        <option value="female" {{ old('gender', $staffProfile?->gender) === 'female' ? 'selected' : '' }}>หญิง</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">ความเชี่ยวชาญ</label>
                    <input type="text" name="specialty" value="{{ old('specialty', $staffProfile?->specialty ?? '') }}"
                        placeholder="เช่น ผู้ช่วยฝึกสอนเบสิค"
                        class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none text-gray-700">
                </div>

                <div class="md:col-span-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">แนะนำตัว (Bio)</label>
                    <textarea name="bio" rows="6" placeholder="เขียนคำแนะนำตัว..."
                        class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none resize-none text-gray-700">{{ old('bio', $staffProfile?->bio ?? '') }}</textarea>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">รูปโปรไฟล์</label>
                    <div id="staff-image-dropzone"
                        class="relative cursor-pointer overflow-hidden rounded-lg border-2 border-dashed border-slate-300 text-center transition-colors duration-150 hover:border-blue-400 hover:bg-blue-50/40">
                        <div id="staff-dropzone-empty"
                            class="px-6 py-6 {{ $staffProfile?->profile_image ? 'hidden' : '' }}">
                            <svg class="mx-auto mb-2 h-8 w-8 text-slate-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M12 12v9m0-9l-3 3m3-3l3 3" />
                            </svg>
                            <p class="text-sm text-slate-500"><span class="font-medium text-blue-600">คลิกเพื่อเลือกภาพ</span>
                                หรือลากไฟล์มาวางที่นี่</p>
                            <p class="mt-1 text-xs text-slate-400">JPG, PNG, WEBP ไม่เกิน 20MB</p>
                        </div>
                        <div id="staff-dropzone-preview"
                            class="relative {{ $staffProfile?->profile_image ? '' : 'hidden' }}">
                            <img id="staff-img-preview"
                                src="{{ $staffProfile?->profile_image_url ?? '' }}"
                                alt="ตัวอย่างรูปโปรไฟล์" class="h-36 w-full object-cover">
                            <div class="group absolute inset-0 flex items-center justify-center bg-black/0 transition hover:bg-black/40">
                                <span class="text-sm font-medium text-white opacity-0 transition group-hover:opacity-100">
                                    คลิกเพื่อเปลี่ยนภาพ
                                </span>
                            </div>
                        </div>
                        <input id="staff-profile-image" name="profile_image" type="file"
                            accept="image/png,image/jpeg,image/webp" class="hidden">
                    </div>
                    <p id="staff-image-error" class="hidden mt-1.5 text-xs text-red-600">
                        รองรับเฉพาะไฟล์ JPG, PNG, WEBP และต้องมีขนาดไม่เกิน 20MB
                    </p>
                    @error('profile_image')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <div class="mt-2 flex items-center gap-3">
                        <p id="staff-image-name"
                            class="text-xs text-slate-500 {{ $staffProfile?->profile_image ? '' : 'hidden' }}">
                            {{ $staffProfile?->profile_image ? 'ใช้รูปโปรไฟล์เดิม' : '' }}
                        </p>
                        <button type="button" id="remove-staff-image-btn"
                            class="{{ $staffProfile?->profile_image ? '' : 'hidden' }} rounded-lg border border-red-200 bg-white px-3 py-1 text-xs font-medium text-red-600 hover:bg-red-50">
                            ลบภาพ
                        </button>
                        <input type="hidden" name="remove_profile_image" id="remove-staff-image-input" value="0">
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-100 pt-4 md:col-span-2">
                    <button type="button" onclick="toggleModal('staffProfileModal', false)"
                        class="px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium">ยกเลิก</button>
                    <button type="submit"
                        class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-medium shadow-sm">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.21/locales-all.global.min.js"></script>
    <script>
        const staffScheduleOpenHour = 8, staffScheduleCloseHour = 22;

        function buildStaffPastOverlay(rangeStart, rangeEnd) {
            const overlay = [];
            const now = new Date();
            const cursor = new Date(rangeStart);
            cursor.setHours(0, 0, 0, 0);

            while (cursor < rangeEnd) {
                const dayOpen = new Date(cursor);
                dayOpen.setHours(staffScheduleOpenHour, 0, 0, 0);
                const dayClose = new Date(cursor);
                dayClose.setHours(staffScheduleCloseHour, 0, 0, 0);

                const overlayEnd = now < dayClose ? now : dayClose;

                if (overlayEnd > dayOpen) {
                    overlay.push({
                        start: dayOpen.toISOString(),
                        end: overlayEnd.toISOString(),
                        display: 'background',
                        backgroundColor: '#e5e7eb',
                        extendedProps: { kind: 'past' }
                    });
                }
                cursor.setDate(cursor.getDate() + 1);
            }
            return overlay;
        }
        const staffScheduleCalendar = new FullCalendar.Calendar(
            document.getElementById('staff-schedule-calendar'),
            {
                initialView: 'timeGridWeek',
                locale: 'th',
                firstDay: 1,
                height: 'auto',
                nowIndicator: true,
                selectable: @js(!$isCoach),
                selectMirror: true,
                allDaySlot: false,
                slotMinTime: '08:00:00',
                slotMaxTime: '22:00:00',
                slotDuration: '00:30:00',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                buttonText: {
                    today: 'วันนี้',
                    month: 'เดือน',
                    week: 'สัปดาห์',
                    day: 'วัน',
                    list: 'รายการ'
                },
                events(info, success, failure) {
                    const url = @js(route('admin.staffs.schedule-events', $staff));
                    fetch(`${url}?start=${encodeURIComponent(info.startStr)}&end=${encodeURIComponent(info.endStr)}`, {
                        headers: { Accept: 'application/json' }
                    })
                        .then(response => response.ok ? response.json() : Promise.reject(response))
                        .then(realEvents => {
                            const view = staffScheduleCalendar.view.type;
                            const isTimeGridView = view === 'timeGridWeek' || view === 'timeGridDay';
                            success(isTimeGridView ? [...realEvents, ...buildStaffPastOverlay(info.start, info.end)] : realEvents);
                        })
                        .catch(failure);
                },
                eventDidMount(arg) {
                    if (arg.event.extendedProps.kind !== 'past') return;

                    arg.el.style.backgroundColor = 'rgba(148, 163, 184, 0.65)';

                    const label = document.createElement('div');
                    label.textContent = 'เลยกำหนด';
                    label.style.cssText = `
                        position: absolute;
                        inset: 0;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 11px;
                        font-weight: 700;
                        color: #1e293b;
                        pointer-events: none;
                    `;
                    arg.el.appendChild(label);
                },
                select(info) {
                    @if($isCoach)
                        staffScheduleCalendar.unselect();
                        return;
                    @endif
                    const pad = value => String(value).padStart(2, '0');
                    const formatDate = date => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
                    const formatTime = date => `${pad(date.getHours())}:${pad(date.getMinutes())}`;
                    document.getElementById('modal-date').value = formatDate(info.start);
                    document.getElementById('modal-time-start').value = formatTime(info.start);
                    document.getElementById('modal-time-end').value = formatTime(info.end);
                    document.getElementById('modal-time-range').textContent =
                        `${formatTime(info.start)} - ${formatTime(info.end)} น.`;
                    document.getElementById('modal-court-name').textContent =
                        info.start.toLocaleDateString('th-TH', {weekday:'long', day:'numeric', month:'long', year:'numeric'});
                    document.getElementById('modal-court-id').value = '';
                    toggleDetailInput('booked');
                    toggleModal('dragActionModal', true);
                    staffScheduleCalendar.unselect();
                },
                eventClick(info) {
                    if (info.event.extendedProps.kind === 'past') {
                        Swal.fire({
                            icon: 'info',
                            title: 'เลยกำหนด',
                            text: 'ช่วงเวลานี้ผ่านไปแล้ว'
                        });
                        return;
                    }
                    const props = info.event.extendedProps;
                    Swal.fire({
                        icon: 'info',
                        title: info.event.title,
                        text: props.statusLabel || props.detail || 'กำหนดการของบุคลากร'
                    });
                },
            }
        );
        staffScheduleCalendar.render();

        function toggleModal(id, show) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.toggle('hidden', !show);
                modal.classList.toggle('flex', show);
                if (id === 'dragActionModal' && !show) clearSelection();
            }
        }

        const staffImageDropzone = document.getElementById('staff-image-dropzone');
        const staffDropzoneEmpty = document.getElementById('staff-dropzone-empty');
        const staffDropzonePreview = document.getElementById('staff-dropzone-preview');
        const staffImagePreview = document.getElementById('staff-img-preview');
        const staffImageInput = document.getElementById('staff-profile-image');
        const staffImageName = document.getElementById('staff-image-name');
        const removeStaffImageButton = document.getElementById('remove-staff-image-btn');
        const removeStaffImageInput = document.getElementById('remove-staff-image-input');
        const staffImageError = document.getElementById('staff-image-error');
        const staffAllowedImageTypes = ['image/png', 'image/jpeg', 'image/webp'];
        const staffImageMaxBytes = 20 * 1024 * 1024;
        const staffDragOverClasses = ['!border-blue-600', '!bg-blue-600/5'];

        staffImageDropzone.addEventListener('click', () => staffImageInput.click());

        function showStaffImageError(message) {
            staffImageError.textContent = message;
            staffImageError.classList.remove('hidden');
        }

        function showSelectedStaffImage(file) {
            staffImageError.classList.add('hidden');

            if (!staffAllowedImageTypes.includes(file.type)) {
                showStaffImageError('รองรับเฉพาะไฟล์ JPG, PNG, WEBP เท่านั้น');
                staffImageInput.value = '';
                return;
            }

            if (file.size > staffImageMaxBytes) {
                showStaffImageError('ไฟล์ต้องมีขนาดไม่เกิน 20MB');
                staffImageInput.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = event => {
                staffImagePreview.src = event.target.result;
                staffDropzoneEmpty.classList.add('hidden');
                staffDropzonePreview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);

            staffImageName.textContent = 'เลือกไฟล์: ' + file.name;
            staffImageName.classList.remove('hidden');
            removeStaffImageInput.value = '0';
            removeStaffImageButton.classList.remove('hidden');
        }

        staffImageInput.addEventListener('change', () => {
            if (staffImageInput.files?.[0]) showSelectedStaffImage(staffImageInput.files[0]);
        });

        removeStaffImageButton.addEventListener('click', event => {
            event.stopPropagation();
            staffImageInput.value = '';
            staffImagePreview.src = '';
            staffDropzoneEmpty.classList.remove('hidden');
            staffDropzonePreview.classList.add('hidden');
            staffImageName.classList.add('hidden');
            staffImageError.classList.add('hidden');
            removeStaffImageInput.value = '1';
            removeStaffImageButton.classList.add('hidden');
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            staffImageDropzone.addEventListener(eventName, event => {
                event.preventDefault();
                event.stopPropagation();
                staffImageDropzone.classList.add(...staffDragOverClasses);
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            staffImageDropzone.addEventListener(eventName, event => {
                event.preventDefault();
                event.stopPropagation();
                staffImageDropzone.classList.remove(...staffDragOverClasses);
            });
        });

        staffImageDropzone.addEventListener('drop', event => {
            const file = event.dataTransfer.files[0];
            if (!file) return;

            const transfer = new DataTransfer();
            transfer.items.add(file);
            staffImageInput.files = transfer.files;
            showSelectedStaffImage(file);
        });

        let isDragging = false;
        let selectedSlots = [];
        let currentDraggingCourt = null;
        const allSlots = document.querySelectorAll('.time-slot');
        const globalTooltip = document.getElementById('global-tooltip');

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

        function toggleDetailInput(status) {
            const container = document.getElementById('detail-container');
            const input = document.getElementById('modal-detail-input');
            const isBooked = status === 'booked';

            container.classList.toggle('hidden', !isBooked);
            input.disabled = !isBooked;
            if (!isBooked) input.value = '';
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

            slot.addEventListener('mousemove', function (e) {
                if (!globalTooltip) return;

                globalTooltip.style.left = (e.clientX + 15) + 'px';
                globalTooltip.style.top = (e.clientY + 15) + 'px';
                globalTooltip.style.visibility = 'visible';
                globalTooltip.style.opacity = '1';

                const detailText = this.getAttribute('data-detail');
                globalTooltip.querySelector('#tt-title').innerText = `${this.getAttribute('data-court-name')} (${this.getAttribute('data-status')})`;
                globalTooltip.querySelector('#tt-time').innerText = `เวลา: ${this.getAttribute('data-time-start')} - ${this.getAttribute('data-time-end')} น.`;
                globalTooltip.querySelector('#tt-detail').innerText = detailText ? `รายละเอียด: ${detailText}` : 'ไม่ระบุรายละเอียด';
            });

            slot.addEventListener('mouseleave', function () {
                if (!globalTooltip) return;
                globalTooltip.style.visibility = 'hidden';
                globalTooltip.style.opacity = '0';
            });
        });

        window.addEventListener('mouseup', function () {
            if (isDragging && selectedSlots.length > 0) {
                isDragging = false;
                selectedSlots.sort((a, b) => parseInt(a.getAttribute('data-hour')) - parseInt(b.getAttribute('data-hour')));
                const firstSlot = selectedSlots[0];
                const lastSlot = selectedSlots[selectedSlots.length - 1];

                document.getElementById('modal-court-name').innerText = firstSlot.getAttribute('data-court-name');
                document.getElementById('modal-time-range').innerText = `${firstSlot.getAttribute('data-time-start')} - ${lastSlot.getAttribute('data-time-end')} น.`;
                document.getElementById('modal-court-id').value = firstSlot.getAttribute('data-court-id');
                document.getElementById('modal-time-start').value = firstSlot.getAttribute('data-time-start');
                document.getElementById('modal-time-end').value = lastSlot.getAttribute('data-time-end');

                toggleDetailInput('booked');
                toggleModal('dragActionModal', true);
            }
        });

        document.addEventListener('click', function (e) {
            ['staffProfileModal', 'dragActionModal'].forEach(id => {
                const modal = document.getElementById(id);
                if (e.target === modal) toggleModal(id, false);
            });
        });

    </script>
@endsection
