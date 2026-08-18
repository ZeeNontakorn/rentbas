@extends('layouts.app')

@section('title', 'จองเทรนเนอร์ส่วนตัว: ' . $coach->us_name)

@section('content')
@include('private-training._calendar-theme')
<div class="min-h-screen bg-slate-50 py-8 text-gray-900">
    <div class="container mx-auto px-4 sm:px-6 max-w-7xl">
        <a href="{{ route('private-training.index') }}"
            class="mb-6 inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 transition hover:text-orange-500">
            ← กลับไปหน้ารายชื่อโค้ช
        </a>

        <section class="mb-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                <div class="flex h-20 w-20 flex-shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-orange-100 shadow-sm">
                    @if($staffProfile?->profile_image_url)
                        <img src="{{ $staffProfile->profile_image_url }}" alt="รูปโปรไฟล์ของ {{ $coach->us_name }}"
                            class="h-full w-full object-cover">
                    @else
                        <span class="text-3xl font-bold text-orange-600">{{ mb_strtoupper(mb_substr($coach->us_name, 0, 1)) }}</span>
                    @endif
                </div>
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-800">{{ $coach->us_name }}</h1>
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
                            @if($booking->courtAssistant)
                                <span class="ml-1 text-xs text-blue-600">· ผู้ช่วย {{ $booking->courtAssistant->us_name }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-6">
            <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">เลือกวันและเวลาฝึก</h2>
                    <p class="mt-1 text-xs text-gray-500">ลากเลือกพื้นที่ว่างในปฏิทิน เวลา 08:00–22:00 น. ช่วงที่มี Schedule หรือมีรายการจองแล้วจะเลือกไม่ได้</p>
                    <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">
                        <span aria-hidden="true">ⓘ</span> ดูตารางสัปดาห์หรือเดือนถัดไปได้ แต่จองล่วงหน้าได้สูงสุด {{ \App\Http\Controllers\CheckoutController::ADVANCE_BOOKING_DAYS }} วัน
                    </p>
                    @if($myPackagePurchases->isNotEmpty())
                        @php
                            $dayLabels = ['mon'=>'จ','tue'=>'อ','wed'=>'พ','thu'=>'พฤ','fri'=>'ศ','sat'=>'ส','sun'=>'อา'];
                            $hasRestrictedPackage = $myPackagePurchases->contains(fn ($pp) => !empty($pp->package->usable_days));
                        @endphp
                        @if($hasRestrictedPackage)
                            <div class="mt-2 rounded-xl bg-amber-50 px-3.5 py-2.5 text-xs text-amber-700 ring-1 ring-orange-200">
                                <p class="mb-1.5 flex items-center gap-1.5 font-semibold">
                                    <span aria-hidden="true">ⓘ</span> แพ็กเกจของคุณใช้ได้เฉพาะบางวัน กรุณาเลือกวันให้ตรงกับแพ็กเกจ
                                </p>
                                <ul class="space-y-0.5 pl-5">
                                    @foreach($myPackagePurchases as $pp)
                                        <li class="list-disc">
                                            <span class="font-medium">{{ $pp->package->name }}</span> —
                                            @if(empty($pp->package->usable_days))
                                                <span class="text-green-700">ใช้ได้ทุกวัน</span>
                                            @else
                                                ใช้ได้เฉพาะวัน
                                                <span class="font-semibold">{{ collect($pp->package->usable_days)->map(fn($d) => $dayLabels[$d] ?? $d)->implode(', ') }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif
                </div>
                <div class="flex flex-wrap gap-3 text-xs text-gray-600">
                    <span><i class="mr-1 inline-block h-3 w-3 rounded bg-blue-500"></i>รออนุมัติ</span>
                    <span><i class="mr-1 inline-block h-3 w-3 rounded bg-purple-600"></i>รอจัดสนาม</span>
                    <span><i class="mr-1 inline-block h-3 w-3 rounded bg-green-600"></i>ยืนยันแล้ว</span>
                    <span><i class="mr-1 inline-block h-3 w-3 rounded bg-slate-500"></i>โค้ชไม่ว่าง</span>
                </div>
            </div>
            <div id="private-calendar" class="private-calendar-theme"></div>
        </section>
    </div>
</div>

<style>
    #private-calendar-confirm-btn {
        position: absolute;
        z-index: 20;
        background: transparent;
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        padding: 4px 8px;
        cursor: pointer;
        white-space: nowrap;
        display: none;
        border: none;
        text-decoration: underline;
        text-underline-offset: 2px;
    }
    /* overlay "เลยกำหนด" (สีเทา) ให้อยู่เหนือแถบวันนี้ แต่ต่ำกว่า event การจอง */
    #private-calendar .fc-bg-event {
        z-index: 1 !important;
    }

    /* event การจองจริง (สถานะต่าง ๆ) ต้องอยู่หน้าสุดเสมอ ไม่ให้อะไรมาทับ */
    #private-calendar .fc-timegrid-event-harness {
        z-index: 3 !important;
    }
