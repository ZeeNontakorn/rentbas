@extends('layouts.app')

@section('title', 'จองเทรนเนอร์ส่วนตัว: ' . $coach->name)

@section('content')
@include('private-training._calendar-theme')
<div class="min-h-screen bg-slate-50 py-8 text-gray-900">
    <div class="container mx-auto max-w-6xl px-4 sm:px-6">
        <a href="{{ route('private-training.index') }}"
            class="mb-6 inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 transition hover:text-orange-500">
            ← กลับไปหน้ารายชื่อโค้ช
        </a>

        <section class="mb-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                <div class="flex h-20 w-20 flex-shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-orange-100 shadow-sm">
                    @if($staffProfile?->profile_image_url)
                        <img src="{{ $staffProfile->profile_image_url }}" alt="รูปโปรไฟล์ของ {{ $coach->name }}"
                            class="h-full w-full object-cover">
                    @else
                        <span class="text-3xl font-bold text-orange-600">{{ mb_strtoupper(mb_substr($coach->name, 0, 1)) }}</span>
                    @endif
                </div>
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-800">{{ $coach->name }}</h1>
                        <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">ผู้ฝึกสอน (Coach)</span>
                    </div>
                    <div class="mt-4 grid gap-2 text-sm text-gray-600 md:grid-cols-2">
                        <p>อีเมล: {{ $coach->email }}</p>
                        <p>เบอร์โทรศัพท์: {{ $coach->phone ?? 'ไม่ระบุ' }}</p>
                        <p><strong>ความเชี่ยวชาญ:</strong> {{ $staffProfile?->specialty ?? 'ไม่ระบุ' }}</p>
                        <p><strong>แนะนำตัว:</strong> {{ $staffProfile?->bio ?? 'ไม่มีข้อมูล' }}</p>
                    </div>
                </div>
            </div>
        </section>

        @if($myUpcoming->isNotEmpty())
            <section class="mb-6 rounded-2xl border border-blue-100 bg-blue-50 p-5">
                <h2 class="mb-2 text-sm font-bold text-blue-700">คำขอของคุณกับโค้ชคนนี้</h2>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach($myUpcoming as $booking)
                        @php
                            $isPast = \Illuminate\Support\Carbon::parse($booking->date->format('Y-m-d') . ' ' . $booking->end_time)->isPast();
                        @endphp
                        <div class="rounded-lg bg-white/80 px-3 py-2 text-sm text-blue-800">
                            {{ $booking->date->format('d/m/Y') }}
                            {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }} น.
                            <span class="ml-1 text-xs font-semibold {{ $isPast ? 'text-gray-400' : '' }}">
                                {{ $isPast
                                    ? 'เลยกำหนด'
                                    : match($booking->status) {
                                        'confirmed' => 'ยืนยันแล้ว',
                                        'awaiting_court' => 'รอจัดสนาม',
                                        default => 'รออนุมัติ',
                                    } }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-6">
            <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">เลือกวันและเวลาที่โค้ชว่าง</h2>
                    <p class="mt-1 text-xs text-gray-500">ลากเลือกช่วงเวลาในปฏิทินรายสัปดาห์ เวลา 08:00–22:00 น.</p>
                </div>
                <div class="flex flex-wrap gap-3 text-xs text-gray-600">
                    <span><i class="mr-1 inline-block h-3 w-3 rounded bg-blue-500"></i>คำขอของคุณ</span>
                    <span><i class="mr-1 inline-block h-3 w-3 rounded bg-slate-400"></i>ไม่ว่าง</span>
                    <span><i class="mr-1 inline-block h-3 w-3 rounded bg-green-600"></i>ยืนยันแล้ว</span>
                </div>
            </div>
            <div id="no-available-schedule"
                class="mb-4 hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                โค้ชยังไม่ได้เปิดช่วงเวลารับจอง กรุณารอผู้ดูแลระบบกำหนดตารางก่อน
            </div>
            <div id="private-calendar" class="private-calendar-theme"></div>
        </section>
    </div>
</div>

<style>
    /* เปิดให้กล่องไฮไลต์ (ช่วงเวลาที่ค้างรอยืนยัน) รับคลิกได้ ปกติ FullCalendar ปิดไว้ (pointer-events: none) */
    #private-calendar .fc-highlight {
        pointer-events: auto;
        cursor: pointer;
        z-index: 5;
    }
</style>

