@extends('layouts.app')

@section('title', 'Schedule ของฉัน')

@section('content')
@include('private-training._calendar-theme')
<style>
    #coach-event-modal input,
    #coach-event-modal select,
    #coach-event-modal textarea {
        color: #0f172a !important;
        background-color: #fff;
    }
</style>

<div class="min-h-screen bg-slate-50 py-8 text-slate-900">
    <div class="container mx-auto px-4 sm:px-6 max-w-7xl">
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <span class="inline-flex rounded-full bg-orange-100 px-3 py-1 text-xs font-bold text-orange-700">สำหรับโค้ชและผู้ช่วยสนาม</span>
                <h1 class="mt-3 text-2xl font-bold text-slate-900">Schedule ของฉัน</h1>
                <p class="mt-1 text-sm text-slate-500">ใส่กิจกรรม งาน วันลา หรือคลาสของตัวเองได้เหมือนปฏิทินส่วนตัว</p>
            </div>
            <button id="new-calendar-event" type="button" class="inline-flex h-[42px] items-center justify-center gap-1.5 rounded-lg bg-orange-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700">
                <span class="text-base leading-none">+</span> เพิ่มกำหนดการ
            </button>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <div class="mb-4 flex flex-wrap gap-4 text-xs text-slate-600">
                <span class="flex items-center gap-1.5"><i class="h-3 w-3 rounded bg-blue-600"></i>คลาสโรงเรียนบาส</span>
                <span class="flex items-center gap-1.5"><i class="h-3 w-3 rounded bg-slate-500"></i>กำหนดการอื่น</span>
                <span class="flex items-center gap-1.5"><i class="h-3 w-3 rounded bg-orange-500"></i>รออนุมัติ</span>
                <span class="flex items-center gap-1.5"><i class="h-3 w-3 rounded bg-purple-600"></i>รอจัดสนาม</span>
                <span class="flex items-center gap-1.5"><i class="h-3 w-3 rounded bg-green-600"></i>ยืนยันแล้ว</span>
                <span class="text-slate-400">พื้นที่ว่าง = นักเรียนจองได้</span>
            </div>
            <div id="coach-private-calendar" class="private-calendar-theme"></div>
        </section>
    </div>
</div>

