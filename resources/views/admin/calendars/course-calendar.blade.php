@extends('layouts.app')

@section('title', 'ตารางสอนคอร์ส')

@section('content')
<div class="min-h-screen bg-slate-50 py-8 text-gray-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium text-orange-500">THATA HOMECOURT / ADMIN</p>
                <h1 class="mt-1 text-2xl font-semibold text-gray-800">ตารางสอนคอร์ส</h1>
                <p class="mt-1 text-sm text-gray-500">ดูคลาสทั้งหมด แยกตามวัน เวลา และโค้ชผู้สอน</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <span class="whitespace-nowrap">แสดงโค้ช</span>
                    <select id="coach-filter" class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-200">
                        <option value="all">โค้ชทุกคน</option>
                        <option value="โค้ชต้น">โค้ชต้น</option>
                        <option value="โค้ชฟ้า">โค้ชฟ้า</option>
                        <option value="โค้ชบี">โค้ชบี</option>
                    </select>
                </label>
                <button id="new-class-button" type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    เพิ่มคลาสทดลอง
                </button>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[260px_minmax(0,1fr)]">
            <aside class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm xl:h-fit">
                <h2 class="text-sm font-semibold text-gray-800">ภาพรวมสัปดาห์นี้</h2>
                <div class="mt-4 grid grid-cols-3 gap-2 xl:grid-cols-1">
                    <div class="rounded-lg bg-blue-50 px-3 py-3"><p class="text-xs text-blue-600">คลาสทั้งหมด</p><p id="total-classes" class="mt-1 text-xl font-semibold text-blue-700">0</p></div>
                    <div class="rounded-lg bg-orange-50 px-3 py-3"><p class="text-xs text-orange-600">จำนวนโค้ช</p><p id="total-coaches" class="mt-1 text-xl font-semibold text-orange-700">0</p></div>
                    <div class="rounded-lg bg-green-50 px-3 py-3"><p class="text-xs text-green-600">ชั่วโมงสอน</p><p id="total-hours" class="mt-1 text-xl font-semibold text-green-700">0</p></div>
                </div>

                <div class="mt-6 border-t border-gray-100 pt-5">
                    <h2 class="text-sm font-semibold text-gray-800">สีของคลาส</h2>
                    <div class="mt-3 space-y-2.5 text-sm text-gray-600">
                        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-blue-500"></span>Rookie / Beginner</div>
                        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-violet-500"></span>Junior</div>
                        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-orange-500"></span>Private training</div>
                    </div>
                </div>

                <div class="mt-6 rounded-lg border border-orange-100 bg-orange-50 p-3 text-xs leading-5 text-orange-800">
                    <b>ตัวอย่าง Frontend:</b> ลากคลาสเพื่อเปลี่ยนเวลา หรือคลิกช่วงเวลาว่างเพื่อเพิ่มคลาสทดลองได้ ข้อมูลจะรีเฟรชเมื่อเปิดหน้าใหม่ เพราะยังไม่เชื่อมฐานข้อมูล
                </div>
            </aside>

            <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                <div id="course-calendar"></div>
            </section>
        </div>
    </div>
</div>

{{-- FullCalendar Standard bundle (timeGrid, dayGrid, drag/drop and list view) --}}
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.21/locales-all.global.min.js"></script>

