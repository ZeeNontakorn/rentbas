@extends('layouts.app')

@section('title', 'จัดการ Private Schedule')

@section('content')
@include('private-training._calendar-theme')
<style>
    #coach-filter,
    #coach-filter option,
    #schedule-modal input,
    #schedule-modal select,
    #schedule-modal select option {
        color: #0f172a !important;
        background-color: #fff;
    }

</style>

<div class="min-h-screen bg-slate-50 py-8 text-slate-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">จัดการ Private Schedule</h1>
                <p class="mt-1 text-sm text-slate-500">กำหนดช่วงเวลาว่างและเวลาที่ไม่รับงานของโค้ช โดยไม่รวมคลาสโรงเรียน</p>
            </div>
            <div class="w-full sm:w-72">
                <label for="coach-filter" class="mb-1.5 block text-xs font-semibold text-slate-600">เลือกโค้ช</label>
                <select id="coach-filter" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100">
                    <option value="all" @selected($selectedCoachId === 'all')>โค้ชทุกคน (ดูรวม)</option>
                    @forelse($coaches as $coach)
                        <option value="{{ $coach->id }}" @selected($coach->id === $selectedCoachId)>{{ $coach->name }}</option>
                    @empty
                        <option value="">ยังไม่มีโค้ชในระบบ</option>
                    @endforelse
                </select>
            </div>
        </div>

        <div class="mb-4 flex flex-wrap gap-4 text-xs text-slate-600">
            <span class="flex items-center gap-2"><i class="h-3 w-3 rounded-sm bg-green-600"></i> เปิดรับจอง</span>
            <span class="flex items-center gap-2"><i class="h-3 w-3 rounded-sm bg-slate-500"></i> ไม่ว่าง</span>
            <span class="flex items-center gap-2"><i class="h-3 w-3 rounded-sm bg-orange-500"></i> คำขอจอง</span>
            <span class="flex items-center gap-2"><i class="h-3 w-3 rounded-sm bg-violet-600"></i> ยืนยันแล้ว</span>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            @if($coaches->isEmpty())
                <div class="py-20 text-center text-sm text-slate-500">กรุณากำหนดบทบาท Staff → Coach ในหน้าจัดการผู้ใช้ก่อน</div>
            @else
                <div id="private-schedule-calendar" class="private-calendar-theme"></div>
            @endif
        </section>
    </div>
</div>

