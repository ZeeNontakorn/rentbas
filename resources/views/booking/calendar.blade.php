@extends('layouts.app')

@section('title', 'Court Booking - Calendar')

@section('content')
<div class="bg-white min-h-screen text-[#111827]">

<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');
.bk-main { font-family: 'Sarabun', 'Kanit', sans-serif; }
.bk-main h1, .bk-main h2, .bk-main h3 { font-family: 'Kanit', sans-serif; }

.court-item {
    padding: 8px 12px;
    cursor: pointer;
    transition: background 0.2s;
    font-family: 'Kanit', sans-serif;
}
.court-item:hover { background: #f9fafb; }
.court-item.active { background: #fff7ed; font-weight: 600; }

.view-switch {
    display: inline-flex; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;
    font-family: 'Kanit', sans-serif; font-size: 12px; font-weight: 600;
}
.view-switch a { padding: 7px 14px; color: #6b7280; background: #fff; }
.view-switch a.active { background: #0b0b1a; color: #fff; }

/* ===== Calendar (Google Calendar-style day view) ===== */
.cal-shell { border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; background: #fff; }
.cal-head { display: flex; border-bottom: 1px solid #e5e7eb; background: #fff; position: sticky; top: 0; z-index: 6; }
.cal-head-spacer { width: 56px; flex-shrink: 0; }
.cal-head-lane {
    flex: 1; text-align: center; font-family: 'Kanit', sans-serif; font-weight: 700;
    font-size: 13px; color: #1f2937; padding: 10px 4px; border-left: 1px solid #f1f3f5;
}
.cal-body { display: flex; max-height: 620px; overflow-y: auto; position: relative; }
.cal-axis { width: 56px; flex-shrink: 0; position: relative; }
.cal-axis-label {
    position: absolute; right: 8px; transform: translateY(-50%);
    font-size: 11px; color: #9ca3af; font-family: 'Kanit', sans-serif;
}
.cal-lanes { flex: 1; display: flex; position: relative; }
.cal-lane {
    flex: 1; position: relative; border-left: 1px solid #f1f3f5;
    background-image: repeating-linear-gradient(to bottom, #f1f3f5 0, #f1f3f5 1px, transparent 1px, transparent 60px);
    touch-action: none; cursor: crosshair;
    user-select: none;
}
.cal-now-line { position: absolute; left: 0; right: 0; height: 2px; background: #ef4444; z-index: 4; pointer-events: none; }
.cal-now-line::before {
    content: ''; position: absolute; left: -4px; top: -3px; width: 8px; height: 8px;
    background: #ef4444; border-radius: 50%;
}

.cal-block {
    position: absolute; left: 2px; right: 2px; border-radius: 6px; overflow: hidden;
    font-family: 'Kanit', sans-serif; font-size: 10.5px; font-weight: 600; padding: 3px 6px;
    line-height: 1.3; pointer-events: none;
}
.cal-block.st-approved { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.cal-block.st-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.cal-block.st-pending_payment { background: #ffedd5; color: #9a3412; border: 1px solid #fdba74; }
.cal-block.st-closed { background: repeating-linear-gradient(45deg,#f3f4f6,#f3f4f6 6px,#e5e7eb 6px,#e5e7eb 12px); color: #9ca3af; border: 1px solid #e5e7eb; }
.cal-block.st-past { background: #f9fafb; color: #d1d5db; border: 1px solid #f1f3f5; }
.cal-block.st-crossblocked {
    background: #f3f4f6; color: #9ca3af; border: 1px dashed #d1d5db;
    display: flex; align-items: center; justify-content: center; text-align: center;
}

.cal-drag {
    position: absolute; left: 2px; right: 2px; border-radius: 6px;
    background: rgba(135,208,104,0.35); border: 2px dashed #87D068;
    font-family: 'Kanit', sans-serif; font-size: 11px; font-weight: 700; color: #2f6b1a;
    display: flex; align-items: center; justify-content: center; text-align: center; padding: 4px;
    pointer-events: none; z-index: 3;
}

.cal-selected {
    position: absolute; left: 2px; right: 2px; border-radius: 6px;
    background: #87D068; border: 1px solid #6aa952; color: #fff; z-index: 3;
    font-family: 'Kanit', sans-serif; padding: 4px 6px; display: flex; flex-direction: column;
    justify-content: space-between; box-shadow: 0 2px 6px rgba(0,0,0,0.12);
}
.cal-selected .cal-sel-time { font-size: 11px; font-weight: 700; }
.cal-selected .cal-sel-remove {
    align-self: flex-end; font-size: 10px; font-weight: 700; background: rgba(255,255,255,0.25);
    border-radius: 4px; padding: 1px 6px; cursor: pointer;
}

.cal-legend { display: flex; gap: 16px; flex-wrap: wrap; font-size: 11px; color: #6b7280; font-family: 'Kanit', sans-serif; }
.cal-legend span { display: inline-flex; align-items: center; gap: 5px; }
.cal-legend i { width: 12px; height: 12px; border-radius: 3px; display: inline-block; }
</style>

<div class="bk-main max-w-[1200px] mx-auto px-4 py-8" data-aos="fade-up">

    {{-- Header --}}
    <div class="mb-6 flex items-baseline justify-between flex-wrap gap-4" data-aos="fade-right">
        <div class="flex items-baseline gap-4">
            <h1 class="text-[32px] font-bold text-gray-900 tracking-tight">Court Booking</h1>
            <span class="text-gray-600 text-[15px]">จองสนามแบบปฏิทิน — ลากเลือกช่วงเวลาได้เลย</span>
        </div>
        <div class="view-switch">
            <a href="{{ route('booking.index', ['court_id' => $selectedCourt?->id, 'date' => $date]) }}">ตาราง (Grid)</a>
            <a href="{{ route('booking.calendar', ['court_id' => $selectedCourt?->id, 'date' => $date]) }}" class="active">ปฏิทิน (Calendar)</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">
            @foreach ($errors->all() as $err)
                <div>• {{ $err }}</div>
            @endforeach
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-6">

        {{-- LEFT COLUMN --}}
        <div class="w-full lg:w-[280px] flex-shrink-0 flex flex-col gap-6">

            {{-- BOX 1: เลือกสนาม --}}
            <div class="relative z-50 border border-gray-300 rounded-lg p-5 flex items-center justify-between">
                <span class="font-bold text-[15px] text-gray-900">1. เลือกสนาม</span>
                <div class="relative w-[120px]">
                    <div class="border border-gray-400 rounded px-3 py-1 flex items-center justify-between cursor-pointer bg-white text-[13px]"
                         onclick="document.getElementById('courtList').classList.toggle('hidden')">
                        <span class="truncate">{{ $selectedCourt->name ?? 'เลือก' }}</span>
                        <svg class="w-3.5 h-3.5 text-gray-500 ml-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    <div id="courtList" class="hidden absolute top-full mt-1 left-0 w-full max-h-[380px] overflow-y-auto overflow-x-hidden bg-white border border-gray-200 rounded-md shadow-xl z-50 flex flex-col">
                        @foreach($courts as $court)
                            <a href="{{ route('booking.calendar', ['court_id' => $court->id, 'date' => $date]) }}"
                               class="flex items-center gap-2 px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50 border-b border-gray-50 last:border-0 truncate {{ $selectedCourt?->id == $court->id ? 'font-bold bg-gray-50 text-[#87D068]' : '' }}">
                                <span class="inline-block w-2.5 h-2.5 rounded-full flex-shrink-0 {{ ($court->court_status ?? 'open') === 'open' ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                <span class="truncate">{{ $court->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- BOX 2: เลือกวันที่ --}}
            @php
                $cDate = \Carbon\Carbon::parse($date);
                $thDays = ['อาทิตย์','จันทร์','อังคาร','พุธ','พฤหัสบดี','ศุกร์','เสาร์'];
                $thMonths = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
                $thMonthsFull = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
            @endphp
            <div class="border border-gray-300 rounded-lg p-5 flex flex-col">
                <span class="font-bold text-[15px] text-gray-900 mb-5">2. เลือกวันที่</span>
                <div class="relative w-full rounded border border-purple-400 p-[1px] mb-2 focus-within:border-purple-600 focus-within:ring-1 focus-within:ring-purple-600">
                    <span class="absolute -top-2.5 left-2 bg-white px-1 text-[10px] font-bold text-purple-600 tracking-wider">Date</span>
                    <form id="dateForm" method="GET" action="{{ route('booking.calendar') }}">
                        <input type="hidden" name="court_id" value="{{ $selectedCourt?->id }}">
                        <input type="date" name="date" id="dateInput" value="{{ $date }}"
                               min="{{ now()->toDateString() }}" max="{{ now()->addMonth()->toDateString() }}"
                               class="w-full text-sm text-gray-700 p-2 outline-none bg-transparent">
                    </form>
                </div>
                <div class="bg-[#f8f9fe] rounded-lg mt-3 p-3 text-center border border-gray-100 flex-1">
                    <p class="font-bold text-[14px] text-gray-900 mt-2">เลือก {{ $thDays[$cDate->dayOfWeek] }} ที่ {{ $cDate->day }} {{ $thMonthsFull[$cDate->month] }} {{ $cDate->year + 543 }}</p>
                    <p class="text-[12px] text-gray-500 mt-2">จองได้ล่วงหน้าได้สูงสุด 1 เดือน</p>
                </div>
            </div>

            {{-- Legend --}}
            @if($matrix)
            <div class="border border-gray-300 rounded-lg p-5">
                <span class="font-bold text-[13px] text-gray-900 block mb-3">คำอธิบายสี</span>
                <div class="cal-legend">
                    <span><i style="background:#fff;border:1px solid #d1d5db;"></i>ว่าง (ลาก/แตะเพื่อเลือก)</span>
                    <span><i style="background:#87D068;"></i>ที่เลือกไว้</span>
                    <span><i style="background:#fef3c7;border:1px solid #fde68a;"></i>รออนุมัติ</span>
                    <span><i style="background:#fee2e2;border:1px solid #fecaca;"></i>จองแล้ว</span>
                    <span><i style="background:#f3f4f6;border:1px solid #e5e7eb;"></i>ปิด/ผ่านมาแล้ว</span>
                </div>
            </div>
            @endif
        </div>

        {{-- RIGHT COLUMN --}}
        <div class="flex-1 flex flex-col gap-6">

            <div class="border border-gray-300 rounded-lg p-6 bg-white">
                <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                    <span class="font-bold text-[15px] text-gray-900">3. เลือกเวลา - {{ $cDate->day }} {{ $thMonthsFull[$cDate->month] }} {{ $cDate->year + 543 }}</span>
                    <span class="font-bold text-[14px] text-gray-900">เวลาปัจจุบัน <span id="currentClock">{{ now()->format('H:i') }}</span> น.</span>
                </div>

                @if(!$selectedCourt || !$matrix || empty($matrix['sections']))
                    <div class="text-center py-20 text-gray-400 font-medium">{{ $selectedCourt ? 'สนามนี้ยังไม่เปิดให้จอง' : 'กรุณาเลือกสนาม' }}</div>
                @else
                    <p class="text-[12px] text-gray-500 mb-3">ลากขึ้น/ลงในแต่ละคอลัมน์เพื่อเลือกช่วงเวลา หรือแตะครั้งเดียวเพื่อเลือกช่วงขั้นต่ำ {{ $matrix['minBookingMinutes'] }} นาที (ปัดตามช่วง {{ $matrix['intervalMinutes'] }} นาที)</p>

                    <div class="cal-shell">
                        <div class="cal-head">
                            <div class="cal-head-spacer"></div>
                            @foreach($matrix['sections'] as $sec)
                                <div class="cal-head-lane">{{ $sec['name'] }}</div>
                            @endforeach
                        </div>
                        <div class="cal-body" id="calBody">
                            <div class="cal-axis" id="calAxis"></div>
                            <div class="cal-lanes" id="calLanes">
                                @foreach($matrix['sections'] as $sec)
                                    <div class="cal-lane" data-section-id="{{ $sec['id'] }}" data-section-name="{{ $sec['name'] }}" id="lane-{{ $sec['id'] }}"></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- BOX 4: ยืนยันการจอง --}}
            <div id="confirmBox" class="hidden border border-gray-300 rounded-lg p-6 bg-white">
                <span class="font-bold text-[15px] text-gray-900 block mb-6">4. ตรวจสอบรายละเอียดการจอง</span>
                <div class="flex flex-col gap-3">
                    <div class="flex gap-4 sm:gap-8 text-[14px] text-gray-900 flex-wrap">
                        <p>วันที่ <span class="font-bold">{{ $cDate->day }} {{ $thMonths[$cDate->month] }}. {{ $cDate->year }}</span></p>
                        <p>สนามที่ <span class="font-bold">{{ str_replace('สนามที่ ', '', $selectedCourt?->name) }}</span></p>
                    </div>
                    <div id="confirmList" class="flex flex-col gap-2 text-[14px] text-gray-900 mt-2"></div>
                    <div class="flex justify-end mt-2">
                        <button type="button" onclick="submitBooking()" class="bg-[#87D068] hover:bg-[#76bc5a] text-white font-bold py-2.5 px-8 rounded-lg shadow transition">
                            ยืนยัน
                        </button>
                    </div>
                </div>
                <div class="mt-8 pt-4 border-t border-gray-100 text-center text-xs text-gray-500">
                    กรุณาตรวจสอบ 'วันที่' และ 'เวลา' ของทุกรายการอีกครั้งก่อนกดยืนยัน
                </div>
            </div>
        </div>
    </div>
</div>

{{-- SUCCESS MODAL --}}
@if(session('success_booking'))
    @php
        $sbList = session('success_booking');
        $sbList = isset($sbList['court_name']) ? [$sbList] : $sbList;
    @endphp
    <div class="modal-bg" id="successModalBg" onclick="closeSuccessModal(event)" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:1000;display:flex;align-items:center;justify-content:center;padding:24px 16px;overflow-y:auto;">
        <div class="modal-content relative" onclick="event.stopPropagation()" style="background:#fff;width:100%;max-width:400px;max-height:calc(100vh - 48px);overflow-y:auto;border-radius:12px;padding:30px 24px;text-align:center;box-shadow:0 20px 40px rgba(0,0,0,0.2);">
            <div class="w-16 h-16 mx-auto bg-green-50 rounded-full flex items-center justify-center border-4 border-[#87D068] mb-4">
                <svg class="w-8 h-8 text-[#87D068]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h2 class="text-2xl font-bold text-[#87D068] mb-6 tracking-wide" style="font-family:'Kanit',sans-serif;">รอแอดมินอนุมัติ</h2>
            <div class="text-left border border-gray-200 rounded-lg p-5 mb-6">
                <p class="font-bold text-gray-900 mb-3 text-[15px]">รายละเอียดการจอง ({{ count($sbList) }} รายการ)</p>
                @foreach($sbList as $sb)
                    <div class="mb-3 pb-3 border-b border-gray-100 last:border-0 last:mb-0 last:pb-0">
                        <div class="flex justify-between py-1 text-sm"><span class="text-gray-500">สนาม</span><span class="text-gray-900 text-right">{{ $sb['court_name'] }}</span></div>
                        <div class="flex justify-between py-1 text-sm"><span class="text-gray-500">วันที่</span><span class="text-gray-900 text-right">{{ $sb['date'] }}</span></div>
                        <div class="flex justify-between py-1 text-sm"><span class="text-gray-500">เวลา</span><span class="text-gray-900 text-right">{{ $sb['time'] }}</span></div>
                        <div class="flex justify-between py-1 text-sm"><span class="text-gray-500">สถานะ</span><span class="font-bold text-gray-900 text-right">{{ $sb['status'] }}</span></div>
                    </div>
                @endforeach
            </div>
            <div class="flex gap-4">
                <a href="{{ route('booking.calendar') }}" class="flex-1 py-3 border border-gray-300 rounded-lg font-bold text-gray-700 text-center hover:bg-gray-50">จองเพิ่ม</a>
                <a href="{{ route('history') }}" class="flex-1 py-3 bg-[#0b0b1a] rounded-lg font-bold text-white text-center hover:bg-[#1a1a2e]">ดูประวัติการจอง</a>
            </div>
        </div>
    </div>
@endif

</div>

@push('scripts')
<script>
@if($matrix && $selectedCourt)
const PX_PER_MIN = 1; // 60px ต่อชั่วโมง
const OPEN = timeToMin('{{ $matrix['openTime'] }}');
const CLOSE = timeToMin('{{ $matrix['closeTime'] }}');
const INTERVAL = {{ $matrix['intervalMinutes'] }};
const MIN_MINUTES = {{ $matrix['minBookingMinutes'] }};
const ROWS = @json($mappedRows);
const TODAY_STR = '{{ now()->toDateString() }}';
const PAGE_DATE = '{{ $date }}';
// รหัส section (full / a / b ...) ต่อ id — ใช้เช็คว่า section ไหนเป็น "เต็มสนาม"
const SECTION_CODES = @json(collect($matrix['sections'])->pluck('code', 'id'));

function timeToMin(t) {
    const [h, m] = t.split(':').map(Number);
    return h * 60 + m;
}
function minToLabel(m) {
    const h = Math.floor(m / 60), mm = m % 60;
    return String(h).padStart(2, '0') + ':' + String(mm).padStart(2, '0');
}
function statusAt(sectionId, minute) {
    const row = ROWS.find(r => timeToMin(r.start) <= minute && minute < timeToMin(r.end));
    return row ? row.sections[sectionId] : 'closed';
}
// หาขอบเขตช่วงว่างต่อเนื่องที่ครอบ anchorMin อยู่ (ใช้ clamp ตอนลาก)
function availableRunBounds(sectionId, anchorMin) {
    let lo = anchorMin, hi = anchorMin + INTERVAL;
    while (lo - INTERVAL >= OPEN && effectiveStatusAt(sectionId, lo - INTERVAL) === 'available') lo -= INTERVAL;
    while (hi < CLOSE && effectiveStatusAt(sectionId, hi) === 'available') hi += INTERVAL;
    return { lo, hi };
}

// ช่วงเวลา (นาที) ที่ section นี้ถูก "บล็อกชั่วคราว" เพราะมีการเลือก (ยังไม่ยืนยัน) อยู่ใน
// section ที่ตัดกันอยู่ — เต็มสนามบล็อกครึ่งสนามทุกอัน และครึ่งสนามก็บล็อกเต็มสนามกลับ
// (ครึ่ง A กับครึ่ง B ไม่บล็อกกันเอง เหมือน logic ฝั่ง backend)
function crossBlockingRanges(sectionId) {
    const iAmFull = SECTION_CODES[sectionId] === 'full';
    return selections
        .filter(s => {
            const selIsFull = SECTION_CODES[s.sectionId] === 'full';
            return iAmFull ? !selIsFull : selIsFull;
        })
        .map(s => [timeToMin(s.start), timeToMin(s.end)]);
}

// เหมือน statusAt() แต่รวมผลของการเลือกค้างอยู่ (cross full/half) เข้าไปด้วย
// ใช้แทน statusAt() ทุกจุดที่ตัดสินใจว่าลากได้/ไม่ได้ เพื่อกันไม่ให้เลือกทับข้ามฝั่งได้ก่อน submit
function effectiveStatusAt(sectionId, minute) {
    const base = statusAt(sectionId, minute);
    if (base !== 'available') return base;
    const blocked = crossBlockingRanges(sectionId).some(([s, e]) => minute >= s && minute < e);
    return blocked ? 'crossblocked' : 'available';
}

function isRangeAvailable(sectionId, startMin, endMin) {
    for (let m = startMin; m < endMin; m += INTERVAL) {
        if (effectiveStatusAt(sectionId, m) !== 'available') return false;
    }
    return true;
}

let selections = []; // {sectionId, sectionName, start, end, label, blockEl}

// ---- Render time axis + hour label ----
const axis = document.getElementById('calAxis');
const totalHeight = (CLOSE - OPEN) * PX_PER_MIN;
document.getElementById('calBody').style.setProperty('--h', totalHeight + 'px');
for (let m = OPEN; m <= CLOSE; m += 60) {
    const lbl = document.createElement('div');
    lbl.className = 'cal-axis-label';
    lbl.style.top = ((m - OPEN) * PX_PER_MIN) + 'px';
    lbl.textContent = minToLabel(m);
    axis.appendChild(lbl);
}
axis.style.height = totalHeight + 'px';

// ---- Render lanes: background blocks for non-available rows, wire up drag ----
document.querySelectorAll('.cal-lane').forEach(lane => {
    lane.style.height = totalHeight + 'px';
    const sectionId = lane.dataset.sectionId;

    // group consecutive rows with the same non-available status into single blocks
    let i = 0;
    while (i < ROWS.length) {
        const st = ROWS[i].sections[sectionId];
        if (st === 'available') { i++; continue; }
        let j = i;
        while (j + 1 < ROWS.length && ROWS[j + 1].sections[sectionId] === st) j++;
        const top = (timeToMin(ROWS[i].start) - OPEN) * PX_PER_MIN;
        const height = (timeToMin(ROWS[j].end) - timeToMin(ROWS[i].start)) * PX_PER_MIN;
        const div = document.createElement('div');
        const labelMap = { pending: 'รออนุมัติ', approved: 'จองแล้ว', closed: 'ปิดบริการ', past: 'ผ่านมาแล้ว' };
        div.className = 'cal-block st-' + st;
        div.style.top = top + 'px';
        div.style.height = Math.max(height, 16) + 'px';
        if (height >= 22) div.textContent = st === 'pending_payment' ? 'กำลังจอง' : (labelMap[st] || '');
        lane.appendChild(div);
        i = j + 1;
    }

    // now-line if viewing today
    if (PAGE_DATE === TODAY_STR) {
        const nowMin = timeToMin('{{ now()->format('H:i') }}');
        if (nowMin > OPEN && nowMin < CLOSE) {
            const line = document.createElement('div');
            line.className = 'cal-now-line';
            line.style.top = ((nowMin - OPEN) * PX_PER_MIN) + 'px';
            lane.appendChild(line);
        }
    }

    setupDrag(lane, sectionId);
});

function yToMinutes(lane, clientY) {
    const rect = lane.getBoundingClientRect();
    const y = Math.min(Math.max(clientY - rect.top, 0), totalHeight);
    return OPEN + (y / PX_PER_MIN);
}
function snapDown(m) { return OPEN + Math.floor((m - OPEN) / INTERVAL) * INTERVAL; }
function snapUp(m) { return OPEN + Math.ceil((m - OPEN) / INTERVAL) * INTERVAL; }

function setupDrag(lane, sectionId) {
    let dragging = false;
    let anchorMin = null;
    let ghost = null;
    let moved = false;
    let runBounds = null;

    lane.addEventListener('pointerdown', (e) => {
        if (e.target !== lane) return; // อย่าเริ่มลากถ้ากดโดนบล็อกที่จองไว้แล้ว/selected block
        const rawMin = yToMinutes(lane, e.clientY);
        const startCandidate = snapDown(rawMin);
        if (effectiveStatusAt(sectionId, startCandidate) !== 'available') return;

        dragging = true;
        moved = false;
        anchorMin = startCandidate;
        runBounds = availableRunBounds(sectionId, anchorMin);

        ghost = document.createElement('div');
        ghost.className = 'cal-drag';
        lane.appendChild(ghost);
        renderGhost(anchorMin, anchorMin + INTERVAL);
        lane.setPointerCapture(e.pointerId);
    });

    lane.addEventListener('pointermove', (e) => {
        if (!dragging) return;
        moved = true;
        const rawMin = yToMinutes(lane, e.clientY);
        let start, end;
        if (rawMin >= anchorMin) {
            start = anchorMin;
            end = Math.min(snapUp(Math.max(rawMin, anchorMin + INTERVAL)), runBounds.hi);
        } else {
            end = anchorMin + INTERVAL;
            start = Math.max(snapDown(rawMin), runBounds.lo);
        }
        renderGhost(start, end);
    });

    function finishDrag(e) {
        if (!dragging) return;
        dragging = false;
        let start = parseInt(ghost.dataset.start, 10);
        let end = parseInt(ghost.dataset.end, 10);

        // แตะครั้งเดียว (ไม่ลาก) → ใช้ระยะเวลาขั้นต่ำของสนามแทน 1 ช่วง interval เดียว
        if (!moved && (end - start) < MIN_MINUTES) {
            end = Math.min(start + MIN_MINUTES, runBounds.hi);
            start = Math.max(end - MIN_MINUTES, runBounds.lo);
        }

        ghost.remove();
        ghost = null;

        if ((end - start) < MIN_MINUTES || !isRangeAvailable(sectionId, start, end)) {
            // เลือกไม่ได้ (พื้นที่ว่างสั้นกว่าขั้นต่ำ) — ไม่ทำอะไรต่อ
            return;
        }

        addSelection(sectionId, lane.dataset.sectionName, start, end, lane);
    }

    lane.addEventListener('pointerup', finishDrag);
    lane.addEventListener('pointercancel', finishDrag);

    function renderGhost(start, end) {
        ghost.style.top = ((start - OPEN) * PX_PER_MIN) + 'px';
        ghost.style.height = ((end - start) * PX_PER_MIN) + 'px';
        ghost.textContent = minToLabel(start) + ' - ' + minToLabel(end);
        ghost.dataset.start = start;
        ghost.dataset.end = end;
    }
}

function addSelection(sectionId, sectionName, start, end, lane) {
    // ถ้าช่วงที่เพิ่งลากทับซ้อน (หรือชนต่อกันพอดี) กับรายการที่เลือกไว้แล้วใน section
    // เดียวกัน ให้ "รวม" เป็นช่วงเดียวแทนที่จะสร้างเป็นรายการที่ซ้อนกัน 2 รายการ
    // (วนซ้ำเผื่อรวมแล้วไปทับกับรายการอื่นต่อเป็นทอดๆ เช่น เดิมมี 2 ช่วง แล้วลากช่วงใหม่คาบทั้งคู่)
    let merged = true;
    while (merged) {
        merged = false;
        for (let i = selections.length - 1; i >= 0; i--) {
            const s = selections[i];
            if (s.sectionId !== sectionId) continue;

            const sStart = timeToMin(s.start), sEnd = timeToMin(s.end);
            if (sStart <= end && sEnd >= start) {
                start = Math.min(start, sStart);
                end = Math.max(end, sEnd);
                s.blockEl.remove();
                selections.splice(i, 1);
                merged = true;
            }
        }
    }

    const block = document.createElement('div');
    block.className = 'cal-selected';
    block.style.top = ((start - OPEN) * PX_PER_MIN) + 'px';
    block.style.height = ((end - start) * PX_PER_MIN) + 'px';
    block.innerHTML = `<span class="cal-sel-time">${minToLabel(start)}-${minToLabel(end)}</span><span class="cal-sel-remove">ลบ</span>`;
    lane.appendChild(block);

    const entry = {
        sectionId, sectionName,
        start: minToLabel(start), end: minToLabel(end),
        label: minToLabel(start) + ' - ' + minToLabel(end),
        blockEl: block,
    };
    selections.push(entry);

    block.querySelector('.cal-sel-remove').addEventListener('click', (ev) => {
        ev.stopPropagation();
        block.remove();
        selections = selections.filter(s => s !== entry);
        updateConfirmBox();
    });

    updateConfirmBox();
}

function updateConfirmBox() {
    renderCrossBlocks();
    const box = document.getElementById('confirmBox');
    const list = document.getElementById('confirmList');
    if (selections.length === 0) { box.classList.add('hidden'); list.innerHTML = ''; return; }
    box.classList.remove('hidden');
    list.innerHTML = selections.map((s, idx) => `
        <div class="flex justify-between items-center border-b border-gray-50 pb-2">
            <span class="text-gray-500"><span class="text-gray-400">(${s.sectionName})</span> เวลา <span class="font-bold text-gray-900">${s.label} น.</span></span>
            <button type="button" onclick="removeSelectionByIndex(${idx})" class="text-red-400 hover:text-red-600 text-xs font-bold ml-3">ลบ</button>
        </div>`).join('');
}

// วาด/ล้าง overlay สีเทาทับฝั่งตรงข้าม (เต็มสนาม <-> ครึ่งสนาม) ตามที่กำลังเลือกอยู่ตอนนี้
// เรียกทุกครั้งที่ selections เปลี่ยน (เพิ่ม/ลบ) ให้ผลลัพธ์ตรงกับ effectiveStatusAt() เสมอ
function renderCrossBlocks() {
    document.querySelectorAll('.cal-lane').forEach(lane => {
        lane.querySelectorAll('.cal-block.st-crossblocked').forEach(el => el.remove());

        const sectionId = lane.dataset.sectionId;
        crossBlockingRanges(sectionId).forEach(([s, e]) => {
            const div = document.createElement('div');
            div.className = 'cal-block st-crossblocked';
            div.style.top = ((s - OPEN) * PX_PER_MIN) + 'px';
            div.style.height = Math.max((e - s) * PX_PER_MIN, 16) + 'px';
            if ((e - s) * PX_PER_MIN >= 22) div.textContent = 'ไม่ว่าง';
            lane.appendChild(div);
        });
    });
}
function removeSelectionByIndex(idx) {
    const s = selections[idx];
    if (s) { s.blockEl.remove(); selections.splice(idx, 1); updateConfirmBox(); }
}

function submitBooking() {
    if (selections.length === 0) return;

    // ระบบชำระเงิน (checkout.reserve) ยังรองรับการจอง "ทีละ 1 ช่วงเวลา" ต่อการชำระเงิน 1 ครั้ง
    // (คำนวณราคา/ล็อกสล็อต 15 นาที/หักเครดิตเป็นรายการเดียว) ถ้าเลือกไว้หลายช่วง ให้ผู้ใช้
    // ยืนยันทีละรายการก่อน — ไม่งั้นราคา/การล็อกจะไม่ตรงกับที่ตั้งใจไว้
    if (selections.length > 1) {
        alert('ตอนนี้ระบบชำระเงินรองรับการจองทีละ 1 ช่วงเวลาต่อการชำระเงิน 1 ครั้ง กรุณาลบรายการที่เลือกไว้ให้เหลือ 1 รายการ แล้วกดยืนยันอีกครั้ง');
        return;
    }

    const only = selections[0];
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = "{{ route('checkout.reserve') }}";

    const csrf = document.createElement('input');
    csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);

    const fields = {
        court_section_id: only.sectionId,
        booking_date: '{{ $date }}',
        start_time: only.start,
        end_time: only.end,
    };
    Object.entries(fields).forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = name; input.value = value;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}
@endif

setInterval(() => {
    let d = new Date();
    const el = document.getElementById('currentClock');
    if (el) el.innerText = String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
}, 60000);

function closeSuccessModal(event) {
    const bg = document.getElementById('successModalBg');
    if (event.target === bg) bg.remove();
}

const dateInput = document.getElementById('dateInput');
if (dateInput) {
    let isTyping = false;
    dateInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); document.getElementById('dateForm').submit(); return; }
        isTyping = true;
    });
    dateInput.addEventListener('change', function () {
        if (isTyping) { isTyping = false; return; }
        if (dateInput.value && dateInput.checkValidity()) document.getElementById('dateForm').submit();
    });
}
</script>
@endpush
@endsection
