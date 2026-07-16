@extends('layouts.app')

@section('title', 'Court Booking')

@section('content')
<div class="bg-white min-h-screen text-[#111827]">

<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');
.bk-main { font-family: 'Sarabun', 'Kanit', sans-serif; }
.bk-main h1, .bk-main h2, .bk-main h3 { font-family: 'Kanit', sans-serif; }

/* Slot CSS */
.slot-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px 10px;
    text-align: center;
    background: #fff;
    cursor: pointer;
    transition: all .2s;
}
.slot-card:hover.available {
    border-color: #d1d5db;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.slot-card.selected {
    border: 2px solid #87D068 !important;
    padding: 11px 9px; /* offset for border */
}
.slot-time {
    font-family: 'Kanit', sans-serif;
    font-size: 13px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 8px;
}
.slot-btn {
    width: 100%;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    padding: 6px 0;
}
.slot-card.available .slot-btn { background: #f3f4f6; color: #4b5563; }
.slot-card.selected .slot-btn { background: #87D068; color: #fff; display: flex; align-items: center; justify-content: center; gap: 4px; }
.slot-card.pending-s .slot-btn { background: #fef3c7; color: #92400e; cursor: not-allowed; }
.slot-card.approved-s .slot-btn { background: #fee2e2; color: #b91c1c; cursor: not-allowed; }
.slot-card.past-s .slot-btn { background: #e5e7eb; color: #9ca3af; cursor: not-allowed; }

.court-item {
    padding: 8px 12px;
    cursor: pointer;
    transition: background 0.2s;
    font-family: 'Kanit', sans-serif;
}
.court-item:hover { background: #f9fafb; }
.court-item.active { background: #fff7ed; font-weight: 600; }

/* Section grid (ครึ่งสนาม + เวลาไม่เต็มชั่วโมง) */
.sec-table { border-collapse: collapse; width: 100%; }
.sec-table th {
    font-family: 'Kanit', sans-serif;
    font-size: 12px;
    font-weight: 700;
    color: #4b5563;
    padding: 8px 6px;
    text-align: center;
    position: sticky; top: 0; background: #fff; z-index: 5;
    border-bottom: 1px solid #e5e7eb;
}
.sec-table td { padding: 3px; vertical-align: top; }
.sec-time-label {
    font-family: 'Kanit', sans-serif;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    white-space: nowrap;
    padding-right: 8px !important;
}
.sec-cell {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 7px 4px;
    text-align: center;
    font-family: 'Kanit', sans-serif;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    color: #4b5563;
    background: #f9fafb;
    transition: all .15s;
    min-width: 64px;
}
.sec-cell:hover.available { border-color: #87D068; background: #f0fdf4; }
.sec-cell.selected { background: #87D068 !important; color: #fff !important; border-color: #6aa952 !important; }
.sec-cell.pending-s { background: #fef3c7; color: #92400e; cursor: not-allowed; }
.sec-cell.approved-s { background: #fee2e2; color: #b91c1c; cursor: not-allowed; }
.sec-cell.past-s { background: #f3f4f6; color: #b0b6c0; cursor: not-allowed; }
.sec-cell.disabled-slot {
    pointer-events: none !important;
    opacity: 0.3 !important;
    background-color: #f3f4f6 !important;
    border-color: #e5e7eb !important;
    color: #9ca3af !important;
    cursor: not-allowed !important;
}
.sec-scroll { max-height: 560px; overflow-y: auto; }
.view-switch {
    display: inline-flex; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;
    font-family: 'Kanit', sans-serif; font-size: 12px; font-weight: 600;
}
.view-switch a { padding: 7px 14px; color: #6b7280; background: #fff; }
.view-switch a.active { background: #0b0b1a; color: #fff; }

.modal-bg {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.6);
    z-index: 1000;
    display: flex; align-items: center; justify-content: center;
    padding: 24px 16px;
    overflow-y: auto;
}
.modal-content {
    background: #fff;
    width: 100%; max-width: 400px;
    max-height: calc(100vh - 48px);
    overflow-y: auto;
    border-radius: 12px;
    padding: 30px 24px;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
}
</style>

<div class="bk-main max-w-[1200px] mx-auto px-4 py-8" data-aos="fade-up">

    {{-- Header --}}
    <div class="mb-6 flex items-baseline justify-between flex-wrap gap-4" data-aos="fade-right">
        <div class="flex items-baseline gap-4">
            <h1 class="text-[32px] font-bold text-gray-900 tracking-tight">Court Booking</h1>
            <span class="text-gray-600 text-[15px]">จองสนามบาสเกตบอล</span>
        </div>
        <div class="view-switch">
            <a href="{{ route('booking.index', ['court_id' => $selectedCourt?->id, 'date' => $date]) }}" class="active">ตาราง (Grid)</a>
            <a href="{{ route('booking.calendar', ['court_id' => $selectedCourt?->id, 'date' => $date]) }}">ปฏิทิน (Calendar)</a>
        </div>
    </div>

    {{-- Banner Dynamic --}}
    @php
        $cid = $selectedCourt ? $selectedCourt->id : 1;

        // รูปภาพสนามบาสเกตบอลแบบเต็มสนาม (รวบรวมจาก Unsplash หลากหลายมุมมอง)
        $realCourts = [
            'https://images.unsplash.com/photo-1577416412292-747c6607f055?q=80&w=2340&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' // Close up hoop (Default)

        ];

        $courtImgSetting = $selectedCourt
            ? \App\Models\Setting::where('key', 'court_img_' . $selectedCourt->id)->value('value')
            : null;
        // เลือกรูปแบบวนลูปตาม ID ของ Court
        $bannerImg = $courtImgSetting ?? $realCourts[($cid - 1) % count($realCourts)];
    @endphp

    <div class="w-full h-[280px] rounded-[16px] overflow-hidden mb-10 shadow-sm relative group" data-aos="zoom-in" data-aos-delay="100">
        <img src="{{ $bannerImg }}"
             onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1546519638-68e109498ffc?q=80&w=1400&auto=format&fit=crop'"
             alt="Court Banner" class="w-full h-full object-cover transition duration-500 group-hover:scale-105">

        {{-- Overlay gradient for text readability and premium look --}}
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
        <div class="absolute bottom-6 left-8 text-white">
            <span class="bg-[#87D068] text-black font-bold text-xs px-2 py-1 rounded mb-2 inline-block">SELECTED</span>
            <h2 class="text-3xl font-bold tracking-wide">{{ $selectedCourt ? $selectedCourt->name : 'โปรดเลือกสนาม' }}</h2>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">
            @foreach ($errors->all() as $err)
                <div>• {{ $err }}</div>
            @endforeach
        </div>
    @endif

    {{-- Layout Grid --}}
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

                    <div id="courtList"
                         class="hidden absolute top-full mt-1 left-0 left-0 w-full max-h-[380px] overflow-y-auto overflow-x-hidden bg-white border border-gray-200 rounded-md shadow-xl z-50 flex flex-col">
                        @foreach($courts as $court)
                            <a href="{{ route('booking.index', ['court_id' => $court->id, 'date' => $date]) }}"
                               class="flex items-center gap-2 px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50 border-b border-gray-50 last:border-0 truncate {{ $selectedCourt?->id == $court->id ? 'font-bold bg-gray-50 text-[#87D068]' : '' }}">
                                <span class="inline-block w-2.5 h-2.5 rounded-full flex-shrink-0 {{ ($court->court_status ?? 'open') === 'open' ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                <span class="truncate">{{ $court->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
                {{-- Dropdown Data --}}
                <div id="courtList" class="hidden mt-4 border-t border-gray-100 pt-3 flex flex-col gap-1">
                    @foreach($courts as $court)
                        <a href="{{ route('booking.index', ['court_id' => $court->id, 'date' => $date]) }}"
                           class="court-item rounded {{ $selectedCourt?->id == $court->id ? 'active' : '' }}">
                           {{ $court->name }}
                        </a>
                    @endforeach
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

                {{-- Outline Input Date --}}
                <div class="relative w-full rounded border border-purple-400 p-[1px] mb-2 focus-within:border-purple-600 focus-within:ring-1 focus-within:ring-purple-600">
                    <span class="absolute -top-2.5 left-2 bg-white px-1 text-[10px] font-bold text-purple-600 tracking-wider">Date</span>
                    <form id="dateForm" method="GET" action="{{ route('booking.index') }}">
                        <input type="hidden" name="court_id" value="{{ $selectedCourt?->id }}">
                        <input type="date" name="date" id="dateInput" value="{{ $date }}"
                               min="{{ now()->toDateString() }}" max="{{ now()->addMonth()->toDateString() }}"
                               id="dateInput"
                               class="w-full text-sm text-gray-700 p-2 outline-none bg-transparent">
                    </form>
                </div>

                {{-- Mock Calendar below --}}
                <div class="bg-[#f8f9fe] rounded-lg mt-3 p-3 text-center border border-gray-100 flex-1">
                    <p class="font-bold text-[14px] text-gray-900 mt-2">เลือก {{ $thDays[$cDate->dayOfWeek] }} ที่ {{ $cDate->day }} {{ $thMonthsFull[$cDate->month] }} {{ $cDate->year + 543 }}</p>
                    <p class="text-[12px] text-gray-500 mt-2">จองได้ล่วงหน้าได้สูงสุด 1 เดือน</p>
                </div>
            </div>

        </div>

        {{-- RIGHT COLUMN --}}
        <div class="flex-1 flex flex-col gap-6">

            {{-- BOX 3: เลือกเวลา (เลือกได้หลายช่วงเวลาพร้อมกัน) --}}
            <div class="border border-gray-300 rounded-lg p-6 bg-white min-h-[400px]">
                <div class="flex justify-between items-center mb-8">
                    <span class="font-bold text-[15px] text-gray-900">3. เลือกเวลา - {{ $cDate->day }} {{ $thMonthsFull[$cDate->month] }} {{ $cDate->year + 543 }}</span>
                    <span class="font-bold text-[14px] text-gray-900">เวลาปัจจุบัน <span id="currentClock">{{ now()->format('H:i') }}</span> น.</span>
                </div>

                @if(!$selectedCourt)
                    <div class="text-center py-20 text-gray-400 font-medium">กรุณาเลือกสนาม</div>
                @elseif(!$matrix || empty($matrix['sections']))
                    <div class="text-center py-20 text-gray-400 font-medium">สนามนี้ยังไม่เปิดให้จอง</div>
                @else
                    @php
                        $statusMeta = [
                            'available' => ['cls' => 'available', 'label' => 'ว่าง'],
                            'pending'   => ['cls' => 'pending-s', 'label' => 'รออนุมัติ'],
                            'approved'  => ['cls' => 'approved-s', 'label' => 'จองแล้ว'],
                            'closed'    => ['cls' => 'past-s', 'label' => 'ปิดบริการ'],
                            'past'      => ['cls' => 'past-s', 'label' => 'ผ่านมาแล้ว'],
                        ];
                    @endphp

                    <p class="text-[12px] text-gray-500 mb-3">เลือกได้ทั้ง "เต็มสนาม" หรือครึ่งสนาม (ครึ่งใดครึ่งหนึ่ง) — ช่วงเวลาละ {{ $matrix['intervalMinutes'] }} นาที จองต่อเนื่องขั้นต่ำ {{ $matrix['minBookingMinutes'] }} นาที</p>

                    <div class="sec-scroll border border-gray-100 rounded-lg">
                    <table class="sec-table">
                        <thead>
                            <tr>
                                <th style="min-width:76px;">เวลา</th>
                                @foreach($matrix['sections'] as $sec)
                                    <th>{{ $sec['name'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($matrix['rows'] as $row)
                                <tr>
                                    <td class="sec-time-label">{{ $row['label'] }}</td>
                                    @foreach($matrix['sections'] as $sec)
                                        @php
                                            $st = $row['sections'][$sec['id']]['status'] ?? 'closed';
                                            $meta = $statusMeta[$st] ?? $statusMeta['closed'];
                                            $isAvail = $st === 'available';
                                        @endphp
                                        <td>
                                            <div class="sec-cell {{ $meta['cls'] }}"
                                                 data-section-id="{{ $sec['id'] }}"
                                                 data-section-code="{{ $sec['code'] }}"
                                                 data-section-name="{{ $sec['name'] }}"
                                                 data-start="{{ substr($row['start'], 0, 5) }}"
                                                 data-end="{{ substr($row['end'], 0, 5) }}"
                                                 {!! $isAvail ? 'onclick="selectTime(this)"' : '' !!}>
                                                {{ $meta['label'] }}
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
        // รองรับทั้งกรณี array เดี่ยว (จองรายการเดียว) และ array ของหลายรายการ (จองหลายเวลาพร้อมกัน)
        $sbList = isset($sbList['court_name']) ? [$sbList] : $sbList;
    @endphp
    <div class="modal-bg" id="successModalBg" onclick="closeSuccessModal(event)">
        <div class="modal-content relative" onclick="event.stopPropagation()">
            <div class="w-16 h-16 mx-auto bg-green-50 rounded-full flex items-center justify-center border-4 border-[#87D068] mb-4">
                <svg class="w-8 h-8 text-[#87D068]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
            </div>

            <h2 class="text-2xl font-bold text-[#87D068] mb-6 tracking-wide" style="font-family:'Kanit',sans-serif;">รอแอดมินอนุมัติ</h2>

            <div class="text-left border border-gray-200 rounded-lg p-5 mb-6">
                <p class="font-bold text-gray-900 mb-3 text-[15px]">รายละเอียดการจอง ({{ count($sbList) }} รายการ)</p>

                @foreach($sbList as $sb)
                    <div class="mb-3 pb-3 border-b border-gray-100 last:border-0 last:mb-0 last:pb-0">
                        <div class="flex justify-between py-1 text-sm">
                            <span class="text-gray-500">สนาม</span>
                            <span class="text-gray-900 text-right">{{ $sb['court_name'] }}</span>
                        </div>
                        <div class="flex justify-between py-1 text-sm">
                            <span class="text-gray-500">วันที่</span>
                            <span class="text-gray-900 text-right">
                                @php
                                    $sbt = \Carbon\Carbon::parse($sb['date']);
                                    $mn = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
                                    echo $sbt->day . ' ' . $mn[$sbt->month] . ' ' . ($sbt->year+543);
                                @endphp
                            </span>
                        </div>
                        <div class="flex justify-between py-1 text-sm">
                            <span class="text-gray-500">เวลา</span>
                            <span class="text-gray-900 text-right">{{ $sb['time'] }}</span>
                        </div>
                        <div class="flex justify-between py-1 text-sm">
                            <span class="text-gray-500">สถานะ</span>
                            <span class="font-bold text-gray-900 text-right">{{ $sb['status'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="bg-blue-50 text-blue-900 rounded-lg p-4 text-left mb-6">
                <p class="font-bold text-sm mb-2">ข้อควรทราบ</p>
                <ul class="text-[12px] space-y-1 text-gray-700 pl-4" style="list-style-type:disc;">
                    <li>กรุณามาถึงก่อนเวลาเล่นอย่างน้อย 10 นาที</li>
                    <li>สามารถยกเลิกการจองได้ที่หน้าประวัติการจอง</li>
                    <li>ยกเลิกได้ก่อนเวลาเล่นจริงอย่างน้อย 24 ชั่วโมง</li>
                    <li>เวลาในการอนุมัติ โดยปกติ ไม่เกิน 15 นาที</li>
                </ul>
            </div>

            <div class="flex gap-4">
                <a href="{{ route('booking.index') }}" class="flex-1 py-3 border border-gray-300 rounded-lg font-bold text-gray-700 text-center hover:bg-gray-50">จองเพิ่ม</a>
                <a href="{{ route('history') }}" class="flex-1 py-3 bg-[#0b0b1a] rounded-lg font-bold text-white text-center hover:bg-[#1a1a2e]">ดูประวัติการจอง</a>
            </div>
        </div>
    </div>
@endif

</div>

@push('scripts')
<script>
// เก็บ selection เป็นรายการ (array) เพื่อให้เลือกได้หลายช่วงเวลาในสนามเดียวพร้อมกัน
let selections = []; // [{ start, end, label, el }]

function findSelectionIndex(el) {
    return selections.findIndex(s => s.el === el);
}

function selectTime(el) {
    const idx = findSelectionIndex(el);

    // กด cell เดิมซ้ำ = ยกเลิกการเลือกอันนี้ออกจากรายการ (ไม่กระทบ cell อื่นที่เลือกไว้)
    if (idx !== -1) {
        selections.splice(idx, 1);
        el.classList.remove('selected');
        updateGridStatus();
        updateConfirmBox();
        return;
    }

    el.classList.add('selected');

    selections.push({
        start: el.dataset.start,
        end: el.dataset.end,
        label: `${el.dataset.start} - ${el.dataset.end}`,
        sectionId: el.dataset.sectionId,
        sectionName: el.dataset.sectionName,
        el,
    });

    updateGridStatus();
    updateConfirmBox();
}

function removeSelection(index) {
    const s = selections[index];
    if (s && s.el) {
        s.el.classList.remove('selected');
        const start = s.start;
        const end = s.end;

        selections.splice(index, 1);

        // อัปเดตช่องอื่นๆ ให้กลับมาคลิกได้ เมื่อลบออกจากรายการจอง
        updateGridStatus();
    } else {
        selections.splice(index, 1);
    }
    updateConfirmBox();
}

function updateConfirmBox() {
    const box = document.getElementById('confirmBox');
    const list = document.getElementById('confirmList');

    if (selections.length === 0) {
        box.classList.add('hidden');
        list.innerHTML = '';
        return;
    }

    box.classList.remove('hidden');
    list.innerHTML = selections.map((s, idx) => {
        const secLabel = s.sectionName ? `<span class="text-gray-400">(${s.sectionName})</span> ` : '';
        return `<div class="flex justify-between items-center border-b border-gray-50 pb-2">
                    <span class="text-gray-500">เวลา ${secLabel}<span class="font-bold text-gray-900">${s.label} น.</span></span>
                    <button type="button" onclick="removeSelection(${idx})" class="text-red-400 hover:text-red-600 text-xs font-bold ml-3">ลบ</button>
                </div>`;
    }).join('');
}

function submitBooking() {
    if (selections.length === 0) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = "{{ route('booking.store') }}";

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);

    const dateInput = document.createElement('input');
    dateInput.type = 'hidden';
    dateInput.name = 'booking_date';
    dateInput.value = '{{ $date }}';
    form.appendChild(dateInput);

    selections.forEach((s, idx) => {
        const ci = document.createElement('input');
        ci.type = 'hidden';
        ci.name = `bookings[${idx}][court_id]`;
        ci.value = "{{ $selectedCourt?->id }}";
        form.appendChild(ci);

        const sec = document.createElement('input');
        sec.type = 'hidden';
        sec.name = `bookings[${idx}][court_section_id]`;
        sec.value = s.sectionId;
        form.appendChild(sec);

        const st = document.createElement('input');
        st.type = 'hidden';
        st.name = `bookings[${idx}][start_time]`;
        st.value = s.start;
        form.appendChild(st);

        const et = document.createElement('input');
        et.type = 'hidden';
        et.name = `bookings[${idx}][end_time]`;
        et.value = s.end;
        form.appendChild(et);
    });

    document.body.appendChild(form);
    form.submit();
}

setInterval(() => {
    let d = new Date();
    document.getElementById('currentClock').innerText =
        String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
}, 60000);

function validateAndSubmitDate(input) {
    const maxDate = new Date("{{ now()->addMonth()->toDateString() }}");
    const minDate = new Date("{{ now()->toDateString() }}");
    const selectedDate = new Date(input.value);

    maxDate.setHours(0,0,0,0);
    minDate.setHours(0,0,0,0);
    selectedDate.setHours(0,0,0,0);

    if (selectedDate > maxDate) {
        alert("สามารถจองล่วงหน้าได้สูงสุด 1 เดือนเท่านั้น");
        input.value = "{{ now()->addMonth()->toDateString() }}";
    }
    else if (selectedDate < minDate) {
        alert("ไม่สามารถเลือกวันย้อนหลังได้");
        input.value = "{{ now()->toDateString() }}";
    }

    document.getElementById('dateForm').submit();
}

// ปิด success modal เมื่อคลิกที่พื้นหลัง (นอกกล่องขาว)
function closeSuccessModal(event) {
    const bg = document.getElementById('successModalBg');
    if (event.target === bg) {
        bg.remove();
    }
}

const dateInput = document.getElementById('dateInput');
if (dateInput) {
    let isTyping = false;

    // ตรวจจับว่าผู้ใช้กำลังพิมพ์ด้วยคีย์บอร์ดอยู่ (ไม่ใช่คลิกเลือกจาก popup)
    dateInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('dateForm').submit();
            return;
        }
        isTyping = true;
    });

    // 'change' จะยิงทั้งตอนคลิกเลือกจาก popup และตอน blur หลังพิมพ์
    dateInput.addEventListener('change', function () {
        if (isTyping) {
            // ผู้ใช้พิมพ์เอง ไม่ auto-submit ให้รอ Enter เท่านั้น
            isTyping = false;
            return;
        }
        // ไม่ได้พิมพ์ (คลิกเลือกจาก popup calendar) → submit ทันที
        if (dateInput.value && dateInput.checkValidity()) {
            document.getElementById('dateForm').submit();
        }
    });
}

function updateGridStatus() {
    const allCells = document.querySelectorAll('.sec-cell');
    const rows = {};

    // จัดกลุ่มตามเวลา (แถว)
    allCells.forEach(cell => {
        const timeKey = cell.dataset.start + '-' + cell.dataset.end;
        if (!rows[timeKey]) rows[timeKey] = [];
        rows[timeKey].push(cell);
    });

    Object.values(rows).forEach(rowCells => {
        // หาช่องที่ "กำลังถูกเลือกอยู่" ในแถวนี้
        const selectedCells = rowCells.filter(c => c.classList.contains('selected'));

        // --- เปลี่ยนมาเช็คด้วย CODE แทน NAME ---
        // มีการเลือก "เต็มสนาม" (code: full)
        const hasFullCourt = selectedCells.some(c => c.dataset.sectionCode === 'full');

        // มีการเลือก "ครึ่งสนาม" (code ไม่ใช่ full, เช่น left, right)
        const hasHalfCourt = selectedCells.some(c => c.dataset.sectionCode !== 'full');

        rowCells.forEach(cell => {
            if (!cell.classList.contains('available') && !cell.classList.contains('selected')) {
                return;
            }

            const isFullCourtCell = (cell.dataset.sectionCode === 'full');

            cell.classList.remove('disabled-slot');

            if (!cell.classList.contains('selected')) {
                if (hasFullCourt) {
                    // ถ้าเลือก 'เต็มสนาม' -> ล็อคช่องอื่นๆ
                    cell.classList.add('disabled-slot');
                } else if (hasHalfCourt && isFullCourtCell) {
                    // ถ้าเลือก 'ครึ่งสนาม' -> ล็อคช่อง 'เต็มสนาม'
                    cell.classList.add('disabled-slot');
                }
            }
        });
    });
}

</script>
@endpush
@endsection
