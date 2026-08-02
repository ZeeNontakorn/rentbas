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

                        @if($ratingCount)
                            <span class="inline-flex items-center gap-1.5 text-sm text-gray-500">
                                @include('reviews._stars', ['score' => $ratingAvg, 'size' => 'h-4 w-4'])
                                <span class="font-semibold text-gray-700">{{ number_format($ratingAvg, 1) }}</span>
                                <span class="text-xs">({{ $ratingCount }} รีวิว)</span>
                            </span>
                        @else
                            <span class="text-xs text-gray-400">ยังไม่มีรีวิว</span>
                        @endif
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
                        <div class="rounded-lg bg-white/80 px-3 py-2 text-sm text-blue-800">
                            {{ $booking->date->format('d/m/Y') }}
                            {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }} น.
                            <span class="ml-1 text-xs font-semibold">
                                {{ match($booking->status) {
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

        {{-- รีวิวจากลูกค้าที่เคยเรียนกับโค้ชคนนี้ --}}
        @if($reviews->isNotEmpty())
            <section class="mb-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-bold text-gray-800">รีวิวจากผู้เรียน ({{ $ratingCount }})</h2>
                <div class="divide-y divide-gray-100">
                    @foreach($reviews as $review)
                        <div class="py-3 first:pt-0 last:pb-0">
                            <div class="flex flex-wrap items-center gap-2">
                                @include('reviews._stars', ['score' => $review->averageScore(), 'size' => 'h-4 w-4'])
                                <span class="text-sm font-medium text-gray-700">{{ $review->user->name ?? 'ผู้ใช้' }}</span>
                                <span class="text-xs text-gray-400">{{ $review->created_at->format('d/m/Y') }}</span>
                            </div>
                            @if($review->comment)
                                <p class="mt-1.5 text-sm text-gray-600">{{ $review->comment }}</p>
                            @endif
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
                <label class="mb-1.5 block text-xs font-semibold text-gray-700">แพ็กเกจโปรโมชั่น (ถ้ามี)</label>
                <select name="promotion_code" id="promotion-code-select" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">ไม่ใช้โปรโมชั่น (คิดราคาตามช่วงเวลาปกติ)</option>
                    @foreach ($promotionPackages as $p)
                        <option value="{{ $p->code }}" data-duration-hours="{{ $p->duration_hours ?? '' }}">
                            {{ $p->label }}{{ $p->duration_hours !== null ? " ({$p->duration_hours} ชม.)" : '' }}
                        </option>
                    @endforeach
                </select>
                <p id="promotion-hint" class="mt-1 text-[11px] text-gray-400">ระบบจะเช็คเงื่อนไข (เต็ม/ครึ่งสนาม, ระยะเวลา, วันที่) อีกครั้งตอนแอดมินจัดสนามให้ ถ้าใช้ไม่ได้จะแจ้งแอดมินให้ทราบ</p>
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
    const pad = value => String(value).padStart(2, '0');
    const localDate = date => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const localTime = date => `${pad(date.getHours())}:${pad(date.getMinutes())}`;
    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        calendar.unselect();
    };

    // เช็คว่าแพ็กเกจโปรโมชั่นแต่ละตัวใช้ได้กับช่วงเวลาที่ลูกค้าเพิ่งลากเลือกหรือไม่ (เฉพาะเงื่อนไข
    // ระยะเวลา — duration_hours) แล้ว disable ตัวเลือกที่ไม่เข้าเงื่อนไข พร้อมโชว์คำอธิบายไว้ในตัวเลือก
    // (เงื่อนไขอื่น เช่น เต็ม/ครึ่งสนาม, วันที่ จะถูกเช็คจริงจังอีกทีตอนแอดมินจัดสนามให้)
    const promotionSelect = document.getElementById('promotion-code-select');
    const promotionHint = document.getElementById('promotion-hint');

    function updatePromotionAvailability(start, end) {
        if (!promotionSelect) return;
        const selectedHours = (end - start) / (1000 * 60 * 60);
        let selectedBecameInvalid = false;

        Array.from(promotionSelect.options).forEach(option => {
            if (!option.value) return; // ตัวเลือก "ไม่ใช้โปรโมชั่น" ใช้ได้เสมอ

            const durationAttr = option.dataset.durationHours;
            const requiredHours = durationAttr ? parseFloat(durationAttr) : null;
            const matches = requiredHours === null || Math.abs(requiredHours - selectedHours) < 0.01;

            option.disabled = !matches;
            option.classList.toggle('text-gray-300', !matches);

            if (!matches && option.selected) {
                selectedBecameInvalid = true;
            }
        });

        if (selectedBecameInvalid) {
            promotionSelect.value = '';
        }

        promotionHint.textContent = `ช่วงเวลาที่เลือก ${selectedHours} ชม. — แพ็กเกจที่ระยะเวลาไม่ตรงจะถูกปิดใช้งานให้อัตโนมัติ (ระบบจะเช็คเงื่อนไขอื่น เช่น เต็ม/ครึ่งสนาม, วันที่ อีกครั้งตอนแอดมินจัดสนามให้)`;
    }

    // จองล่วงหน้าได้สูงสุด {{ \App\Http\Controllers\CheckoutController::ADVANCE_BOOKING_DAYS }} วัน
    // (การบังคับจริงอยู่ที่ server ใน PrivateTrainingController::store() อันนี้แค่กันผู้ใช้
    // เลือกช่วงเวลาที่เกินมาแล้วเจอ error ตอน submit ให้เห็นตั้งแต่ตอนลากเลือกเลย)
    const maxSelectableDate = new Date(@js($maxDate) + 'T23:59:59');

    const calendar = new FullCalendar.Calendar(document.getElementById('private-calendar'), {
        initialView: 'timeGridWeek',
        locale: 'th',
        firstDay: 1,
        height: 'auto',
        nowIndicator: true,
        selectable: true,
        selectMirror: true,
        selectOverlap(event) {
            return event.extendedProps.kind === 'available';
        },
        allDaySlot: false,
        slotMinTime: '08:00:00',
        slotMaxTime: '22:00:00',
        slotDuration: '00:30:00',
        snapDuration: '00:30:00',
        validRange: { start: @js($today), end: @js(\Carbon\Carbon::parse($maxDate)->addDay()->toDateString()) },
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
            return insideAvailableSchedule
                && info.start >= new Date()
                && info.start <= maxSelectableDate
                && (info.end - info.start) <= 4 * 60 * 60 * 1000;
        },
        select(info) {
            document.getElementById('booking-date').value = localDate(info.start);
            document.getElementById('booking-start').value = localTime(info.start);
            document.getElementById('booking-end').value = localTime(info.end);
            document.getElementById('booking-date-label').textContent =
                info.start.toLocaleDateString('th-TH', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            document.getElementById('booking-time-label').textContent =
                `${localTime(info.start)}–${localTime(info.end)} น.`;
            updatePromotionAvailability(info.start, info.end);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        },
        eventClick(info) {
            const props = info.event.extendedProps;
            Swal.fire({
                title: info.event.title,
                text: [props.statusLabel, props.court].filter(Boolean).join(' · ') || 'ช่วงเวลานี้ไม่ว่าง',
                icon: 'info'
            });
        }
    });

    calendar.render();
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