<div id="coach-event-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
    <form id="coach-event-form" class="max-h-[calc(100vh-2rem)] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 id="coach-event-modal-title" class="text-lg font-bold text-slate-900">เพิ่มกำหนดการ</h2>
                <p id="coach-event-modal-note" class="mt-0.5 text-xs text-slate-500">สร้าง Event แบบครั้งเดียวหรือเกิดซ้ำได้</p>
            </div>
            <button type="button" data-close-event-modal class="text-2xl leading-none text-slate-400 hover:text-slate-700">&times;</button>
        </div>

        <input id="coach-event-id" type="hidden">
        <input id="coach-event-source-kind" type="hidden" value="calendar_event">

        <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="coach-event-title" class="mb-1 block text-xs font-semibold text-slate-600">ชื่อกำหนดการ</label>
                    <input id="coach-event-title" required maxlength="255" placeholder="เช่น คลาสเยาวชนรุ่น U12" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100">
                </div>
                <div>
                    <label for="coach-event-type" class="mb-1 block text-xs font-semibold text-slate-600">ประเภท</label>
                    <select id="coach-event-type" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100">
                        <option value="general">กิจกรรมส่วนตัว</option>
                        <option value="work">งาน</option>
                        <option value="leave">ลางาน</option>
                        <option value="school_class">คลาสโรงเรียนบาส</option>
                        @if($coach->membership_type === 'coach')
                            <option value="private_training_manual">Private Training (กำหนดเอง)</option>
                        @endif
                    </select>
                </div>
            </div>

            <div id="school-class-hint" class="hidden rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs leading-relaxed text-blue-700">
                คลาสนี้เป็น Event ในปฏิทินเท่านั้น ไม่เชื่อมกับข้อมูลคอร์สเรียนหรือแพ็กเกจในฐานข้อมูล
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label for="coach-event-date" class="mb-1 block text-xs font-semibold text-slate-600">วันที่เริ่ม</label>
                    <input id="coach-event-date" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100">
                </div>
                <div>
                    <label for="coach-event-start" class="mb-1 block text-xs font-semibold text-slate-600">เวลาเริ่ม</label>
                    <input id="coach-event-start" type="time" min="08:00" max="21:30" step="1800" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100">
                </div>
                <div>
                    <label for="coach-event-end" class="mb-1 block text-xs font-semibold text-slate-600">เวลาสิ้นสุด</label>
                    <input id="coach-event-end" type="time" min="08:30" max="22:00" step="1800" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="coach-event-recurrence" class="mb-1 block text-xs font-semibold text-slate-600">การเกิดซ้ำ</label>
                    <select id="coach-event-recurrence" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100">
                        <option value="none">ไม่เกิดซ้ำ</option>
                        <option value="daily">ทุกวัน</option>
                        <option value="weekly">เลือกวันในแต่ละสัปดาห์</option>
                        <option value="monthly">ทุกเดือน</option>
                    </select>
                </div>
                <div id="recurrence-until-wrap" class="hidden">
                    <label for="coach-event-recurrence-until" class="mb-1 block text-xs font-semibold text-slate-600">เกิดซ้ำถึงวันที่</label>
                    <input id="coach-event-recurrence-until" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100">
                </div>
            </div>

            <div id="recurrence-days-wrap" class="hidden">
                <p class="mb-2 text-xs font-semibold text-slate-600">วันที่เกิดซ้ำในแต่ละสัปดาห์</p>
                <div class="grid grid-cols-4 gap-2 sm:grid-cols-7">
                    @foreach(['mon' => 'จ', 'tue' => 'อ', 'wed' => 'พ', 'thu' => 'พฤ', 'fri' => 'ศ', 'sat' => 'ส', 'sun' => 'อา'] as $value => $label)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="recurrence_days" value="{{ $value }}" class="peer sr-only">
                            <span class="flex h-10 items-center justify-center rounded-lg border border-slate-200 text-xs font-bold text-slate-500 transition peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold text-slate-600">สี Event</label>
                <input id="coach-event-color" type="hidden" value="#039be5">
                <div class="flex flex-wrap items-center gap-2" data-event-color-palette>
                    @foreach(['#7986cb', '#33b679', '#8e24aa', '#e67c73', '#f6bf26', '#f4511e', '#039be5', '#616161', '#3f51b5', '#0b8043', '#d50000'] as $color)
                        <button type="button" data-event-color="{{ $color }}" aria-label="เลือกสี {{ $color }}" class="h-8 w-8 rounded-full border-2 border-white shadow-sm ring-offset-2 transition hover:scale-110" style="background: {{ $color }}"></button>
                    @endforeach
                    <label class="ml-1 flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-2 py-1 text-xs text-slate-500 hover:bg-slate-50">
                        สีอื่น
                        <input id="coach-event-custom-color" type="color" value="#039be5" class="h-6 w-7 cursor-pointer border-0 bg-transparent p-0">
                    </label>
                </div>
            </div>

            <div>
                <div>
                    <label for="coach-event-description" class="mb-1 block text-xs font-semibold text-slate-600">รายละเอียด (ถ้ามี)</label>
                    <textarea id="coach-event-description" rows="3" maxlength="1000" placeholder="เช่น คลาสพื้นฐานสำหรับนักเรียนอายุ 8–12 ปี" class="w-full resize-none rounded-lg border border-slate-300 px-3 py-2.5 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100"></textarea>
                </div>
            </div>
        </div>

        <p id="coach-event-error" class="mt-4 hidden rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600"></p>
        <div class="mt-6 flex flex-wrap gap-2">
            <button id="delete-coach-event" type="button" class="hidden rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">ลบกำหนดการ</button>
            <div class="flex-1"></div>
            <button type="button" data-close-event-modal class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">ยกเลิก</button>
            <button type="submit" class="rounded-lg bg-orange-600 px-5 py-2 text-sm font-bold text-white hover:bg-orange-700">บันทึก</button>
        </div>
    </form>
