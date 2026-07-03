@extends('layouts.app')

@section('title', 'จัดการสถานะสนาม')

@section('content')
<div class="bg-[#f8f9fe] min-h-screen text-[#111827] pb-10">

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
.slot-card.selected {
    border: 2px solid #87D068 !important;
    padding: 11px 9px;
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
    font-size: 13px;
    font-weight: 600;
    padding: 8px 0;
}
/* States */
.slot-card.available .slot-btn { background: #f3f4f6; color: #4b5563; }
.slot-card.unavailable .slot-btn { background: #ff0000; color: #fff; }
.slot-card.maintenance .slot-btn { background: #d4e100; color: #111827; }
.slot-card.booking_pending .slot-btn { background: #fef3c7; color: #92400e; }
.slot-card.booking_approved .slot-btn { background: #ff0000; color: #fff; }
.slot-card.booked .slot-btn { background: #ff0000; color: #fff; }

.court-item {
    padding: 8px 12px;
    cursor: pointer;
    transition: background 0.2s;
    font-family: 'Kanit', sans-serif;
}
.court-item:hover { background: #f9fafb; border-color: #e5e7eb; }
.court-item.active { border: 1px solid #87D068; font-weight: bold; }
</style>

<div class="bk-main max-w-[1200px] mx-auto px-4 py-8">

    <div class="mb-6 flex justify-between items-end">
        <div>
            <h1 class="text-[32px] font-bold text-gray-900 tracking-tight mb-2">จัดการสนาม</h1>
            <p class="text-gray-600 text-[15px]">แก้ไขข้อมูลสนาม และสถานะสนาม</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-sm border border-gray-300 px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-50 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            กลับสู่ Dashboard
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm font-medium shadow-sm">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm shadow-sm">
            @foreach ($errors->all() as $err)
                <div>• {{ $err }}</div>
            @endforeach
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-6 mt-8">

        {{-- LEFT COLUMN --}}
        <div class="w-full lg:w-[280px] flex-shrink-0 flex flex-col gap-6">
            
            {{-- BOX 1: เลือกสนาม --}}
            <div class="relative z-50 border border-gray-200 bg-white rounded-lg p-5 flex items-center justify-between">
                <span class="font-bold text-[15px] text-gray-900">1. เลือกสนาม</span>
                
                <div class="relative w-[120px]">
                    <div class="border border-gray-300 rounded px-3 py-1 flex items-center justify-between cursor-pointer bg-white text-[14px]" onclick="document.getElementById('courtList').classList.toggle('hidden')">
                        <span class="truncate">สนามที่ {{ $selectedCourt?->id ?? 1 }}</span>
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                    <div id="courtList" class="hidden absolute top-full mt-1 left-0 w-[150px] bg-white border border-gray-200 rounded-md shadow-xl z-50 flex flex-col overflow-hidden">
                        @foreach($courts as $court)
                            <a href="{{ route('admin.courts', ['court_id' => $court->id, 'date' => $date]) }}" 
                               class="px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 border-b border-gray-50 last:border-0 {{ $selectedCourt?->id == $court->id ? 'font-bold bg-gray-50 text-[#87D068]' : '' }}">
                               สนามที่ {{ $court->id }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- BOX 2: เลือกวันที่ --}}
            @php
                $cDate = \Carbon\Carbon::parse($date);
                $thMonthsFull = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
            @endphp
            <div class="border border-gray-200 bg-white rounded-lg p-5 flex flex-col shadow-sm">
                <span class="font-bold text-[15px] text-gray-900 mb-5 block">2. เลือกวันที่</span>
                <div class="relative w-full rounded border border-[#87a0ff] p-[1px] mb-2 focus-within:border-[#5271ff] focus-within:ring-1 focus-within:ring-[#5271ff]">
                    <span class="absolute -top-2.5 left-2 bg-white px-1 text-[10px] font-bold text-[#5271ff] tracking-wider">Date</span>
                    <form id="dateForm" method="GET" action="{{ route('admin.courts') }}">
                        <input type="hidden" name="court_id" value="{{ $selectedCourt?->id }}">
                        <input type="date" name="date" value="{{ $date }}"
                               onchange="document.getElementById('dateForm').submit()"
                               class="w-full text-sm text-gray-700 p-2 outline-none bg-transparent">
                    </form>
                </div>
                <!-- Note: Native datepicker popup forms the pseudo-calendar seen in mockups -->
            </div>

        </div>

        {{-- RIGHT COLUMN --}}
        <div class="flex-1 flex flex-col gap-6">

            {{-- BOX 3: เลือกเวลา --}}
            <div class="border border-gray-300 bg-white rounded-lg p-6 min-h-[400px]">
                <div class="flex justify-between items-center mb-8 border-b border-gray-100 pb-4">
                    <span class="font-bold text-[16px] text-gray-900">3. เลือกเวลา - {{ $cDate->day }} {{ $thMonthsFull[$cDate->month] }} {{ $cDate->year }}</span>
                    <span class="font-bold text-[14px] text-gray-900">เวลาปัจจุบัน <span id="currentClock">{{ now()->format('H:i') }}</span> น.</span>
                </div>

                @if(!$selectedCourt)
                    <div class="text-center py-20 text-gray-400 font-medium">กรุณาเลือกสนาม</div>
                @else
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-6">
                        @foreach($slots as $slot)
                            @php
                                $sClass = $slot['status']; // available, unavailable, maintenance, booked
                                $sLabel = match($slot['status']) {
                                    'available' => 'ว่าง',
                                    'unavailable' => 'ไม่ว่าง',
                                    'maintenance' => 'ปิดปรับปรุง',
                                    'booking_pending' => 'รออนุมัติ (จอง)',
                                    'booking_approved' => 'ถูกจอง (Booking)',
                                    'booked' => 'ถูกจอง (Booking)',
                                    default => 'ว่าง'
                                };
                            @endphp

                            <div class="slot-card {{ $sClass }}" 
                                 onclick="selectAdminTime('{{ $slot['start'] }}', '{{ $slot['end'] }}', '{{ $slot['status'] }}', this)">
                                <div class="slot-time">{{ $slot['label'] }}</div>
                                <div class="slot-btn">{{ $sLabel }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Action Box (Fixed bottom or static) --}}
            <div id="statusBox" class="hidden border-2 border-gray-200 bg-white rounded-lg p-8 shadow-2xl fixed bottom-8 left-1/2 -translate-x-1/2 w-[90%] max-w-[800px] z-50 flex flex-col items-start gap-6">
                <div class="flex justify-between items-center w-full">
                    <span class="font-bold text-[16px] text-gray-900">เปลี่ยนสถานะ: <span id="s_label" class="text-gray-500 ml-2 font-normal"></span></span>
                    <button onclick="document.getElementById('statusBox').classList.add('hidden'); if(selEl) { selEl.classList.remove('selected'); }" 
                            class="text-gray-400 hover:text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-full p-1 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form id="slotForm" method="POST" action="{{ route('admin.courts.slot') }}" class="flex flex-col sm:flex-row gap-6 w-full">
                    @csrf
                    <input type="hidden" name="court_id" value="{{ $selectedCourt?->id }}">
                    <input type="hidden" name="date" value="{{ $date }}">
                    <input type="hidden" name="start_time" id="st_val">
                    <input type="hidden" name="end_time" id="en_val">

                    <button type="submit" name="status" value="available" 
                            class="flex-1 bg-[#eeeeee] hover:bg-gray-300 text-gray-800 font-medium py-3 px-6 rounded-lg transition text-[15px] shadow-sm">
                        ว่าง
                    </button>
                    <button type="submit" name="status" value="unavailable" 
                            class="flex-1 bg-[#ff0000] hover:bg-red-700 text-white font-medium py-3 px-6 rounded-lg transition text-[15px] shadow-sm">
                        ไม่ว่าง
                    </button>
                    <button type="submit" name="status" value="maintenance" 
                            class="flex-1 bg-[#dcd700] hover:bg-[#c2bd00] text-gray-900 font-medium py-3 px-6 rounded-lg transition text-[15px] shadow-sm">
                        ปิดปรับปรุง
                    </button>
                </form>

            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
let selEl = null;

function selectAdminTime(start, end, status, el) {
    if (selEl) {
        selEl.classList.remove('selected');
    }
    
    // Check if it's booked by user, maybe warn before allowing override
    if (status.includes('book')) {
        if(!confirm('ช่วงเวลานี้มีการจองโดยผู้ใช้งานอยู่แล้ว ต้องการแก้ไขสถานะทับซ้อนหรือไม่? (ระบบจะนับเฉพาะสถานะใหม่ที่คุณเลือก)')) {
            return;
        }
    }

    el.classList.add('selected');
    selEl = el;

    document.getElementById('st_val').value = start;
    document.getElementById('en_val').value = end;
    document.getElementById('s_label').innerText = start.substring(0, 5) + ' - ' + end.substring(0, 5);
    
    document.getElementById('statusBox').classList.remove('hidden');
}

setInterval(() => {
    let d = new Date();
    let clock = document.getElementById('currentClock');
    if(clock) {
        clock.innerText = String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
    }
}, 60000);
</script>
@endpush
@endsection
