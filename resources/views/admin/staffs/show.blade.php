@extends('layouts.app')

@section('title', 'ข้อมูลบุคลากร: ' . $staff->us_name)

@php
    $startHour = 8;
    $endHour = 22;
    $today = now()->toDateString();
    $sortedCourts = $courts->sortBy('name', SORT_NATURAL);
    $allServices = collect($upcomingAvailabilities)->merge($pastServices ?? collect());

    // ─── ตั้งค่าคลาสสีตาม Role เพื่อแก้ปัญหา Tailwind Purge/JIT ───
    $isCoach = $staff->membership_type === 'coach';
    $roleTitle = $isCoach ? 'โค้ช' : 'ผู้ช่วยสนาม';
    
    $badgeClass = $isCoach ? 'bg-blue-100 text-blue-700 border-blue-200' : 'bg-purple-100 text-purple-700 border-purple-200';
    $cardBorderClass = $isCoach ? 'border-t-blue-500' : 'border-t-purple-500';
    $avatarBgClass = $isCoach ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600';
    $dotClass = $isCoach ? 'bg-blue-500' : 'bg-purple-500';
    $breadcrumbHoverClass = $isCoach ? 'hover:text-blue-500' : 'hover:text-purple-500';

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

{{-- ─── INCLUDE SWEETALERT & STYLES ─── --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');
.pf-edit { font-family:'Sarabun','Kanit',sans-serif; }
.pf-edit h1, .pf-edit h2, .pf-edit h3, .pf-edit .font-kanit { font-family:'Kanit',sans-serif; }

.tooltip-content {
    visibility: hidden;
    position: fixed;
    background-color: #1f2937;
    color: #ffffff;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-family: 'Sarabun', sans-serif;
    pointer-events: none;
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.2s;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
}
.group:hover .tooltip-content { visibility: visible; opacity: 1; }
.slot-selected {
    border: 2px solid #3b82f6 !important;
    transform: scale(0.95);
    border-radius: 4px;
    z-index: 10;
}
#staff-schedule-calendar .fc-bg-event { z-index: 1 !important; }
#staff-schedule-calendar .fc-timegrid-event-harness { z-index: 3 !important; }
</style>

@if ($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const msgs = {!! json_encode($errors->all()) !!};
        Swal.fire({ icon:'error', title:'กรุณาตรวจสอบข้อมูล', html: msgs.join('<br>'), confirmButtonText:'ตกลง', confirmButtonColor:'#3b82f6' });
        if(typeof toggleModal === 'function') toggleModal('staffProfileModal', true);
    });
</script>
@endif

@if (session('success'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        Swal.fire({ icon:'success', title:'สำเร็จ!', text: '{{ session('success') }}', confirmButtonText:'ตกลง', confirmButtonColor:'#3b82f6' });
    });
</script>
@endif

@section('content')
@include('private-training._calendar-theme')

<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-4 sm:px-6 max-w-7xl">

        {{-- Breadcrumb --}}
        <a href="{{ route('admin.staffs.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 {{ $breadcrumbHoverClass }} mb-6 transition font-medium group">
            <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            กลับไปหน้าจัดการบุคลากร
        </a>

        <!-- Tooltip สำหรับ Schedule -->
        <div id="global-tooltip" class="tooltip-content">
            <div id="tt-title" class="font-bold border-b border-gray-600 pb-1 mb-1"></div>
            <div id="tt-time" class="text-xs text-blue-300"></div>
            <div id="tt-detail" class="text-xs text-gray-300 mt-1 pt-1 border-t border-gray-600/50"></div>
        </div>

        {{-- Staff Profile Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6 border-t-4 {{ $cardBorderClass }}">
            <div class="flex flex-col sm:flex-row sm:items-start gap-5">
                
                {{-- Avatar --}}
                <div class="w-20 h-20 rounded-full {{ $avatarBgClass }} flex items-center justify-center flex-shrink-0 border-2 border-white shadow-sm overflow-hidden">
                    @if($staffProfile?->profile_image)
                        <img src="{{ $staffProfile->profile_image_url }}" alt="{{ $staff->us_name }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-3xl font-bold">{{ mb_strtoupper(mb_substr($staff->us_name, 0, 1)) }}</span>
                    @endif
                </div>
                
                <div class="flex-1 w-full">
                    {{-- แถวบน: ชื่อ, สถานะ และ ปุ่มจัดการ --}}
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div class="flex items-center flex-wrap gap-3">
                            <h1 class="text-xl font-bold text-gray-800">{{ $staff->us_name }}</h1>
                            
                            {{-- บทบาท (Role) Pill --}}
                            <span class="inline-flex items-center text-sm font-semibold px-3 py-1 rounded-full border {{ $badgeClass }}">
                                {{ $roleTitle }}
                            </span>
                        </div>

                        {{-- Quick Actions --}}
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <button type="button" onclick="toggleModal('staffProfileModal', true)" class="flex items-center gap-1.5 bg-gray-100 text-gray-700 border border-gray-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                แก้ไขข้อมูลบุคลากร
                            </button>
                        </div>
                    </div>

                    {{-- รายละเอียดผู้ใช้ (Grid) --}}
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                            <div class="text-[11px] font-bold uppercase tracking-[0.08em] text-gray-400">เบอร์โทรศัพท์</div>
                            <div class="mt-1 flex items-center gap-2 font-semibold text-gray-700">
                                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                {{ $staff->phone ?? 'ไม่ได้ระบุ' }}
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                            <div class="text-[11px] font-bold uppercase tracking-[0.08em] text-gray-400">เพศ</div>
                            <div class="mt-1 font-semibold text-gray-700">
                                {{ match($staffProfile?->gender) { 'male' => 'ชาย', 'female' => 'หญิง', default => 'ไม่ระบุ' } }}
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                            <div class="text-[11px] font-bold uppercase tracking-[0.08em] text-gray-400">ความเชี่ยวชาญ</div>
                            <div class="mt-1 font-semibold text-gray-700">
                                {{ $staffProfile?->specialty ?? 'เบสิค / ทั่วไป' }}
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 md:col-span-3">
                            <div class="text-[11px] font-bold uppercase tracking-[0.08em] text-gray-400">แนะนำตัว (Bio)</div>
                            <div class="mt-1 text-gray-400 text-[13px] leading-relaxed">
                                {{ $staffProfile?->bio ?? 'ไม่มีข้อมูลแนะนำตัว' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── STAFF SCHEDULE ─── --}}
        <section class="mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full {{ $dotClass }}"></span>
                        <h2 class="font-bold text-gray-800 text-base md:text-lg">ตารางปฏิบัติงาน (Schedule Calendar)</h2>
                    </div>

                    <div class="ml-auto flex items-center gap-2">
                        <a href="{{ route('admin.private-schedule.index', ['staff_id' => $staff->id]) }}"
                           class="flex items-center gap-1.5 bg-orange-500 text-white border border-orange-600 px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-orange-600 shadow-sm transition cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                            </svg>
                            จัดการ Schedule แบบละเอียด
                        </a>
                    </div>
                </div>

                <div class="p-6">
                    <div class="mb-4 flex flex-wrap gap-4 text-[11px] text-gray-500 font-medium uppercase tracking-wide">
                        <span class="flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded-full bg-gray-500"></i>ไม่ว่าง</span>
                        @if($isCoach)
                            <span class="flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded-full bg-orange-500"></i>คำขอ Private</span>
                            <span class="flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded-full bg-violet-600"></i>ยืนยันแล้ว</span>
                        @endif
                    </div>
                    
                    <div id="staff-schedule-calendar" class="private-calendar-theme relative z-0"></div>
                </div>
            </div>
        </section>

    </div>
</div>

{{-- ─── MODAL: แก้ไขข้อมูลบุคลากร ─── --}}
<div id="staffProfileModal" class="fixed inset-0 z-[60] hidden bg-gray-900/60 backdrop-blur-sm items-center justify-center p-4 transition-all">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden border border-gray-100">
        <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-6 py-4">
            <h3 class="text-lg font-bold text-gray-800">แก้ไขข้อมูลบุคลากร</h3>
            <button type="button" onclick="toggleModal('staffProfileModal', false)" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form action="{{ route('admin.staffs.profile.update', $staff->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4 bg-white p-6 max-h-[80vh] overflow-y-auto">
            @csrf @method('PUT')

            {{-- ช่องอัปโหลดรูปโปรไฟล์ --}}
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-xs font-medium text-gray-600">รูปโปรไฟล์</label>
                <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-xl border border-gray-200">
                    <div class="w-16 h-16 rounded-full {{ $avatarBgClass }} flex items-center justify-center shrink-0 border border-gray-200 overflow-hidden relative">
                        <img id="staffAvatarPreview" src="{{ $staffProfile?->profile_image ? $staffProfile->profile_image_url : '' }}" class="{{ $staffProfile?->profile_image ? '' : 'hidden' }} w-full h-full object-cover">
                        <span id="staffAvatarFallback" class="{{ $staffProfile?->profile_image ? 'hidden' : '' }} text-2xl font-bold">{{ mb_strtoupper(mb_substr($staff->us_name, 0, 1)) }}</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <input type="file" name="profile_image" id="staffAvatarInput" accept="image/png, image/jpeg, image/webp" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100 cursor-pointer">
                            
                            {{-- ปุ่มลบภาพ --}}
                            <button type="button" id="removeStaffAvatarBtn" class="{{ $staffProfile?->profile_image ? '' : 'hidden' }} shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                ลบรูป
                            </button>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1.5">รองรับ JPG, PNG, WEBP ขนาดไม่เกิน 20MB</p>
                        <input type="hidden" name="remove_profile_image" id="removeStaffAvatarInput" value="0">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <input type="hidden" name="us_name" value="{{ $staff->us_name }}">
                    <input type="hidden" name="email" value="{{ $staff->email }}">    

                    <label class="mb-1.5 block text-xs font-medium text-gray-600">ตำแหน่ง (Role) <span class="text-red-500">*</span></label>
                    <select name="membership_type" required class="w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 outline-none transition focus:ring-2 focus:ring-blue-500">
                        <option value="court_assistant" {{ old('membership_type', $staff->membership_type) === 'court_assistant' ? 'selected' : '' }}>ผู้ช่วยสนาม (Staff)</option>
                        <option value="coach" {{ old('membership_type', $staff->membership_type) === 'coach' ? 'selected' : '' }}>ผู้ฝึกสอน (Coach)</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600">เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" value="{{ old('phone', $staff->phone ?? '') }}" maxlength="10" placeholder="08xxxxxxxx" oninput="this.value = this.value.replace(/[^0-9]/g, '');" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 outline-none transition focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600">เพศ</label>
                    <select name="gender" class="w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 outline-none transition focus:ring-2 focus:ring-blue-500">
                        <option value="">ไม่ระบุ</option>
                        <option value="male" {{ old('gender', $staffProfile?->gender) === 'male' ? 'selected' : '' }}>ชาย</option>
                        <option value="female" {{ old('gender', $staffProfile?->gender) === 'female' ? 'selected' : '' }}>หญิง</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600">ความเชี่ยวชาญ</label>
                    <input type="text" name="specialty" value="{{ old('specialty', $staffProfile?->specialty ?? '') }}" placeholder="เช่น ผู้ช่วยฝึกสอนเบสิค" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 outline-none transition focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-xs font-medium text-gray-600">แนะนำตัว (Bio)</label>
                    <textarea name="bio" rows="3" placeholder="เขียนคำแนะนำตัว..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 outline-none resize-none transition focus:ring-2 focus:ring-blue-500">{{ old('bio', $staffProfile?->bio ?? '') }}</textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-100 pt-4 mt-4">
                <button type="button" onclick="toggleModal('staffProfileModal', false)" class="cursor-pointer rounded-lg border border-gray-200 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-200">ยกเลิก</button>
                <button type="submit" class="cursor-pointer rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">บันทึกข้อมูล</button>
            </div>
        </form>
    </div>
</div>

{{-- ─── MODAL: จัดการสถานะการทำงาน (ลากคลุม Calendar) ─── --}}
<div id="dragActionModal" class="fixed inset-0 z-[60] hidden bg-gray-900/60 backdrop-blur-sm items-center justify-center p-4 transition-all">
    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden border border-gray-100">
        <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-6 py-4">
            <h3 class="text-lg font-bold text-gray-800">จัดการสถานะการทำงาน</h3>
        </div>

        <form action="{{ route('admin.staffs.availabilities.store', $staff->id) }}" method="POST" class="p-6 space-y-4 bg-white">
            @csrf
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-center mb-2">
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
                <label class="mb-1.5 block text-xs font-medium text-gray-600">สถานะ</label>
                <div class="flex items-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-700">
                    <span class="mr-2 inline-block h-2.5 w-2.5 rounded-full bg-gray-500"></span>
                    ช่วงเวลานี้ไม่ว่าง
                </div>
            </div>

            <div id="detail-container">
                <label class="mb-1.5 block text-xs font-medium text-gray-600">รายละเอียด / กิจกรรม (ถ้ามี)</label>
                <input type="text" id="modal-detail-input" name="detail" placeholder="เก็บบาส, เคลียร์สนาม"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 outline-none transition focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="pt-3 flex gap-2 border-t border-gray-100 mt-4">
                <button type="button" onclick="toggleModal('dragActionModal', false)"
                    class="w-1/2 cursor-pointer rounded-lg border border-gray-200 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-200">ยกเลิก</button>
                <button type="submit"
                    class="w-1/2 cursor-pointer rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">บันทึกสถานะ</button>
            </div>
        </form>
    </div>
</div>

{{-- Scripts --}}
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
                    backgroundColor: '#e5e7eb', // gray-200
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

                arg.el.style.backgroundColor = 'rgba(156, 163, 175, 0.3)'; // gray-400 opacity

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
                    color: #4b5563; /* gray-600 */
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
                        text: 'ช่วงเวลานี้ผ่านไปแล้ว',
                        confirmButtonColor:'#3b82f6'
                    });
                    return;
                }
                const props = info.event.extendedProps;
                Swal.fire({
                    icon: 'info',
                    title: info.event.title,
                    text: props.statusLabel || props.detail || 'กำหนดการของบุคลากร',
                    confirmButtonColor:'#3b82f6'
                });
            },
        }
    );
    staffScheduleCalendar.render();

    // ─── Modal Functions ───
    function toggleModal(id, show) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.toggle('hidden', !show);
            modal.classList.toggle('flex', show);
            if (id === 'dragActionModal' && !show) clearSelection();
        }
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            ['staffProfileModal', 'dragActionModal'].forEach(id => {
                const modal = document.getElementById(id);
                if (modal && !modal.classList.contains('hidden')) toggleModal(id, false);
            });
        }
    });

    // ─── Avatar Upload Functions ───
    const staffAvatarInput = document.getElementById('staffAvatarInput');
    const staffAvatarPreview = document.getElementById('staffAvatarPreview');
    const staffAvatarFallback = document.getElementById('staffAvatarFallback');
    const removeStaffAvatarBtn = document.getElementById('removeStaffAvatarBtn');
    const removeStaffAvatarInput = document.getElementById('removeStaffAvatarInput');

    if (staffAvatarInput) {
        staffAvatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 20 * 1024 * 1024) {
                    Swal.fire({ icon: 'error', title: 'ไฟล์ใหญ่เกินไป', text: 'กรุณาอัปโหลดรูปภาพขนาดไม่เกิน 20MB', confirmButtonColor:'#3b82f6' });
                    this.value = ''; 
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    staffAvatarPreview.src = e.target.result;
                    staffAvatarPreview.classList.remove('hidden');
                    if (staffAvatarFallback) staffAvatarFallback.classList.add('hidden');
                    if (removeStaffAvatarBtn) removeStaffAvatarBtn.classList.remove('hidden');
                    if (removeStaffAvatarInput) removeStaffAvatarInput.value = '0';
                }
                reader.readAsDataURL(file);
            }
        });
    }

    if (removeStaffAvatarBtn) {
        removeStaffAvatarBtn.addEventListener('click', function() {
            staffAvatarPreview.classList.add('hidden');
            staffAvatarPreview.src = '';
            if (staffAvatarFallback) staffAvatarFallback.classList.remove('hidden');
            if (staffAvatarInput) staffAvatarInput.value = '';
            this.classList.add('hidden');
            if (removeStaffAvatarInput) removeStaffAvatarInput.value = '1';
        });
    }

    // ─── Custom Drag Selection Logic (If used in list view) ───
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

</script>
@endsection