</div>

@include('private-training._booking-details-script')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.21/locales-all.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('coach-event-modal');
    const form = document.getElementById('coach-event-form');
    const eventsApi = @js(route('private-training.my-schedule.events'));
    const calendarEventApi = @js(route('private-training.my-schedule.calendar-events.store'));
    const csrf = @js(csrf_token());
    const pad = value => String(value).padStart(2, '0');
    const localDate = date => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const localTime = date => `${pad(date.getHours())}:${pad(date.getMinutes())}`;
    const isSameCalendarDate = (first, second) => Boolean(first && second) && localDate(first) === localDate(second);
    const staysWithinOneDate = (start, end) => !end || isSameCalendarDate(start, new Date(end.getTime() - 1));
    const field = id => document.getElementById(id);
    const checkedDays = () => [...document.querySelectorAll('input[name="recurrence_days"]:checked')].map(input => input.value);

    function selectEventColor(color) {
        const normalized = color.toLowerCase();
        field('coach-event-color').value = normalized;
        field('coach-event-custom-color').value = normalized;
        document.querySelectorAll('[data-event-color]').forEach(button => {
            const selected = button.dataset.eventColor.toLowerCase() === normalized;
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            button.classList.toggle('ring-2', selected);
            button.classList.toggle('ring-slate-700', selected);
        });
    }

    function setCheckedDays(days) {
        document.querySelectorAll('input[name="recurrence_days"]').forEach(input => {
            input.checked = days.includes(input.value);
        });
    }

    function toggleConditionalFields() {
        const recurrence = field('coach-event-recurrence').value;
        field('recurrence-days-wrap').classList.toggle('hidden', recurrence !== 'weekly');
        field('recurrence-until-wrap').classList.toggle('hidden', recurrence === 'none');
        field('coach-event-recurrence-until').required = recurrence !== 'none';
        field('coach-event-recurrence-until').min = field('coach-event-date').value;
        field('school-class-hint').classList.toggle('hidden', field('coach-event-type').value !== 'school_class');
    }

    function checkEventStartDay() {
        if (checkedDays().length || !field('coach-event-date').value) return;
        const dayKeys = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        const selectedDate = new Date(`${field('coach-event-date').value}T12:00:00`);
        setCheckedDays([dayKeys[selectedDate.getDay()]]);
    }

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        calendar.unselect();
    };

    function openModal(event = null, start = null, end = null) {
        const props = event?.extendedProps || {};
        const sourceKind = props.kind || 'calendar_event';
        const editing = Boolean(event);
        const isLegacy = sourceKind === 'blocked';
        const seriesStart = props.seriesStartsAt ? new Date(props.seriesStartsAt) : (event?.start || start);
        const seriesEnd = props.seriesEndsAt ? new Date(props.seriesEndsAt) : (event?.end || end);

        form.reset();
        field('coach-event-id').value = isLegacy ? props.recordId : (props.eventId || '');
        field('coach-event-source-kind').value = sourceKind;
        field('coach-event-title').value = event?.title || '';
        field('coach-event-type').value = props.eventType || 'general';
        field('coach-event-date').value = localDate(seriesStart || new Date());
        field('coach-event-start').value = localTime(seriesStart || new Date());
        field('coach-event-end').value = localTime(seriesEnd || new Date((seriesStart || new Date()).getTime() + 60 * 60 * 1000));
        field('coach-event-description').value = props.description || props.detail || '';
        selectEventColor(event?.backgroundColor || (props.eventType === 'school_class' ? '#039be5' : '#7986cb'));
        field('coach-event-recurrence').value = isLegacy ? 'none' : (props.recurrence || 'none');
        field('coach-event-recurrence-until').value = props.recurrenceUntil || '';
        setCheckedDays(props.recurrenceDays || []);

        field('coach-event-modal-title').textContent = editing ? 'แก้ไขกำหนดการ' : 'เพิ่มกำหนดการ';
        field('coach-event-modal-note').textContent = isLegacy
            ? 'รายการเดิมแบบครั้งเดียว สามารถแก้วัน เวลา และรายละเอียดได้'
            : 'การแก้ Event ที่เกิดซ้ำจะมีผลกับทั้งชุด';
        field('delete-coach-event').classList.toggle('hidden', !editing);
        field('coach-event-recurrence').disabled = isLegacy;
        field('coach-event-type').disabled = isLegacy;
        field('coach-event-color').disabled = isLegacy;
        field('coach-event-error').classList.add('hidden');
        toggleConditionalFields();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    const calendarPayload = () => ({
        title: field('coach-event-title').value,
        description: field('coach-event-description').value || null,
        date: field('coach-event-date').value,
        start_time: field('coach-event-start').value,
        end_time: field('coach-event-end').value,
        event_type: field('coach-event-type').value,
        color: field('coach-event-color').value,
        recurrence: field('coach-event-recurrence').value,
        recurrence_days: checkedDays(),
        recurrence_until: field('coach-event-recurrence-until').value || null
    });

    const legacyPayload = () => ({
        date: field('coach-event-date').value,
        start_time: field('coach-event-start').value,
        end_time: field('coach-event-end').value,
        detail: field('coach-event-description').value || field('coach-event-title').value || null
    });

    const request = async (url, method, data) => {
        const response = await fetch(url, {
            method,
            headers: {'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':csrf},
            body: data ? JSON.stringify(data) : null
        });
        if (!response.ok) {
            const result = await response.json().catch(() => ({}));
            throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'บันทึกกำหนดการไม่สำเร็จ');
        }
    };

    const calendar = new FullCalendar.Calendar(document.getElementById('coach-private-calendar'), {
        initialView: 'timeGridWeek',
        locale: 'th',
        firstDay: 1,
        height: 'auto',
        nowIndicator: true,
        selectable: true,
        selectMirror: true,
        editable: true,
        eventOverlap: false,
        slotEventOverlap: false,
        selectOverlap: false,
        allDaySlot: false,
        slotMinTime: '08:00:00',
        slotMaxTime: '22:00:00',
        slotDuration: '00:30:00',
        snapDuration: '00:30:00',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
        buttonText: { today: 'วันนี้', month: 'เดือน', week: 'สัปดาห์', day: 'วัน', list: 'รายการ' },
        dayMaxEvents: true,
        events: eventsApi,
        selectAllow(info) {
            return staysWithinOneDate(info.start, info.end)
                && info.start > new Date();
        },
        eventAllow(dropInfo, draggedEvent) {
            return ['blocked', 'calendar_event'].includes(draggedEvent.extendedProps.kind)
                && (draggedEvent.extendedProps.kind === 'blocked' || draggedEvent.extendedProps.recurrence === 'none')
                && isSameCalendarDate(draggedEvent.start, dropInfo.start)
                && staysWithinOneDate(dropInfo.start, dropInfo.end)
                && dropInfo.start > new Date();
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
            if (['blocked', 'calendar_event'].includes(info.event.extendedProps.kind)) {
                openModal(info.event);
                return;
            }

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
            if (props.kind === 'blocked') {
                await request(`${eventsApi}/${props.recordId}`, 'PUT', {
                    date: localDate(info.event.start),
                    start_time: localTime(info.event.start),
                    end_time: localTime(info.event.end),
                    detail: props.detail || null
                });
            } else if (props.kind === 'calendar_event' && props.recurrence === 'none') {
                const data = {
                    title: info.event.title,
                    description: props.description || null,
                    date: localDate(info.event.start),
                    start_time: localTime(info.event.start),
                    end_time: localTime(info.event.end),
                    event_type: props.eventType || 'general',
                    color: info.event.backgroundColor,
                    recurrence: 'none',
                    recurrence_days: [],
                    recurrence_until: null
                };
                await request(`${calendarEventApi}/${props.eventId}`, 'PUT', data);
            } else {
                info.revert();
                return;
            }
            calendar.refetchEvents();
        } catch (error) {
            info.revert();
            Swal.fire({icon:'error', title:'แก้ไขกำหนดการไม่สำเร็จ', text:error.message});
        }
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const id = field('coach-event-id').value;
        const isLegacy = field('coach-event-source-kind').value === 'blocked';
        try {
            await request(
                isLegacy ? `${eventsApi}/${id}` : (id ? `${calendarEventApi}/${id}` : calendarEventApi),
                id ? 'PUT' : 'POST',
                isLegacy ? legacyPayload() : calendarPayload()
            );
            closeModal();
            calendar.refetchEvents();
        } catch (error) {
            field('coach-event-error').textContent = error.message;
            field('coach-event-error').classList.remove('hidden');
        }
    });

    field('delete-coach-event').addEventListener('click', async () => {
        const id = field('coach-event-id').value;
        if (!id) return;
        const isLegacy = field('coach-event-source-kind').value === 'blocked';
        const recurring = field('coach-event-recurrence').value !== 'none';
        const result = await Swal.fire({
            icon: 'warning',
            title: 'ลบกำหนดการนี้?',
            text: recurring ? 'ระบบจะลบกำหนดการที่เกิดซ้ำทั้งชุด' : 'เมื่อลบแล้ว นักเรียนจะเลือกจองช่วงเวลานี้ได้',
            showCancelButton: true,
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#dc2626'
        });
        if (!result.isConfirmed) return;

        try {
            await request(isLegacy ? `${eventsApi}/${id}` : `${calendarEventApi}/${id}`, 'DELETE');
            closeModal();
            calendar.refetchEvents();
        } catch (error) {
            Swal.fire({icon:'error', title:'ลบกำหนดการไม่สำเร็จ', text:error.message});
        }
    });

    field('coach-event-recurrence').addEventListener('change', () => {
        if (field('coach-event-recurrence').value === 'weekly') checkEventStartDay();
        toggleConditionalFields();
    });
    field('coach-event-date').addEventListener('change', toggleConditionalFields);
    field('coach-event-type').addEventListener('change', () => {
        const isSchoolClass = field('coach-event-type').value === 'school_class';
        if (isSchoolClass && !field('coach-event-title').value.trim()) field('coach-event-title').value = 'คลาสโรงเรียนบาส';
        const suggestedColors = {school_class:'#039be5', private_training_manual:'#7c3aed', work:'#33b679', leave:'#d50000', general:'#7986cb'};
        selectEventColor(suggestedColors[field('coach-event-type').value] || '#7986cb');
        toggleConditionalFields();
    });
    document.querySelectorAll('[data-event-color]').forEach(button => button.addEventListener('click', () => selectEventColor(button.dataset.eventColor)));
    field('coach-event-custom-color').addEventListener('input', event => selectEventColor(event.target.value));
    field('new-calendar-event').addEventListener('click', () => {
        const start = new Date(Math.max(Date.now() + 60 * 60 * 1000, calendar.getDate().getTime()));
        start.setMinutes(start.getMinutes() < 30 ? 30 : 0, 0, 0);
        if (start.getMinutes() === 0) start.setHours(start.getHours() + 1);
        const end = new Date(start.getTime() + 2 * 60 * 60 * 1000);
        openModal(null, start, end);
    });
    document.querySelectorAll('[data-close-event-modal]').forEach(button => button.addEventListener('click', closeModal));
    modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
    calendar.render();
});
</script>
@endsection