</style>

<div id="bookTrainingModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-md overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-2xl">
        <div class="border-b border-gray-100 bg-gray-50 px-6 py-4">
            <h3 class="font-bold text-gray-800">ยืนยันคำขอจองเทรนเนอร์ส่วนตัว</h3>
        </div>
        <form action="{{ route('private-training.store') }}" method="POST" class="space-y-4 p-6">
            @csrf
            <input type="hidden" name="coach_id" value="{{ $coach->id }}">
            <input type="hidden" name="date" id="booking-date">
            <input type="hidden" name="start_time" id="booking-start">
            <input type="hidden" name="end_time" id="booking-end">
            <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-center">
                <p class="text-xs font-semibold text-blue-500">โค้ช {{ $coach->us_name }}</p>
                <p id="booking-date-label" class="mt-1 font-bold text-blue-800"></p>
                <p id="booking-time-label" class="text-lg font-extrabold text-blue-700"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-gray-700">เลือกแพ็กเกจที่ต้องการใช้ <span class="text-red-500">*</span></label>
                <select name="package_purchase_id" id="package-purchase-select" required
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                    <option value="">-- กรุณาเลือกแพ็กเกจ --</option>
                    @foreach ($myPackagePurchases as $pp)
                        <option value="{{ $pp->id }}" data-days="{{ json_encode($pp->package->usable_days ?? []) }}">
                            {{ $pp->package->name }} (เหลือ {{ $pp->remaining_use }} ครั้ง{{ $pp->expired_at ? ' · หมดอายุ ' . $pp->expired_at->format('d/m/Y') : '' }})
                        </option>
                    @endforeach
                </select>
                <p id="package-day-warning" class="mt-1.5 hidden text-[11px] font-medium text-red-600">
                    ไม่มีแพ็กเกจของคุณที่ใช้ได้ในวันนี้ กรุณาเลือกวันอื่น หรือซื้อแพ็กเกจที่รองรับวันนี้
                </p>
                <p class="mt-1 text-[11px] text-gray-400">ระบบจะหักสิทธิ์จากแพ็กเกจที่คุณเลือกทันทีที่ส่งคำขอ</p>
            </div>
            <div>
                <label for="assistant-requested" class="mb-1.5 block text-xs font-semibold text-gray-700">บริการผู้ช่วยสนามเก็บบาส</label>
                <select name="assistant_requested" id="assistant-requested" required
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-100 cursor-pointer">
                    <option value="0">ไม่ต้องการ</option>
                    <option value="1">ต้องการผู้ช่วยสนาม</option>
                </select>
            </div>
            <div id="assistant-select-wrap" class="hidden rounded-xl border border-blue-100 bg-blue-50/70 p-4">
                <label for="court-assistant-select" class="mb-1.5 block text-xs font-semibold text-blue-800">เลือกผู้ช่วยที่ว่าง <span class="text-red-500">*</span></label>
                <select name="court_assistant_id" id="court-assistant-select"
                    class="w-full rounded-lg border border-blue-200 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    <option value="">กรุณาเลือกผู้ช่วยสนาม</option>
                </select>
                <p id="assistant-status" class="mt-2 text-[11px] text-blue-600">ระบบจะแสดงเฉพาะผู้ช่วยที่ว่างตรงกับเวลานี้</p>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-gray-700">รายละเอียดที่ต้องการฝึก (ถ้ามี)</label>
                <textarea name="note" rows="3" maxlength="500" placeholder="เช่น ฝึกการยิงสามแต้ม"
                    class="w-full resize-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <p class="text-xs text-gray-500">ระบบใช้สิทธิ์แพ็กเกจ 1 ครั้งตอนส่งคำขอ และจะไม่หักเครดิตเพิ่มตอนแอดมินจัดสนาม หากคำขอถูกยกเลิกหรือปฏิเสธ ระบบจะคืนสิทธิ์ให้</p>
            <div class="flex gap-2 pt-2">
                <button type="button" id="cancel-booking-modal"
                    class="w-1/2 rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-200 cursor-pointer">ยกเลิก</button>
                <button type="submit"
                    class="w-1/2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 cursor-pointer">ส่งคำขอ</button>
            </div>
        </form>
    </div>