<div id="schedule-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4">
    <form id="schedule-form" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div class="mb-5 flex items-center justify-between">
            <h2 id="schedule-modal-title" class="text-lg font-semibold text-slate-900">เพิ่มช่วงเวลา</h2>
            <button type="button" data-close-modal class="text-2xl leading-none text-slate-400 hover:text-slate-700">&times;</button>
        </div>
        <input id="schedule-id" type="hidden">
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-600">วันที่</label>
                <input id="schedule-date" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">เริ่ม</label>
                <input id="schedule-start" type="time" step="1800" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">สิ้นสุด</label>
                <input id="schedule-end" type="time" step="1800" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div class="col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-600">สถานะ</label>
                <select id="schedule-status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="available">เปิดรับจอง</option>
                    <option value="booked">ไม่ว่าง</option>
                </select>
            </div>
            <div class="col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-600">รายละเอียด</label>
                <input id="schedule-detail" maxlength="255" placeholder="เช่น รับ Private Training" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
        </div>
        <p id="schedule-error" class="mt-3 hidden text-sm text-red-600"></p>
        <div class="mt-6 flex gap-2">
            <button id="delete-schedule" type="button" class="hidden rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">ลบ</button>
            <div class="flex-1"></div>
            <button type="button" data-close-modal class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600">ยกเลิก</button>
            <button type="submit" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700">บันทึก</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.21/locales-all.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const calendarEl = document.getElementById('private-schedule-calendar');
    if (!calendarEl) return;
    const coach = document.getElementById('coach-filter');
    const modal = document.getElementById('schedule-modal');
    const form = document.getElementById('schedule-form');
    const csrf = @js(csrf_token());
    const api = @js(route('admin.private-schedule.events'));
    const pad = value => String(value).padStart(2, '0');
    const localDate = date => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const localTime = date => `${pad(date.getHours())}:${pad(date.getMinutes())}`;
    const field = id => document.getElementById(id);
    const closeModal = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
    const openModal = (event, start, end) => {
        const editing = Boolean(event);
        field('schedule-id').value = editing ? event.extendedProps.recordId : '';
        field('schedule-date').value = localDate(editing ? event.start : start);
        field('schedule-start').value = localTime(editing ? event.start : start);
        field('schedule-end').value = localTime(editing ? event.end : end);
        field('schedule-status').value = editing ? event.extendedProps.status : 'available';
        field('schedule-detail').value = editing ? (event.extendedProps.detail || '') : '';
        field('schedule-modal-title').textContent = editing ? 'แก้ไขช่วงเวลา' : 'เพิ่มช่วงเวลา';
        field('delete-schedule').classList.toggle('hidden', !editing);
        field('schedule-error').classList.add('hidden');
        modal.classList.remove('hidden'); modal.classList.add('flex');
    };
    const payload = () => ({
        coach_id: coach.value,
        date: field('schedule-date').value,
        start_time: field('schedule-start').value,
        end_time: field('schedule-end').value,
        status: field('schedule-status').value,
        detail: field('schedule-detail').value || null
    });
    const request = async (url, method, data) => {
        const response = await fetch(url, {
            method,
            headers: {'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':csrf},
            body: data ? JSON.stringify(data) : null
        });
        if (!response.ok) {
            const result = await response.json().catch(() => ({}));
            throw new Error(Object.values(result.errors || {}).flat()[0] || 'บันทึกข้อมูลไม่สำเร็จ');
        }
    };
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        locale: 'th',
        firstDay: 1,
        height: 'auto',
        nowIndicator: true,
        selectable: true,
        editable: true,
        allDaySlot: false,
        slotMinTime: '08:00:00',
        slotMaxTime: '22:00:00',
        slotDuration: '00:30:00',
        snapDuration: '00:30:00',
        headerToolbar: {left:'prev,next today', center:'title', right:'dayGridMonth,timeGridWeek,timeGridDay,listWeek'},
        buttonText: {today:'วันนี้', month:'เดือน', week:'สัปดาห์', day:'วัน', list:'รายการ'},
        events(info, success, failure) {
            fetch(`${api}?coach_id=${coach.value}&start=${encodeURIComponent(info.startStr)}&end=${encodeURIComponent(info.endStr)}`, {headers:{'Accept':'application/json'}})
                .then(response => response.ok ? response.json() : Promise.reject(response))
                .then(success).catch(failure);
        },
        selectAllow() {
            return coach.value !== 'all';
        },
        eventAllow() {
            return coach.value !== 'all';
        },
        select(info) {
            if (coach.value === 'all') {
                calendar.unselect();
                Swal.fire({icon:'info', title:'กรุณาเลือกโค้ชก่อน', text:'มุมมองรวมใช้สำหรับดูตารางเท่านั้น'});
                return;
            }
            openModal(null, info.start, info.end);
            calendar.unselect();
        },
        eventClick(info) {
            if (info.event.extendedProps.kind === 'booking') {
                Swal.fire({icon:'info', title:info.event.title, text:'รายการจองต้องจัดการจากหน้า Private Training'});
                return;
            }
            if (coach.value === 'all') {
                coach.value = String(info.event.extendedProps.coachId);
            }
            openModal(info.event);
        },
        eventDrop: persistMove,
        eventResize: persistMove
    });
    async function persistMove(info) {
        if (coach.value === 'all' || info.event.extendedProps.kind !== 'availability') return info.revert();
        const data = {
            coach_id: coach.value,
            date: localDate(info.event.start),
            start_time: localTime(info.event.start),
            end_time: localTime(info.event.end),
            status: info.event.extendedProps.status,
            detail: info.event.extendedProps.detail || null
        };
        try { await request(`${api}/${info.event.extendedProps.recordId}`, 'PUT', data); }
        catch (error) { info.revert(); Swal.fire({icon:'error', title:error.message}); }
        calendar.refetchEvents();
    }
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const id = field('schedule-id').value;
        try {
            await request(id ? `${api}/${id}` : api, id ? 'PUT' : 'POST', payload());
            closeModal(); calendar.refetchEvents();
        } catch (error) {
            field('schedule-error').textContent = error.message;
            field('schedule-error').classList.remove('hidden');
        }
    });
    field('delete-schedule').addEventListener('click', async () => {
        const id = field('schedule-id').value;
        if (!id || !confirm('ลบช่วงเวลานี้ใช่หรือไม่?')) return;
        try {
            await request(`${api}/${id}`, 'DELETE', {coach_id: coach.value});
            closeModal(); calendar.refetchEvents();
        } catch (error) { Swal.fire({icon:'error', title:error.message}); }
    });
    document.querySelectorAll('[data-close-modal]').forEach(button => button.addEventListener('click', closeModal));
    modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
    coach.addEventListener('change', () => calendar.refetchEvents());
    calendar.render();
});
</script>
@endsection
