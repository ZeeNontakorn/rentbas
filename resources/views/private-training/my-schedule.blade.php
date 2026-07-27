@extends('layouts.app')

@section('title', 'ตาราง Private Training ของฉัน')

@section('content')
@include('private-training._calendar-theme')
<div class="min-h-screen bg-slate-50 py-8 text-slate-900">
    <div class="container mx-auto max-w-6xl px-4 sm:px-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">Private Schedule ของฉัน</h1>
            <p class="mt-1 text-sm text-gray-500">ตารางคำขอและรายการ Private Training ของโค้ช {{ $coach->name }}</p>
        </div>
        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
            <div class="mb-4 flex flex-wrap gap-4 text-xs text-gray-600">
                <span><i class="mr-1 inline-block h-3 w-3 rounded bg-orange-500"></i>รออนุมัติ</span>
                <span><i class="mr-1 inline-block h-3 w-3 rounded bg-purple-600"></i>รอจัดสนาม</span>
                <span><i class="mr-1 inline-block h-3 w-3 rounded bg-green-600"></i>ยืนยันแล้ว</span>
            </div>
            <div id="coach-private-calendar" class="private-calendar-theme"></div>
        </section>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.21/locales-all.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendar = new FullCalendar.Calendar(document.getElementById('coach-private-calendar'), {
        initialView: 'timeGridWeek',
        locale: 'th',
        firstDay: 1,
        height: 'auto',
        nowIndicator: true,
        allDaySlot: false,
        slotMinTime: '08:00:00',
        slotMaxTime: '22:00:00',
        slotDuration: '00:30:00',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'timeGridWeek,timeGridDay,listWeek' },
        buttonText: { today: 'วันนี้', week: 'สัปดาห์', day: 'วัน', list: 'รายการ' },
        events: @js(route('private-training.schedule', $coach)),
        eventClick(info) {
            const props = info.event.extendedProps;
            Swal.fire({
                title: info.event.title,
                text: [props.statusLabel, props.court].filter(Boolean).join(' · ') || 'ไม่ว่าง',
                icon: 'info'
            });
        }
    });
    calendar.render();
});
</script>
@endsection