</div>

@include('private-training._booking-details-script')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.21/locales-all.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('bookTrainingModal');
    const calendarEl = document.getElementById('private-calendar');
    const pad = value => String(value).padStart(2, '0');
    const localDate = date => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const localTime = date => `${pad(date.getHours())}:${pad(date.getMinutes())}`;
    const maxDurationMs = 5 * 60 * 60 * 1000;

    let pendingSelection = null;
    let isClampingSelection = false;

    const DAY_KEYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
    function filterPackageOptionsForDate(date) {
        const select = document.getElementById('package-purchase-select');
        const warning = document.getElementById('package-day-warning');
        const dayKey = DAY_KEYS[date.getDay()];
        let hasSelectable = false;

        Array.from(select.options).forEach(option => {
            if (!option.value) return; // ข้าม placeholder "-- กรุณาเลือกแพ็กเกจ --"
            let days = [];
            try { days = JSON.parse(option.dataset.days || '[]'); } catch (e) { days = []; }
            const usable = days.length === 0 || days.includes(dayKey); // ว่าง = ใช้ได้ทุกวัน
            option.hidden = !usable;
            option.disabled = !usable;
            if (usable) hasSelectable = true;
        });

        select.value = ''; // reset ทุกครั้งที่เปลี่ยนวัน กันเลือกค้างจากวันก่อนหน้า
        warning.classList.toggle('hidden', hasSelectable);
    }
    const openBookingModal = (start, end) => {
        document.getElementById('booking-date').value = localDate(start);
        document.getElementById('booking-start').value = localTime(start);
        document.getElementById('booking-end').value = localTime(end);
        document.getElementById('booking-date-label').textContent =
            start.toLocaleDateString('th-TH', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        document.getElementById('booking-time-label').textContent =
            `${localTime(start)}–${localTime(end)} น.`;
        filterPackageOptionsForDate(start); // <-- เพิ่มบรรทัดนี้
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const assistantRequested = document.getElementById('assistant-requested');
    const assistantWrap = document.getElementById('assistant-select-wrap');
    const assistantSelect = document.getElementById('court-assistant-select');
    const assistantStatus = document.getElementById('assistant-status');
    const assistantsApi = @js(route('private-training.available-assistants'));
    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        pendingSelection = null;
        calendar.unselect();
        hideConfirmButton();
    };

    async function loadAvailableAssistants() {
        if (assistantRequested.value !== '1') {
            assistantWrap.classList.add('hidden');
            assistantSelect.required = false;
            assistantSelect.value = '';
            return;
        }

        assistantWrap.classList.remove('hidden');
        assistantSelect.required = true;
        assistantSelect.disabled = true;
        assistantSelect.innerHTML = '<option value="">กำลังตรวจสอบผู้ช่วยที่ว่าง...</option>';
        assistantStatus.textContent = 'กำลังตรวจสอบ Schedule ของผู้ช่วยสนาม...';
        assistantStatus.className = 'mt-2 text-[11px] text-blue-600';

        const params = new URLSearchParams({
            date: document.getElementById('booking-date').value,
            start_time: document.getElementById('booking-start').value,
            end_time: document.getElementById('booking-end').value
        });

        try {
            const response = await fetch(`${assistantsApi}?${params}`, {headers: {'Accept':'application/json'}});
            if (!response.ok) throw new Error('โหลดรายชื่อผู้ช่วยไม่สำเร็จ');
            const result = await response.json();
            const assistants = result.assistants || [];
            assistantSelect.innerHTML = '<option value="">กรุณาเลือกผู้ช่วยสนาม</option>' + assistants
                .map(assistant => `<option value="${assistant.id}">${escapeHtml(assistant.name)}</option>`)
                .join('');
            assistantSelect.disabled = assistants.length === 0;
            assistantStatus.textContent = assistants.length
                ? `มีผู้ช่วยว่าง ${assistants.length} คน`
                : 'ไม่มีผู้ช่วยสนามว่างในช่วงเวลานี้ กรุณาเลือกไม่ใช้บริการหรือเปลี่ยนเวลา';
            assistantStatus.className = `mt-2 text-[11px] ${assistants.length ? 'text-blue-600' : 'font-medium text-red-600'}`;
        } catch (error) {
            assistantSelect.innerHTML = '<option value="">โหลดข้อมูลไม่สำเร็จ</option>';
            assistantStatus.textContent = error.message;
            assistantStatus.className = 'mt-2 text-[11px] font-medium text-red-600';
        }
    }

    function escapeHtml(value) {
        const element = document.createElement('div');
        element.textContent = value ?? '';
        return element.innerHTML;
    }

    // เช็คว่าแพ็กเกจโปรโมชั่นแต่ละตัวใช้ได้กับช่วงเวลาที่ลูกค้าเพิ่งลากเลือกหรือไม่ (เฉพาะเงื่อนไข
    // ระยะเวลา — duration_hours) แล้ว disable ตัวเลือกที่ไม่เข้าเงื่อนไข พร้อมโชว์คำอธิบายไว้ในตัวเลือก
    // (เงื่อนไขอื่น เช่น เต็ม/ครึ่งสนาม, วันที่ จะถูกเช็คจริงจังอีกทีตอนแอดมินจัดสนามให้)

    // จองล่วงหน้าได้สูงสุด {{ \App\Http\Controllers\CheckoutController::ADVANCE_BOOKING_DAYS }} วัน
    // (การบังคับจริงอยู่ที่ server ใน PrivateTrainingController::store() อันนี้แค่กันผู้ใช้
    // เลือกช่วงเวลาที่เกินมาแล้วเจอ error ตอน submit ให้เห็นตั้งแต่ตอนลากเลือกเลย)
    const maxSelectableDate = new Date(@js($maxDate) + 'T23:59:59');
    const advanceLimitMessage = @js('สามารถจอง Private Training ล่วงหน้าได้สูงสุด '.\App\Http\Controllers\CheckoutController::ADVANCE_BOOKING_DAYS.' วัน');

    function showAdvanceLimitMessage() {
        Swal.fire({
            icon: 'info',
            title: 'วันที่นี้ยังจองไม่ได้',
            text: advanceLimitMessage,
            confirmButtonText: 'เข้าใจแล้ว',
            confirmButtonColor: '#f97316'
        });
    }

    function showUnavailableDetails(event) {
        const props = event.extendedProps || {};
        const dateLabel = event.start
            ? event.start.toLocaleDateString('th-TH', {weekday:'long', day:'numeric', month:'long', year:'numeric'})
            : '—';
        const timeLabel = event.start
            ? `${localTime(event.start)}${event.end ? `–${localTime(event.end)}` : ''} น.`
            : '—';

        Swal.fire({
            icon: 'info',
            title: 'ช่วงเวลานี้จองไม่ได้',
            html: `
                <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-left">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div><p class="text-xs text-slate-400">วันที่</p><p class="mt-1 font-semibold text-slate-700">${escapeHtml(dateLabel)}</p></div>
                        <div><p class="text-xs text-slate-400">เวลา</p><p class="mt-1 font-semibold text-slate-700">${escapeHtml(timeLabel)}</p></div>
                    </div>
                    <div class="mt-3 rounded-xl bg-slate-200/70 px-3 py-2.5 text-sm font-medium text-slate-700">
                        ${escapeHtml(props.unavailableReason || 'โค้ชไม่ว่างในช่วงเวลานี้')}
                    </div>
                </div>`,
            confirmButtonText: 'ปิด',
            confirmButtonColor: '#64748b'
        });
    }

    const openHour = 8, closeHour = 22;
    const scheduleUrl = @js(route('private-training.schedule', $coach));
    function buildPastOverlay(rangeStart, rangeEnd) {
        const overlay = [];
        const now = new Date();
        const cursor = new Date(rangeStart);
        cursor.setHours(0, 0, 0, 0);
        while (cursor < rangeEnd) {
            const dayOpen = new Date(cursor);
            dayOpen.setHours(openHour, 0, 0, 0);
            const dayClose = new Date(cursor);
            dayClose.setHours(closeHour, 0, 0, 0);
            // ครอบเฉพาะช่วงที่ผ่านมาแล้วจริง ๆ ของวันนั้น (ไม่เกิน "ตอนนี้" และไม่เกินเวลาปิด)
            const overlayEnd = now < dayClose ? now : dayClose;
            if (overlayEnd > dayOpen) {
                overlay.push({
                    start: dayOpen.toISOString(),
                    end: overlayEnd.toISOString(),
                    display: 'background',
                    backgroundColor: '#e5e7eb',
                    extendedProps: { kind: 'past', selectable: false }
                });
            }
            cursor.setDate(cursor.getDate() + 1);
        }
        return overlay;
    }

    const calendarWrap = calendarEl.parentElement;
        calendarWrap.style.position = calendarWrap.style.position || 'relative';
        const confirmBtn = document.createElement('button');
        confirmBtn.type = 'button';
        confirmBtn.id = 'private-calendar-confirm-btn';
        calendarWrap.appendChild(confirmBtn);
        function hideConfirmButton() {
            confirmBtn.style.display = 'none';
        }

        // ซ่อนปุ่มทันทีเมื่อเริ่มลากใหม่ (mousedown/touchstart ในพื้นที่ปฏิทิน)
        // กันปุ่มจากการเลือกครั้งก่อนค้างอยู่ตำแหน่งเดิมระหว่างลากคอลัมน์ใหม่
        // ไม่กระทบปุ่มเอง เพราะ confirmBtn อยู่นอก calendarEl (เป็น sibling ใน calendarWrap)
        calendarEl.addEventListener('mousedown', hideConfirmButton);
        calendarEl.addEventListener('touchstart', hideConfirmButton, { passive: true });

        function positionConfirmButton(text) {
            requestAnimationFrame(() => {
                const highlight = document.querySelector('#private-calendar .fc-highlight');
                if (!highlight) {
                    confirmBtn.style.display = 'none';
                    return;
                }
                const rect = highlight.getBoundingClientRect();
                const wrapRect = calendarWrap.getBoundingClientRect();
                confirmBtn.textContent = text;

                const durationMs = pendingSelection ? (pendingSelection.end - pendingSelection.start) : 0;
                const isLong = durationMs > 60 * 60 * 1000; // เกิน 1 ชม.

                if (isLong) {
                    // กึ่งกลาง highlight
                    confirmBtn.style.left = `${rect.left - wrapRect.left + rect.width / 2}px`;
                    confirmBtn.style.top = `${rect.top - wrapRect.top + rect.height / 2}px`;
                    confirmBtn.style.transform = 'translate(-50%, -50%)';
                } else {
                    // ชิดขวาล่างของ highlight
                    confirmBtn.style.left = `${rect.right - wrapRect.left}px`;
                    confirmBtn.style.top = `${rect.bottom - wrapRect.top}px`;
                    confirmBtn.style.transform = 'translate(-100%, -100%)';
                }

                confirmBtn.style.display = 'block';
            });
        }

    function hideConfirmButton() {
        confirmBtn.style.display = 'none';
    }

    confirmBtn.addEventListener('click', (event) => {
        event.stopPropagation();
        if (!pendingSelection) return;
        openBookingModal(pendingSelection.start, pendingSelection.end);
    });
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
            if (arg.event.extendedProps.kind === 'past' && arg.event.end <= new Date()) {
                return ['opacity-40'];
            }
            return [];
        },

        eventDidMount(arg) {
            const props = arg.event.extendedProps;
            const isPastAvailable = props.kind === 'past' && arg.event.end <= new Date();
            if (arg.event.extendedProps.kind !== 'past') return;

            arg.el.style.backgroundColor = 'rgba(148, 163, 184, 0.65)';
            arg.el.style.opacity = '0.4';


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

        selectOverlap: false,
        allDaySlot: false,
        slotMinTime: '08:00:00',
        slotMaxTime: '22:00:00',
        slotDuration: '00:30:00',
        snapDuration: '00:30:00',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: { today: 'วันนี้', month: 'เดือน', week: 'สัปดาห์', day: 'วัน' },
        dayMaxEvents: true,
        eventMaxStack: 3,
        slotEventOverlap: false,

        events: function (fetchInfo, successCallback, failureCallback) {
            const params = new URLSearchParams({ start: fetchInfo.startStr, end: fetchInfo.endStr });
            fetch(`${scheduleUrl}?${params}`, { headers: { Accept: 'application/json' } })
                .then(res => res.json())
                .then(realEvents => {
                    successCallback([...realEvents, ...buildPastOverlay(fetchInfo.start, fetchInfo.end)]);
                })
                .catch(failureCallback);
        },
        selectAllow(info) {
            // FullCalendar ให้ลากข้ามคอลัมน์วันได้ จึงตรวจปลายช่วงแบบ exclusive
            // (ลบ 1 ms) เพื่อยืนยันว่าช่วงที่เลือกทั้งหมดอยู่ภายในวันเดียวกัน
            const inclusiveEnd = new Date(info.end.getTime() - 1);
            return calendar.view.type !== 'dayGridMonth'
                && info.start >= new Date()
                && info.start <= maxSelectableDate
                && localDate(info.start) === localDate(inclusiveEnd)
        },
        dateClick(info) {
            if (info.date > maxSelectableDate) {
                showAdvanceLimitMessage();
                return;
            }

            if (info.view.type === 'dayGridMonth') {
                calendar.changeView('timeGridDay', info.date);
                return;
            }
        },
        select(info) {
            if (isClampingSelection) return;

            let start = info.start;
            let end = info.end;
            let wasClamped = false;

            if (end - start > maxDurationMs) {
                end = new Date(start.getTime() + maxDurationMs);
                wasClamped = true;

                isClampingSelection = true;
                calendar.unselect();
                calendar.select(start, end);
                isClampingSelection = false;
            }

            pendingSelection = { start, end };

            positionConfirmButton(wasClamped ? 'คลิกเพื่อจอง' : 'คลิกเพื่อจอง');

            if (wasClamped) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: 'จองได้สูงสุด 5 ชั่วโมงต่อครั้ง',
                    text: 'ระบบปรับช่วงเวลาให้ไม่เกิน 5 ชม. อัตโนมัติแล้ว',
                    timer: 2500,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            }
        },
        eventClick(info) {
            const props = info.event.extendedProps;

            if (props.isMine) {
                showPrivateTrainingDetails(info.event);
                return;
            }

            if (props.kind === 'past') {
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

            showUnavailableDetails(info.event);
        }
    });

    calendar.render();



    document.getElementById('cancel-booking-modal').addEventListener('click', closeModal);
    assistantRequested.addEventListener('change', loadAvailableAssistants);
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