<div id="bookTrainingModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-md overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-2xl">
        <div class="border-b border-gray-100 bg-gray-50 px-6 py-4">
            <h3 class="font-bold text-gray-800">ยืนยันคำขอ Private Training</h3>
        </div>
        <form action="{{ route('private-training.store') }}" method="POST" class="space-y-4 p-6">
            @csrf
            <input type="hidden" name="coach_id" value="{{ $coach->id }}">
            <input type="hidden" name="date" id="booking-date">
            <input type="hidden" name="start_time" id="booking-start">
            <input type="hidden" name="end_time" id="booking-end">
            <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-center">
                <p class="text-xs font-semibold text-blue-500">โค้ช {{ $coach->name }}</p>
                <p id="booking-date-label" class="mt-1 font-bold text-blue-800"></p>
                <p id="booking-time-label" class="text-lg font-extrabold text-blue-700"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-gray-700">เลือกแพ็กเกจที่ต้องการใช้ <span class="text-red-500">*</span></label>
                <select name="package_purchase_id" id="package-purchase-select" required
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- กรุณาเลือกแพ็กเกจ --</option>
                    @foreach ($myPackagePurchases as $pp)
                        <option value="{{ $pp->id }}">
                            {{ $pp->package->name }} (เหลือ {{ $pp->remaining_use }} ครั้ง{{ $pp->expired_at ? ' · หมดอายุ ' . $pp->expired_at->format('d/m/Y') : '' }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-gray-400">ระบบจะหักสิทธิ์จากแพ็กเกจที่คุณเลือกทันทีที่ส่งคำขอ</p>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-gray-700">รายละเอียดที่ต้องการฝึก (ถ้ามี)</label>
                <textarea name="note" rows="3" maxlength="500" placeholder="เช่น ฝึกการยิงสามแต้ม"
                    class="w-full resize-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <p class="text-xs text-gray-500">ราคาและการหักเครดิตจะเกิดขึ้นตอนแอดมินจัดสนามให้เท่านั้น (ยังไม่หักตอนส่งคำขอ)</p>
            <div class="flex gap-2 pt-2">
                <button type="button" id="cancel-booking-modal"
                    class="w-1/2 rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-200">ยกเลิก</button>
                <button type="submit"
                    class="w-1/2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">ส่งคำขอ</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.21/locales-all.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('bookTrainingModal');
    const calendarEl = document.getElementById('private-calendar');
    const pad = value => String(value).padStart(2, '0');
    const localDate = date => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const localTime = date => `${pad(date.getHours())}:${pad(date.getMinutes())}`;

    const maxDurationMs = 5 * 60 * 60 * 1000; // ลากได้สูงสุด 5 ชั่วโมง

    let pendingSelection = null;
    let needsConfirmClick = false;
    let isProgrammaticSelect = false;

    const programmaticSelect = (start, end) => {
        isProgrammaticSelect = true;
        calendar.select(start, end);
        isProgrammaticSelect = false;
    };

    const openBookingModal = (start, end) => {
        document.getElementById('booking-date').value = localDate(start);
        document.getElementById('booking-start').value = localTime(start);
        document.getElementById('booking-end').value = localTime(end);
        document.getElementById('booking-date-label').textContent =
            start.toLocaleDateString('th-TH', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        document.getElementById('booking-time-label').textContent =
            `${localTime(start)}–${localTime(end)} น.`;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        pendingSelection = null;
        needsConfirmClick = false;
        calendar.unselect();
    };

    // เช็คว่าแพ็กเกจโปรโมชั่นแต่ละตัวใช้ได้กับช่วงเวลาที่ลูกค้าเพิ่งลากเลือกหรือไม่ (เฉพาะเงื่อนไข
    // ระยะเวลา — duration_hours) แล้ว disable ตัวเลือกที่ไม่เข้าเงื่อนไข พร้อมโชว์คำอธิบายไว้ในตัวเลือก
    // (เงื่อนไขอื่น เช่น เต็ม/ครึ่งสนาม, วันที่ จะถูกเช็คจริงจังอีกทีตอนแอดมินจัดสนามให้)

    // จองล่วงหน้าได้สูงสุด {{ \App\Http\Controllers\CheckoutController::ADVANCE_BOOKING_DAYS }} วัน
    // (การบังคับจริงอยู่ที่ server ใน PrivateTrainingController::store() อันนี้แค่กันผู้ใช้
    // เลือกช่วงเวลาที่เกินมาแล้วเจอ error ตอน submit ให้เห็นตั้งแต่ตอนลากเลือกเลย)

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        locale: 'th',
        firstDay: 1,
        height: 'auto',
        nowIndicator: true,
        selectable: true,
        selectMirror: true,
        unselectAuto: false,

        eventClassNames(arg) {
            // ทำให้ช่วงเวลาที่ว่างแต่ผ่านไปแล้วดูจางลง ไม่ให้เข้าใจผิดว่ายังจองได้
            if (arg.event.extendedProps.kind === 'available' && arg.event.end <= new Date()) {
                return ['opacity-40'];
            }
            return [];
        },

        eventDidMount(arg) {
            const props = arg.event.extendedProps;
            const isPastAvailable = props.kind === 'available' && arg.event.end <= new Date();
            if (!isPastAvailable) return;

            arg.el.style.backgroundColor = '#e5e7eb';

            const label = document.createElement('div');
            label.textContent = 'เลยกำหนด';
            label.style.cssText = `
                position: absolute;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 11px;
                font-weight: 600;
                color: #6b7280;
                pointer-events: none;
            `;
            arg.el.appendChild(label);
        },

        selectOverlap(event) {
            return event.extendedProps.kind === 'available';
        },
        allDaySlot: false,
        slotMinTime: '08:00:00',
        slotMaxTime: '22:00:00',
        slotDuration: '00:30:00',
        snapDuration: '00:30:00',

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'timeGridWeek,timeGridDay'
        },
        buttonText: { today: 'วันนี้', week: 'สัปดาห์', day: 'วัน' },
        events: @js(route('private-training.schedule', $coach)),
        eventSourceSuccess(events) {
            const hasAvailableSchedule = events.some(event =>
                event.extendedProps?.kind === 'available'
            );
            document.getElementById('no-available-schedule')
                .classList.toggle('hidden', hasAvailableSchedule);
            return events;
        },
        selectAllow(info) {
            const insideAvailableSchedule = calendar.getEvents().some(event =>
                event.extendedProps.kind === 'available'
                && info.start >= event.start
                && info.end <= event.end
            );
            // ไม่เช็คระยะเวลาตรงนี้ เพื่อให้กล่องไฮไลต์โตต่อได้ตอนลาก ไม่หายไปกลางทาง
            return insideAvailableSchedule && info.start >= new Date();
        },
        select(info) {
            if (isProgrammaticSelect) {
                pendingSelection = { start: info.start, end: info.end };
                return;
            }

            const start = info.start;
            const end = info.end;
            const duration = end - start;

            if (duration > maxDurationMs) {
                const clampedEnd = new Date(start.getTime() + maxDurationMs);
                pendingSelection = { start, end: clampedEnd };
                needsConfirmClick = false;
                programmaticSelect(start, clampedEnd);
                return;
            }

            pendingSelection = { start, end };
            needsConfirmClick = false;
            openBookingModal(start, end);
        },
        dateClick(info) {
            if (!pendingSelection) {
                return;
            }

            const clickedTime = info.date;
            const selectionStart = pendingSelection.start;
            const selectionEnd = pendingSelection.end;

            if (clickedTime >= selectionStart && clickedTime < selectionEnd) {
                if (needsConfirmClick) {
                    needsConfirmClick = false;
                    openBookingModal(selectionStart, selectionEnd);
                } else {
                    needsConfirmClick = true;
                }
            }
        },
        eventClick(info) {
            const props = info.event.extendedProps;

            if (props.kind === 'available') {
                const isPast = info.event.end <= new Date();
                Swal.fire({
                    title: isPast ? 'เลยกำหนด' : 'เปิดรับจอง',
                    text: isPast
                        ? 'ช่วงเวลานี้ผ่านไปแล้ว ไม่สามารถจองได้'
                        : 'ลากเลือกช่วงเวลานี้เพื่อทำการจอง',
                    icon: 'info'
                });
                return;
            }

            const isPast = info.event.end <= new Date();
            const statusLabel = isPast ? 'เลยกำหนด' : props.statusLabel;

            Swal.fire({
                title: info.event.title,
                text: [statusLabel, props.court].filter(Boolean).join(' · ') || 'ช่วงเวลานี้ไม่ว่าง',
                icon: 'info'
            });
        }
    });

    calendar.render();

    calendarEl.addEventListener('click', (event) => {
        if (!pendingSelection) return;

        const target = event.target;
        const interactiveTarget = target.closest('.fc-timegrid-slot, .fc-highlight, .fc-timegrid-bg, .fc-timegrid-col');
        if (!interactiveTarget) return;

        if (needsConfirmClick) {
            event.stopImmediatePropagation();
            event.preventDefault();
            needsConfirmClick = false;
            openBookingModal(pendingSelection.start, pendingSelection.end);
        } else {
            needsConfirmClick = true;
        }
    }, true);

    document.getElementById('cancel-booking-modal').addEventListener('click', closeModal);
    modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });

    @if(session('success'))
        Swal.fire({ icon: 'success', title: @js(session('success')), timer: 2500, showConfirmButton: false });
    @endif
    @if($errors->any())
        Swal.fire({ icon: 'error', title: 'ส่งคำขอไม่สำเร็จ', text: @js($errors->first()) });
    @endif
});
</script>
@endsection