<div id="class-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-4">
            <div><h2 id="modal-title" class="text-lg font-semibold text-gray-800">เพิ่มคลาสทดลอง</h2><p id="selected-time" class="mt-1 text-sm text-gray-500"></p></div>
            <button id="close-modal" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700" type="button" aria-label="ปิด">✕</button>
        </div>
        <form id="class-form" class="mt-5 space-y-4">
            <div><label class="mb-1.5 block text-sm font-medium text-gray-700">ชื่อคลาส</label><input id="class-name" required value="คลาสใหม่" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-200"></div>
            <div><label class="mb-1.5 block text-sm font-medium text-gray-700">โค้ชผู้สอน</label><select id="class-coach" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-200"><option>โค้ชต้น</option><option>โค้ชฟ้า</option><option>โค้ชบี</option></select></div>
            <div class="flex justify-end gap-2 pt-2"><button id="cancel-modal" type="button" class="rounded-lg px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100">ยกเลิก</button><button type="submit" class="rounded-lg bg-orange-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-orange-600">เพิ่มลงปฏิทิน</button></div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('course-calendar');
    const filter = document.getElementById('coach-filter');
    const modal = document.getElementById('class-modal');
    const selectedTime = document.getElementById('selected-time');
    let selectedRange = null;

    let allEvents = [
        { id: '1', title: 'Basketball Rookie Class', start: '2026-07-13T16:00:00', end: '2026-07-13T17:30:00', backgroundColor: '#3b82f6', borderColor: '#3b82f6', extendedProps: { coach: 'โค้ชต้น', level: 'Rookie', capacity: 'รับ 10 คน' } },
        { id: '2', title: 'Junior Class', start: '2026-07-14T16:00:00', end: '2026-07-14T17:30:00', backgroundColor: '#8b5cf6', borderColor: '#8b5cf6', extendedProps: { coach: 'โค้ชฟ้า', level: 'Junior', capacity: 'รับ 12 คน' } },
        { id: '3', title: 'Private Training', start: '2026-07-15T18:00:00', end: '2026-07-15T19:00:00', backgroundColor: '#f97316', borderColor: '#f97316', extendedProps: { coach: 'โค้ชบี', level: 'Private', capacity: 'ตัวต่อตัว' } },
        { id: '4', title: 'Basketball Rookie Class', start: '2026-07-16T16:00:00', end: '2026-07-16T17:30:00', backgroundColor: '#3b82f6', borderColor: '#3b82f6', extendedProps: { coach: 'โค้ชต้น', level: 'Rookie', capacity: 'รับ 10 คน' } },
        { id: '5', title: 'Junior Class', start: '2026-07-18T10:00:00', end: '2026-07-18T11:30:00', backgroundColor: '#8b5cf6', borderColor: '#8b5cf6', extendedProps: { coach: 'โค้ชฟ้า', level: 'Junior', capacity: 'รับ 12 คน' } },
        { id: '6', title: 'Private Training', start: '2026-07-19T13:00:00', end: '2026-07-19T14:00:00', backgroundColor: '#f97316', borderColor: '#f97316', extendedProps: { coach: 'โค้ชบี', level: 'Private', capacity: 'ตัวต่อตัว' } }
    ];

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek', initialDate: '2026-07-13', locale: 'th', firstDay: 1, height: 'auto', nowIndicator: true,
        selectable: true, editable: true, allDaySlot: false, slotMinTime: '08:00:00', slotMaxTime: '21:00:00', slotDuration: '00:30:00',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
        buttonText: { today: 'วันนี้', month: 'เดือน', week: 'สัปดาห์', day: 'วัน', list: 'รายการ' },
        events: allEvents,
        eventClick: function (info) { showEventDetails(info.event); },
        select: function (info) { openModal(info); },
        eventChange: function (info) { syncEvent(info.event); },
        datesSet: function () { updateSummary(); }
    });

    calendar.render(); updateSummary();

    filter.addEventListener('change', function () {
        calendar.removeAllEvents();
        const visibleEvents = this.value === 'all' ? allEvents : allEvents.filter(event => event.extendedProps.coach === this.value);
        calendar.addEventSource(visibleEvents); updateSummary();
    });

    document.getElementById('new-class-button').addEventListener('click', function () {
        const start = new Date(calendar.getDate()); start.setHours(16, 0, 0, 0);
        const end = new Date(start); end.setHours(1 + start.getHours());
        openModal({ start: start, end: end, allDay: false });
    });
    document.getElementById('close-modal').addEventListener('click', closeModal);
    document.getElementById('cancel-modal').addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) { if (event.target === modal) closeModal(); });

    document.getElementById('class-form').addEventListener('submit', function (event) {
        event.preventDefault();
        const coach = document.getElementById('class-coach').value;
        const palette = coach === 'โค้ชฟ้า' ? '#8b5cf6' : coach === 'โค้ชบี' ? '#f97316' : '#3b82f6';
        const newEvent = { id: Date.now().toString(), title: document.getElementById('class-name').value, start: selectedRange.start, end: selectedRange.end, backgroundColor: palette, borderColor: palette, extendedProps: { coach: coach, level: 'คลาสทดลอง', capacity: 'ยังไม่กำหนด' } };
        allEvents.push(newEvent);
        if (filter.value === 'all' || filter.value === coach) calendar.addEvent(newEvent);
        closeModal(); updateSummary();
    });

    function openModal(range) {
        selectedRange = range;
        selectedTime.textContent = formatRange(range.start, range.end);
        modal.classList.remove('hidden'); modal.classList.add('flex');
        document.getElementById('class-name').focus();
        calendar.unselect();
    }
    function closeModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); selectedRange = null; }
    function formatRange(start, end) { return start.toLocaleDateString('th-TH', { weekday: 'long', day: 'numeric', month: 'short' }) + ' · ' + start.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }) + '–' + end.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }); }
    function showEventDetails(event) { alert(event.title + '\n' + formatRange(event.start, event.end) + '\nโค้ชผู้สอน: ' + event.extendedProps.coach + '\n' + event.extendedProps.capacity); }
    function syncEvent(event) { const index = allEvents.findIndex(item => item.id === event.id); if (index !== -1) allEvents[index] = { id: event.id, title: event.title, start: event.start, end: event.end, backgroundColor: event.backgroundColor, borderColor: event.borderColor, extendedProps: event.extendedProps }; updateSummary(); }
    function updateSummary() { const current = calendar.getEvents(); document.getElementById('total-classes').textContent = current.length; document.getElementById('total-coaches').textContent = new Set(current.map(event => event.extendedProps.coach)).size; document.getElementById('total-hours').textContent = current.reduce((sum, event) => sum + ((event.end - event.start) / 3600000), 0).toFixed(1); }
});
</script>
@endsection
