@extends('layouts.app')

@section('title', 'Schedule บุคลากร')

@section('content')
@include('private-training._calendar-theme')
<style>
    #staff-filter, #staff-filter option,
    #admin-schedule-modal input, #admin-schedule-modal select, #admin-schedule-modal textarea {
        color: #0f172a !important;
        background-color: #fff;
    }
</style>

<div class="min-h-screen bg-slate-50 py-8 text-slate-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="mb-6 flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <h1 class="text-[32px] font-bold text-gray-900 tracking-tight">Schedule โค้ชและผู้ช่วยสนาม</h1>
                <p class="mt-1 text-sm text-slate-500">แอดมินเพิ่ม แก้ไข หรือลบกำหนดการให้บุคลากรได้โดยตรง</p>
            </div>
            <div class="flex w-full flex-col gap-3 sm:flex-row lg:w-auto">
                <div class="w-full sm:w-72">
                    <label for="staff-filter" class="mb-1.5 block text-xs font-semibold text-slate-600">เลือกบุคลากร</label>
                    <select id="staff-filter" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100">
                        <option value="all" @selected($selectedStaffId === 'all')>ดูรวมทุกคน</option>
                        @foreach($staffs as $staff)
                            <option value="{{ $staff->id }}" @selected($staff->id === $selectedStaffId)>
                                {{ $staff->name }} — {{ $staff->membership_type === 'coach' ? 'โค้ช' : 'ผู้ช่วยสนาม' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button id="new-admin-schedule" type="button" class="inline-flex h-[42px] self-end items-center justify-center gap-1.5 rounded-lg bg-orange-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700">
                    <span class="text-base leading-none">+</span> เพิ่มกำหนดการ
                </button>
            </div>
        </div>

        <div class="mb-4 flex flex-wrap gap-4 text-xs text-slate-600">
            <span class="flex items-center gap-2"><i class="h-3 w-3 rounded-sm bg-indigo-400"></i> กิจกรรม</span>
            <span class="flex items-center gap-2"><i class="h-3 w-3 rounded-sm bg-emerald-500"></i> งาน</span>
            <span class="flex items-center gap-2"><i class="h-3 w-3 rounded-sm bg-red-600"></i> ลางาน</span>
            <span class="flex items-center gap-2"><i class="h-3 w-3 rounded-sm bg-sky-500"></i> คลาสโรงเรียนบาส</span>
            <span class="flex items-center gap-2"><i class="h-3 w-3 rounded-sm bg-purple-600"></i> Private กำหนดเอง</span>
            <span class="flex items-center gap-2"><i class="h-3 w-3 rounded-sm bg-orange-500"></i> คำขอจอง Private</span>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            @if($staffs->isEmpty())
                <div class="py-20 text-center text-sm text-slate-500">ยังไม่มีโค้ชหรือผู้ช่วยสนามในระบบ</div>
            @else
                <div id="private-schedule-calendar" class="private-calendar-theme"></div>
            @endif
        </section>
    </div>
</div>

<div id="admin-schedule-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
    <form id="admin-schedule-form" class="max-h-[calc(100vh-2rem)] w-full max-w-xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <div>
                <h2 id="admin-modal-title" class="text-lg font-bold text-slate-900">เพิ่มกำหนดการ</h2>
                <p id="admin-modal-note" class="mt-0.5 text-xs text-slate-500">เลือกบุคลากร วัน เวลา และสีของ Event</p>
            </div>
            <button type="button" data-close-admin-modal class="flex h-9 w-9 items-center justify-center rounded-lg text-2xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700">&times;</button>
        </div>

        <div class="space-y-4 p-6">
            <input id="admin-event-id" type="hidden">
            <input id="admin-source-kind" type="hidden" value="calendar_event">
            <input id="admin-event-color" type="hidden" value="#7986cb">

            <div>
                <label for="admin-event-title" class="mb-1 block text-xs font-semibold text-slate-600">ชื่อกำหนดการ</label>
                <input id="admin-event-title" required maxlength="255" placeholder="เช่น ประชุมทีม หรือลางาน" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="admin-staff-id" class="mb-1 block text-xs font-semibold text-slate-600">เจ้าของ Schedule</label>
                    <select id="admin-staff-id" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                        <option value="">เลือกโค้ชหรือผู้ช่วยสนาม</option>
                        @foreach($staffs as $staff)
                            <option value="{{ $staff->id }}" data-membership="{{ $staff->membership_type }}">{{ $staff->name }} — {{ $staff->membership_type === 'coach' ? 'โค้ช' : 'ผู้ช่วยสนาม' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="admin-event-type" class="mb-1 block text-xs font-semibold text-slate-600">ประเภท</label>
                    <select id="admin-event-type" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                        <option value="general">กิจกรรมส่วนตัว</option>
                        <option value="work">งาน</option>
                        <option value="leave">ลางาน</option>
                        <option value="school_class">คลาสโรงเรียนบาส</option>
                        <option value="private_training_manual" data-coach-only>Private Training (กำหนดเอง)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 sm:col-span-1">
                    <label for="admin-event-date" class="mb-1 block text-xs font-semibold text-slate-600">วันที่</label>
                    <input id="admin-event-date" type="date" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="admin-event-start" class="mb-1 block text-xs font-semibold text-slate-600">เริ่ม</label>
                        <input id="admin-event-start" type="time" step="1800" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                    </div>
                    <div>
                        <label for="admin-event-end" class="mb-1 block text-xs font-semibold text-slate-600">สิ้นสุด</label>
                        <input id="admin-event-end" type="time" step="1800" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                    </div>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="admin-event-recurrence" class="mb-1 block text-xs font-semibold text-slate-600">การเกิดซ้ำ</label>
                    <select id="admin-event-recurrence" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                        <option value="none">ไม่เกิดซ้ำ</option>
                        <option value="daily">ทุกวัน</option>
                        <option value="weekly">เลือกวันในแต่ละสัปดาห์</option>
                        <option value="monthly">ทุกเดือน</option>
                    </select>
                </div>
                <div id="admin-recurrence-until-wrap" class="hidden">
                    <label for="admin-recurrence-until" class="mb-1 block text-xs font-semibold text-slate-600">เกิดซ้ำถึงวันที่</label>
                    <input id="admin-recurrence-until" type="date" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                </div>
            </div>

            <div id="admin-recurrence-days-wrap" class="hidden">
                <p class="mb-2 text-xs font-semibold text-slate-600">วันที่เกิดซ้ำ</p>
                <div class="grid grid-cols-4 gap-2 sm:grid-cols-7">
                    @foreach(['mon' => 'จ', 'tue' => 'อ', 'wed' => 'พ', 'thu' => 'พฤ', 'fri' => 'ศ', 'sat' => 'ส', 'sun' => 'อา'] as $value => $label)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="admin_recurrence_days" value="{{ $value }}" class="peer sr-only">
                            <span class="flex h-10 items-center justify-center rounded-xl border border-slate-200 text-xs font-bold text-slate-500 peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold text-slate-600">สี Event</label>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach(['#7986cb', '#33b679', '#8e24aa', '#e67c73', '#f6bf26', '#f4511e', '#039be5', '#616161', '#3f51b5', '#0b8043', '#d50000'] as $color)
                        <button type="button" data-admin-event-color="{{ $color }}" aria-label="เลือกสี {{ $color }}" class="h-8 w-8 rounded-full border-2 border-white shadow-sm ring-offset-2 transition hover:scale-110" style="background: {{ $color }}"></button>
                    @endforeach
                    <label class="ml-1 flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-2 py-1 text-xs text-slate-500">
                        สีอื่น <input id="admin-custom-color" type="color" value="#7986cb" class="h-6 w-7 cursor-pointer border-0 p-0">
                    </label>
                </div>
            </div>

            <div>
                <label for="admin-event-description" class="mb-1 block text-xs font-semibold text-slate-600">รายละเอียด</label>
                <textarea id="admin-event-description" rows="3" maxlength="1000" placeholder="เพิ่มรายละเอียด (ถ้ามี)" class="w-full resize-none rounded-xl border border-slate-300 px-3 py-2.5"></textarea>
            </div>

            <p id="admin-event-error" class="hidden rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600"></p>
        </div>

        <div class="flex items-center gap-2 border-t border-slate-100 px-6 py-4">
            <button id="delete-admin-event" type="button" class="hidden rounded-xl px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">ลบ</button>
            <div class="flex-1"></div>
            <button type="button" data-close-admin-modal class="rounded-xl px-4 py-2 text-sm text-slate-600 hover:bg-slate-100">ยกเลิก</button>
            <button type="submit" class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-blue-700">บันทึก</button>
        </div>
    </form>
</div>

@include('private-training._booking-details-script')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.21/locales-all.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const calendarEl = document.getElementById('private-schedule-calendar');
    if (!calendarEl) return;

    const filter = document.getElementById('staff-filter');
    const modal = document.getElementById('admin-schedule-modal');
    const form = document.getElementById('admin-schedule-form');
    const eventsApi = @js(route('admin.private-schedule.events'));
    const eventApi = @js(route('admin.private-schedule.calendar-events.store'));
    const availabilityApi = @js(url('/admin/private-schedule/availabilities'));
    const csrf = @js(csrf_token());
    const field = id => document.getElementById(id);
    const pad = value => String(value).padStart(2, '0');
    const localDate = date => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const localTime = date => `${pad(date.getHours())}:${pad(date.getMinutes())}`;
    const isSameCalendarDate = (first, second) => Boolean(first && second) && localDate(first) === localDate(second);
    const staysWithinOneDate = (start, end) => !end || isSameCalendarDate(start, new Date(end.getTime() - 1));
    const checkedDays = () => [...document.querySelectorAll('input[name="admin_recurrence_days"]:checked')].map(input => input.value);

    function setCheckedDays(days) {
        document.querySelectorAll('input[name="admin_recurrence_days"]').forEach(input => input.checked = days.includes(input.value));
    }

    function selectColor(color) {
        const normalized = color.toLowerCase();
        field('admin-event-color').value = normalized;
        field('admin-custom-color').value = normalized;
        document.querySelectorAll('[data-admin-event-color]').forEach(button => {
            const selected = button.dataset.adminEventColor.toLowerCase() === normalized;
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            button.classList.toggle('ring-2', selected);
            button.classList.toggle('ring-slate-700', selected);
        });
    }

    function toggleRecurrence() {
        const recurrence = field('admin-event-recurrence').value;
        field('admin-recurrence-days-wrap').classList.toggle('hidden', recurrence !== 'weekly');
        field('admin-recurrence-until-wrap').classList.toggle('hidden', recurrence === 'none');
        field('admin-recurrence-until').required = recurrence !== 'none';
        field('admin-recurrence-until').min = field('admin-event-date').value;
    }

    function checkStartDay() {
        if (checkedDays().length || !field('admin-event-date').value) return;
        const keys = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        const date = new Date(`${field('admin-event-date').value}T12:00:00`);
        setCheckedDays([keys[date.getDay()]]);
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        calendar.unselect();
    }

    function openModal(event = null, start = null, end = null) {
        const props = event?.extendedProps || {};
        const isLegacy = props.kind === 'availability';
        const seriesStart = props.seriesStartsAt ? new Date(props.seriesStartsAt) : (event?.start || start || new Date());
        const seriesEnd = props.seriesEndsAt ? new Date(props.seriesEndsAt) : (event?.end || end || new Date(seriesStart.getTime() + 60 * 60 * 1000));
        form.reset();
        field('admin-event-id').value = isLegacy ? props.recordId : (props.eventId || '');
        field('admin-source-kind').value = isLegacy ? 'availability' : 'calendar_event';
        field('admin-event-title').value = props.rawTitle || props.detail || (event ? event.title : '');
        field('admin-staff-id').value = props.staffId || (filter.value === 'all' ? '' : filter.value);
        field('admin-event-type').value = props.eventType || 'general';
        syncEventTypeForStaff();
        field('admin-event-date').value = localDate(seriesStart);
        field('admin-event-start').value = localTime(seriesStart);
        field('admin-event-end').value = localTime(seriesEnd);
        field('admin-event-recurrence').value = isLegacy ? 'none' : (props.recurrence || 'none');
        field('admin-recurrence-until').value = props.recurrenceUntil || '';
        field('admin-event-description').value = props.description || props.detail || '';
        setCheckedDays(props.recurrenceDays || []);
        selectColor(event?.backgroundColor || '#7986cb');
        field('admin-modal-title').textContent = event ? 'แก้ไขกำหนดการ' : 'เพิ่มกำหนดการ';
        field('admin-modal-note').textContent = props.recurrence && props.recurrence !== 'none' ? 'การแก้ไขจะมีผลกับ Event ที่เกิดซ้ำทั้งชุด' : 'จัดการ Schedule ของโค้ชหรือผู้ช่วยสนาม';
        field('delete-admin-event').classList.toggle('hidden', !event);
        field('admin-staff-id').disabled = isLegacy;
        field('admin-event-type').disabled = isLegacy;
        field('admin-event-recurrence').disabled = isLegacy;
        field('admin-event-error').classList.add('hidden');
        toggleRecurrence();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    const payload = () => ({
        staff_id: field('admin-staff-id').value,
        title: field('admin-event-title').value,
        description: field('admin-event-description').value || null,
        date: field('admin-event-date').value,
        start_time: field('admin-event-start').value,
        end_time: field('admin-event-end').value,
        event_type: field('admin-event-type').value,
        color: field('admin-event-color').value,
        recurrence: field('admin-event-recurrence').value,
        recurrence_days: checkedDays(),
        recurrence_until: field('admin-recurrence-until').value || null
    });

    async function request(url, method, data = null) {
        const response = await fetch(url, {
            method,
            headers: {'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':csrf},
            body: data ? JSON.stringify(data) : null
        });
        if (!response.ok) {
            const result = await response.json().catch(() => ({}));
            throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'บันทึกกำหนดการไม่สำเร็จ');
        }
    }

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: @js($selectedStaffId === 'all' ? 'listWeek' : 'timeGridWeek'), locale: 'th', firstDay: 1, height: 'auto', nowIndicator: true,
        selectable: true, selectMirror: true, editable: true, eventOverlap: false, selectOverlap: false, slotEventOverlap: false,
        allDaySlot: false, slotMinTime: '08:00:00', slotMaxTime: '22:00:00', slotDuration: '00:30:00', snapDuration: '00:30:00',
        dayMaxEvents: true,
        headerToolbar: {left:'prev,next today', center:'title', right:'dayGridMonth,timeGridWeek,timeGridDay,listWeek'},
        buttonText: {today:'วันนี้', month:'เดือน', week:'สัปดาห์', day:'วัน', list:'รายการ'},
        events(info, success, failure) {
            fetch(`${eventsApi}?staff_id=${filter.value}&start=${encodeURIComponent(info.startStr)}&end=${encodeURIComponent(info.endStr)}`, {headers:{'Accept':'application/json'}})
                .then(response => response.ok ? response.json() : Promise.reject(response)).then(success).catch(failure);
        },
        selectAllow(info) {
            return staysWithinOneDate(info.start, info.end)
                && info.start > new Date();
        },
        eventAllow(dropInfo, event) {
            return ['availability', 'calendar_event'].includes(event.extendedProps.kind)
                && (event.extendedProps.kind === 'availability' || event.extendedProps.recurrence === 'none')
                && isSameCalendarDate(event.start, dropInfo.start)
                && staysWithinOneDate(dropInfo.start, dropInfo.end);
        },
        select(info) {
            if (!staysWithinOneDate(info.start, info.end)) {
                calendar.unselect();
                Swal.fire({
                    icon: 'warning',
                    title: 'เลือกข้ามวันไม่ได้',
                    text: 'กรุณาเลือกช่วงเวลาภายในวันเดียวกัน'
                });
                return;
            }

            openModal(null, info.start, info.end);
        },
        eventClick(info) {
            if (['availability', 'calendar_event'].includes(info.event.extendedProps.kind)) return openModal(info.event);
            showPrivateTrainingDetails(info.event);
        },
        eventDrop: persistMove,
        eventResize: persistMove
    });

    async function persistMove(info) {
        const props = info.event.extendedProps;

        if (!isSameCalendarDate(info.oldEvent?.start, info.event.start)
            || !staysWithinOneDate(info.event.start, info.event.end)) {
            info.revert();
            Swal.fire({
                icon: 'warning',
                title: 'ย้ายข้ามวันไม่ได้',
                text: 'กำหนดการของโค้ชและผู้ช่วยสนามปรับเวลาได้เฉพาะภายในวันเดิมเท่านั้น'
            });
            return;
        }

        try {
            if (props.kind === 'availability') {
                await request(`${availabilityApi}/${props.recordId}`, 'PUT', {date:localDate(info.event.start), start_time:localTime(info.event.start), end_time:localTime(info.event.end), detail:props.detail || null});
            } else {
                await request(`${eventApi}/${props.eventId}`, 'PUT', {...payload(), staff_id:props.staffId, title:props.rawTitle || info.event.title, description:props.description || null, date:localDate(info.event.start), start_time:localTime(info.event.start), end_time:localTime(info.event.end), event_type:props.eventType, color:info.event.backgroundColor, recurrence:'none', recurrence_days:[], recurrence_until:null});
            }
            calendar.refetchEvents();
        } catch (error) {
            info.revert();
            Swal.fire({icon:'error', title:'ย้ายกำหนดการไม่สำเร็จ', text:error.message});
        }
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const id = field('admin-event-id').value;
        const isLegacy = field('admin-source-kind').value === 'availability';
        try {
            await request(isLegacy ? `${availabilityApi}/${id}` : (id ? `${eventApi}/${id}` : eventApi), id ? 'PUT' : 'POST', isLegacy ? {date:field('admin-event-date').value, start_time:field('admin-event-start').value, end_time:field('admin-event-end').value, detail:field('admin-event-description').value || field('admin-event-title').value || null} : payload());
            closeModal();
            calendar.refetchEvents();
        } catch (error) {
            field('admin-event-error').textContent = error.message;
            field('admin-event-error').classList.remove('hidden');
        }
    });

    field('delete-admin-event').addEventListener('click', async () => {
        const id = field('admin-event-id').value;
        const isLegacy = field('admin-source-kind').value === 'availability';
        const result = await Swal.fire({icon:'warning', title:'ลบกำหนดการนี้?', text:field('admin-event-recurrence').value !== 'none' ? 'Event ที่เกิดซ้ำจะถูกลบทั้งชุด' : 'รายการนี้จะหายจาก Schedule', showCancelButton:true, confirmButtonText:'ลบ', cancelButtonText:'ยกเลิก', confirmButtonColor:'#dc2626'});
        if (!result.isConfirmed) return;
        try {
            await request(isLegacy ? `${availabilityApi}/${id}` : `${eventApi}/${id}`, 'DELETE');
            closeModal();
            calendar.refetchEvents();
        } catch (error) {
            Swal.fire({icon:'error', title:'ลบกำหนดการไม่สำเร็จ', text:error.message});
        }
    });

    field('admin-event-recurrence').addEventListener('change', () => { if (field('admin-event-recurrence').value === 'weekly') checkStartDay(); toggleRecurrence(); });
    field('admin-event-date').addEventListener('change', toggleRecurrence);
    function syncEventTypeForStaff() {
        const selected = field('admin-staff-id').selectedOptions[0];
        const isCoach = selected?.dataset.membership === 'coach';
        const privateOption = field('admin-event-type').querySelector('[data-coach-only]');
        privateOption.disabled = !isCoach;
        privateOption.hidden = !isCoach;
        if (!isCoach && field('admin-event-type').value === 'private_training_manual') {
            field('admin-event-type').value = 'general';
            selectColor('#7986cb');
        }
    }

    field('admin-event-type').addEventListener('change', () => {
        const colors = {general:'#7986cb', work:'#33b679', leave:'#d50000', school_class:'#039be5', private_training_manual:'#7c3aed'};
        selectColor(colors[field('admin-event-type').value]);
    });
    field('admin-staff-id').addEventListener('change', syncEventTypeForStaff);
    document.querySelectorAll('[data-admin-event-color]').forEach(button => button.addEventListener('click', () => selectColor(button.dataset.adminEventColor)));
    field('admin-custom-color').addEventListener('input', event => selectColor(event.target.value));
    field('new-admin-schedule').addEventListener('click', () => {
        const start = new Date(Date.now() + 60 * 60 * 1000);
        start.setMinutes(start.getMinutes() < 30 ? 30 : 0, 0, 0);
        if (start.getMinutes() === 0) start.setHours(start.getHours() + 1);
        openModal(null, start, new Date(start.getTime() + 60 * 60 * 1000));
    });
    document.querySelectorAll('[data-close-admin-modal]').forEach(button => button.addEventListener('click', closeModal));
    modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
    filter.addEventListener('change', () => {
        calendar.changeView(filter.value === 'all' ? 'listWeek' : 'timeGridWeek');
        calendar.refetchEvents();
    });
    calendar.render();
});
</script>
@endsection
