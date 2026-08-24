@extends('layouts.app')

@section('title', 'จัดการสถานะสนาม')

@section('content')


    <div class="min-h-screen text-[#111827] pt-8 pb-10">

        <style>
            @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');


            .bk-main h1,
            .bk-main h2,
            .bk-main h3 {
                font-family: 'Kanit', sans-serif;
            }

            /* Slot CSS */
            .slot-card {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 12px 10px;
                text-align: center;
                background: #fff;
                cursor: pointer;
                transition: all .2s;
                position: relative;
            }

            .slot-card.selected {
                border: 2px solid #5271ff !important;
                padding: 11px 9px;
                background: #eef2ff;
            }

            /* ติ๊กถูกเล็กๆ มุมขวาบนตอนถูกเลือกแบบ multi-select */
            .slot-card.selected::after {
                content: '✓';
                position: absolute;
                top: 4px;
                right: 6px;
                width: 16px;
                height: 16px;
                border-radius: 50%;
                background: #5271ff;
                color: #fff;
                font-size: 10px;
                line-height: 16px;
                text-align: center;
                font-weight: 700;
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
            .slot-card.available .slot-btn {
                background: #f3f4f6;
                color: #4b5563;
            }

            .slot-card.unavailable .slot-btn {
                background: #ff0000;
                color: #fff;
            }

            .slot-card.maintenance .slot-btn {
                background: #d4e100;
                color: #111827;
            }

            .slot-card.booking_pending .slot-btn {
                background: #fef3c7;
                color: #92400e;
            }

            .slot-card.booking_pending_payment .slot-btn {
                background: #ffedd5;
                color: #9a3412;
            }

            .slot-card.booking_approved .slot-btn {
                background: #ff0000;
                color: #fff;
            }

            .slot-card.booked .slot-btn {
                background: #ff0000;
                color: #fff;
            }

            /* ช่วงเวลาที่มีลูกค้าจองอยู่แล้ว — แก้ไขสถานะทับไม่ได้ ต้องไปจัดการที่หน้ารายการจอง */
            .slot-card.booking_pending,
            .slot-card.booking_pending_payment,
            .slot-card.booking_approved,
            .slot-card.booked {
                cursor: not-allowed;
            }

            .court-item {
                padding: 8px 12px;
                cursor: pointer;
                transition: background 0.2s;
                font-family: 'Kanit', sans-serif;
            }

            .court-item:hover {
                background: #f9fafb;
                border-color: #e5e7eb;
            }

            .court-item.active {
                border: 1px solid #87D068;
                font-weight: bold;
            }

            #courtModal input:invalid,
            #courtModal select:invalid {
                border-color: #ef4444;
                box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.15);
            }

            #courtModal input:focus:invalid,
            #courtModal select:focus:invalid {
                border-color: #ef4444;
                box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.18);
            }
        </style>

        <div class="container mx-auto px-4 sm:px-6 max-w-7xl">

            <div class="mb-6 flex justify-between items-end">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <h1 class="text-[32px] font-bold text-gray-900 tracking-tight">จัดการสนาม</h1>
                    </div>
                    <p class="text-gray-600 text-[15px]">แก้ไขข้อมูลสนาม และสถานะสนาม</p>
                </div>
                <button type="button" onclick="openCourtModal()"
                    class="font-semibold text-sm border border-orange-500 px-4 py-2 rounded-lg text-white bg-orange-500 hover:bg-orange-600 flex items-center gap-2 shadow-sm cursor-pointer transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 5v14m7-7H5" />
                    </svg>
                    เพิ่มสนาม
                </button>
            </div>

            <div class="flex flex-col lg:flex-row gap-6 mt-8">

                {{-- LEFT COLUMN --}}
                <div class="w-full lg:w-[420px] flex-shrink-0 flex flex-col gap-6">

                    {{-- BOX 1: เลือกสนาม --}}
                    <div
                        class="relative z-3 border border-gray-200 bg-white rounded-lg p-5 flex items-center justify-between">
                        <span class="font-bold text-[15px] text-gray-900">1. เลือกสนาม</span>

                        <div class="relative w-[120px]">
                            <div class="border border-gray-300 rounded px-3 py-1 flex items-center justify-between cursor-pointer bg-white text-[14px]"
                                onclick="document.getElementById('courtList').classList.toggle('hidden')">
                                <span class="truncate"> {{ $selectedCourt?->name ?? 'เลือกสนาม' }}</span>
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                            <div id="courtList"
                                class="hidden absolute top-full mt-1 left-0 w-[220px] max-h-[380px] overflow-y-auto overflow-x-hidden bg-white border border-gray-200 rounded-md shadow-xl z-50 flex flex-col">
                                @foreach ($courts as $court)
                                    <div
                                        class="flex items-center justify-between gap-2 px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50 border-b border-gray-50 last:border-0 {{ $selectedCourt?->id == $court->id ? 'font-bold bg-gray-50 text-[#87D068]' : '' }}">
                                        <a href="{{ route('admin.courts', ['court_id' => $court->id, 'date' => $date]) }}"
                                            class="truncate flex-1 text-left">
                                            <span class="inline-flex items-center gap-2">
                                                <span
                                                    class="inline-block w-2.5 h-2.5 rounded-full {{ $court->court_status === 'open' ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                                <span>{{ $court->name }}</span>
                                            </span>
                                        </a>
                                        <!-- ปุ่ม Action -->
                                        <div class="flex items-center gap-1 shrink-0">
                                            <!-- ปุ่มแก้ไข -->
                                            <button type="button"
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-md border border-gray-200 text-gray-500 hover:bg-white hover:text-gray-900 transition"
                                                onclick="event.preventDefault(); event.stopPropagation(); openCourtModal('edit', {{ $court->id }}, @js($court->name), '{{ $court->court_status }}')"
                                                title="แก้ไขสนาม">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        d="M22.94,1.061c-1.368-1.367-3.76-1.365-5.124,0L1.611,17.265c-1.039,1.04-1.611,2.421-1.611,3.89v2.346c0,.276,.224,.5,.5,.5H2.846c1.47,0,2.851-.572,3.889-1.611L22.86,6.265c.579-.581,.953-1.262,1.08-1.972,.216-1.202-.148-2.381-1-3.232ZM6.028,21.682c-.85,.851-1.979,1.318-3.182,1.318H1v-1.846c0-1.202,.468-2.332,1.318-3.183L15.292,4.999l3.709,3.709L6.028,21.682ZM22.956,4.116c-.115,.642-.5,1.138-.803,1.441l-2.444,2.444-3.709-3.709,2.525-2.525c.986-.988,2.718-.99,3.709,0,.617,.617,.88,1.473,.723,2.349Z" />
                                                </svg>
                                            </button>

                                            <!-- ปุ่มลบ -->
                                            <button type="button"
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-md border border-gray-200 text-gray-400 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition"
                                                onclick="event.preventDefault(); event.stopPropagation(); deleteCourt({{ $court->id }}, @js($court->name))"
                                                title="ลบสนาม">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- BOX 2: เลือกวันที่ --}}
                    @php
                        $cDate = \Carbon\Carbon::parse($date);
                        $thMonthsFull = [
                            '',
                            'มกราคม',
                            'กุมภาพันธ์',
                            'มีนาคม',
                            'เมษายน',
                            'พฤษภาคม',
                            'มิถุนายน',
                            'กรกฎาคม',
                            'สิงหาคม',
                            'กันยายน',
                            'ตุลาคม',
                            'พฤศจิกายน',
                            'ธันวาคม',
                        ];
                    @endphp
                    <div class="border border-gray-200 bg-white rounded-lg p-5 flex flex-col shadow-sm">
                        <span class="font-bold text-[15px] text-gray-900 mb-5 block">2. เลือกวันที่</span>
                        <div
                            class="relative w-full rounded border border-[#87a0ff] p-[1px] mb-2 focus-within:border-[#5271ff] focus-within:ring-1 focus-within:ring-[#5271ff]">
                            <span
                                class="absolute -top-2.5 left-2 bg-white px-1 text-[10px] font-bold text-[#5271ff] tracking-wider">Date</span>
                            <form id="dateForm" method="GET" action="{{ route('admin.courts') }}">
                                <input type="hidden" name="court_id" value="{{ $selectedCourt?->id }}">
                                <input type="date" name="date" value="{{ $date }}"
                                    id="dateInput"
                                    class="w-full text-sm text-gray-700 p-2 outline-none bg-transparent">
                            </form>
                        </div>
                        <!-- Note: Native datepicker popup forms the pseudo-calendar seen in mockups -->
                    </div>

                    {{-- BOX 4: จัดการส่วนของสนาม (ครึ่ง A/B) --}}
                    <div class="border border-gray-200 bg-white rounded-lg p-4 shadow-sm">
                        <span class="font-bold text-[15px] sm:text-[16px] text-gray-900 block mb-1">4. จัดการส่วนของสนาม (ครึ่งสนาม)</span>
                        <p class="text-sm text-gray-500 mb-5">แบ่งสนามนี้เป็นครึ่ง A/B เพื่อให้ลูกค้าจองครึ่งสนามได้ ระบบจะกันไม่ให้จองซ้อนกับเต็มสนามให้อัตโนมัติ</p>

                        @if (!$selectedCourt)
                            <div class="text-center py-6 text-gray-400 text-sm">กรุณาเลือกสนาม</div>
                        @else
                            @php
                                $fullSec = $sections->firstWhere('code', 'full');
                                $aSec = $sections->firstWhere('code', 'a');
                                $bSec = $sections->firstWhere('code', 'b');
                                $isSplit = ($aSec && $aSec->is_active) || ($bSec && $bSec->is_active);
                            @endphp

                            @if ($isSplit)
                                {{-- ---------------------------------------------------- --}}
                                {{-- กรณีที่ 1: กำลังแบ่งครึ่งสนามอยู่ (ปรับเปลี่ยนสถานะ A/B ได้) --}}
                                {{-- ---------------------------------------------------- --}}
                                <form method="POST" action="{{ route('admin.courts.sections.split', $selectedCourt->id) }}" class="mb-4">
                                    @csrf
                                    <input type="hidden" name="return_date" value="{{ $date }}">

                                    <div class="flex flex-col gap-3 mb-4">
                                        {{-- เต็มสนาม (FULL) --}}
                                        <div class="flex items-center gap-3 border border-gray-100 rounded-lg p-3">
                                            <span class="text-xs font-bold px-2 py-1 rounded bg-gray-900 text-white">Half</span>
                                            <label class="flex-1 min-w-0 rounded-lg px-3 py-1.5 text-sm text-gray-900 outline-none">
                                                ครึ่งสนาม
                                            </label>
                                            <div class="shrink-0 w-[115px] text-right px-1">
                                                <span class="text-[12px] text-gray-400 whitespace-nowrap">เปิดใช้งานเสมอ</span>
                                            </div>
                                        </div>

                                        {{-- ครึ่ง A --}}
                                        <div class="flex items-center gap-3 border border-gray-100 rounded-lg p-3">
                                            <span class="text-xs font-bold px-2 py-1 rounded bg-[#eef2ff] text-[#5271ff]">A</span>
                                            <input type="text" name="name_a" value="{{ $aSec->name ?? 'ครึ่ง A' }}" maxlength="100"
                                                class="flex-1 min-w-0 rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-900 focus:ring-2 focus:ring-[#5271ff]/20 focus:border-[#5271ff] outline-none">

                                            <!-- Toggle Switch สำหรับ ครึ่ง A -->
                                            <label class="inline-flex items-center gap-2 cursor-pointer select-none px-1 py-1 rounded-lg hover:bg-gray-50 transition shrink-0 w-[115px] justify-end">
                                                <input type="checkbox" name="is_active_a" value="1" {{ ($aSec && $aSec->is_active) ? 'checked' : '' }} class="sr-only peer">
                                                <div class="w-8 h-4.5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-emerald-500 relative"></div>
                                                <span class="w-[62px] text-left text-xs font-medium text-emerald-600 hidden peer-checked:inline-block whitespace-nowrap shrink-0">เปิดใช้งาน</span>
                                                <span class="w-[62px] text-left text-xs font-medium text-gray-400 inline-block peer-checked:hidden whitespace-nowrap shrink-0">ปิดใช้งาน</span>
                                            </label>
                                        </div>

                                        {{-- ครึ่ง B --}}
                                        <div class="flex items-center gap-3 border border-gray-100 rounded-lg p-3">
                                            <span class="text-xs font-bold px-2 py-1 rounded bg-[#eef2ff] text-[#5271ff]">B</span>
                                            <input type="text" name="name_b" value="{{ $bSec->name ?? 'ครึ่ง B' }}" maxlength="100"
                                                class="flex-1 min-w-0 rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-900 focus:ring-2 focus:ring-[#5271ff]/20 focus:border-[#5271ff] outline-none">

                                            <!-- Toggle Switch สำหรับ ครึ่ง B -->
                                            <label class="inline-flex items-center gap-2 cursor-pointer select-none px-1 py-1 rounded-lg hover:bg-gray-50 transition shrink-0 w-[115px] justify-end">
                                                <input type="checkbox" name="is_active_b" value="1" {{ ($bSec && $bSec->is_active) ? 'checked' : '' }} class="sr-only peer">
                                                <div class="w-8 h-4.5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-emerald-500 relative"></div>
                                                <span class="w-[62px] text-left text-xs font-medium text-emerald-600 hidden peer-checked:inline-block whitespace-nowrap shrink-0">เปิดใช้งาน</span>
                                                <span class="w-[62px] text-left text-xs font-medium text-gray-400 inline-block peer-checked:hidden whitespace-nowrap shrink-0">ปิดใช้งาน</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- ปุ่มบันทึก -->
                                    <button type="submit"
                                        class="w-full text-[13px] font-medium text-white bg-orange-500 hover:bg-orange-600 rounded-lg px-4 py-2 transition shadow-sm mb-3 cursor-pointer">
                                        บันทึก
                                    </button>
                                </form>

                                {{-- ปุ่มยกเลิกการแบ่งครึ่งสนาม --}}
                                <form id="mergeSectionForm" method="POST" action="{{ route('admin.courts.sections.merge', $selectedCourt->id) }}">
                                    @csrf
                                    <input type="hidden" name="return_date" value="{{ $date }}">
                                    <button type="button" onclick="confirmMergeSection()"
                                        class="w-full text-[13px] font-medium text-red-600 border border-red-200 hover:bg-red-50 rounded-lg px-3 py-2 transition text-center cursor-pointer">
                                        ยกเลิกการแบ่งครึ่งสนาม (รวมกลับเป็นเต็มสนาม)
                                    </button>
                                </form>

                            @else
                                {{-- ---------------------------------------------------- --}}
                                {{-- กรณีที่ 2: ยกเลิกการแบ่งสนามแล้ว / จองเฉพาะเต็มสนาม   --}}
                                {{-- ---------------------------------------------------- --}}

                                {{-- แก้ไขชื่อเต็มสนาม --}}
                                @if ($fullSec)
                                    <div class="flex items-center gap-3 border border-gray-100 rounded-lg p-3 mb-4">
                                        <span class="text-xs font-bold px-2 py-1 rounded bg-gray-900 text-white">FULL</span>
                                        <label class="flex-1 min-w-0 rounded-lg px-3 py-1.5 text-sm text-gray-900 outline-none">
                                            {{ $fullSec->name }}
                                        </label>
                                        <span class="text-[12px] text-gray-400 px-2 whitespace-nowrap">เปิดใช้งานเสมอ</span>
                                    </div>
                                @endif

                                {{-- ฟอร์มแบ่งครึ่งสนามใหม่ --}}
                                <form method="POST" action="{{ route('admin.courts.sections.split', $selectedCourt->id) }}"
                                    class="border-t border-gray-100 pt-4 flex flex-col sm:flex-row items-end gap-3">
                                    @csrf
                                    <input type="hidden" name="return_date" value="{{ $date }}">
                                    <input type="hidden" name="is_active_a" value="1">
                                    <input type="hidden" name="is_active_b" value="1">
                                    <div class="flex-1 w-full">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">ชื่อครึ่ง A</label>
                                        <input type="text" name="name_a" value="{{ $aSec->name ?? 'ครึ่ง A' }}" maxlength="100"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-[#5271ff]/20 focus:border-[#5271ff] outline-none">
                                    </div>
                                    <div class="flex-1 w-full">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">ชื่อครึ่ง B</label>
                                        <input type="text" name="name_b" value="{{ $bSec->name ?? 'ครึ่ง B' }}" maxlength="100"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-[#5271ff]/20 focus:border-[#5271ff] outline-none">
                                    </div>
                                    <button type="submit"
                                        class="text-[13px] font-medium text-white bg-[#87D068] hover:bg-[#76bc5a] rounded-lg px-4 py-2 transition whitespace-nowrap">
                                        แบ่งครึ่งสนาม
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>

                </div>

                {{-- RIGHT COLUMN --}}
                <div class="flex-1 flex flex-col gap-6">

                    {{-- BOX 3: เลือกเวลา --}}
                    <div class="border border-gray-300 bg-white rounded-lg p-6 min-h-[400px]">
                        <div class="flex justify-between items-center mb-8 border-b border-gray-100 pb-4">
                            <span class="font-bold text-[16px] text-gray-900">3. เลือกเวลา - {{ $cDate->day }}
                                {{ $thMonthsFull[$cDate->month] }} {{ $cDate->year }}</span>
                            <span class="font-bold text-[14px] text-gray-900">เวลาปัจจุบัน <span
                                    id="currentClock">{{ now()->format('H:i') }}</span> น.</span>
                        </div>

                        @if (!$selectedCourt)
                            <div class="text-center py-20 text-gray-400 font-medium">กรุณาเลือกสนาม</div>
                        @else
                            <div class="text-xs text-gray-400 mb-3">* คลิกเลือกได้หลายช่วงเวลา แล้วกดยืนยันสถานะครั้งเดียวด้านล่าง</div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-6">
                                @foreach ($slots as $slot)
                                    @php
                                        $sClass = $slot['status']; // available, unavailable, maintenance, booked
                                        $sLabel = match ($slot['status']) {
                                            'booking_pending_payment' => 'กำลังจอง (ชำระเงิน)',
                                            'available' => 'ว่าง',
                                            'unavailable' => 'ไม่ว่าง',
                                            'maintenance' => 'ปิดปรับปรุง',
                                            'booking_pending' => 'รออนุมัติ (จอง)',
                                            'booking_approved' => 'ถูกจอง (Booking)',
                                            'booked' => 'ถูกจอง (Booking)',
                                            default => 'ว่าง',
                                        };
                                    @endphp

                                    <div class="slot-card {{ $sClass }}" data-start="{{ $slot['start'] }}"
                                        data-end="{{ $slot['end'] }}" data-status="{{ $slot['status'] }}"
                                        data-label="{{ $slot['label'] }}"
                                        onclick="toggleAdminTime('{{ $slot['start'] }}', '{{ $slot['end'] }}', '{{ $slot['status'] }}', '{{ $slot['label'] }}', this)">
                                        <div class="slot-time">{{ $slot['label'] }}</div>
                                        <div class="slot-btn">{{ $sLabel }}</div>
                                        @if(!empty($slot['customer_name']))
                                            <div class="text-[11px] text-gray-600 mt-1.5 truncate" title="{{ $slot['customer_name'] }}">
                                                {{ $slot['customer_name'] }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>



                    {{-- BOX 5: ความละเอียดของเวลา --}}
                    {{-- <div class="border border-gray-200 bg-white rounded-lg p-6 shadow-sm">
                        <span class="font-bold text-[16px] text-gray-900 block mb-1">5. ตั้งค่าความละเอียดของเวลา</span>
                        <p class="text-[13px] text-gray-500 mb-5">กำหนดว่าลูกค้าเลือกเวลาเริ่ม/จบได้ทีละกี่นาที และต้องจองต่อเนื่องอย่างน้อยกี่นาที (ต่อสนามนี้)</p>

                        @if (!$selectedCourt)
                            <div class="text-center py-6 text-gray-400 text-sm">กรุณาเลือกสนาม</div>
                        @else
                            <form method="POST" action="{{ route('admin.courts.slot-settings', $selectedCourt->id) }}"
                                class="flex flex-col sm:flex-row items-end gap-3">
                                @csrf
                                <input type="hidden" name="return_date" value="{{ $date }}">
                                <div class="flex-1 w-full">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">เลือกเวลาได้ทีละ (นาที)</label>
                                    <select name="slot_interval_minutes"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-[#5271ff]/20 focus:border-[#5271ff] outline-none">
                                        @foreach ([15, 30, 60] as $opt)
                                            <option value="{{ $opt }}" @selected($selectedCourt->slot_interval_minutes == $opt)>{{ $opt }} นาที</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex-1 w-full">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">จองต่อเนื่องขั้นต่ำ (นาที)</label>
                                    <select name="min_booking_minutes"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-[#5271ff]/20 focus:border-[#5271ff] outline-none">
                                        @foreach ([30, 60, 90, 120] as $opt)
                                            <option value="{{ $opt }}" @selected($selectedCourt->min_booking_minutes == $opt)>{{ $opt }} นาที</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit"
                                    class="text-[13px] font-medium text-white bg-[#5271ff] hover:bg-[#3f5ee8] rounded-lg px-5 py-2 transition whitespace-nowrap">
                                    บันทึก
                                </button>
                            </form>
                            @error('min_booking_minutes')
                                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div> --}}

                    {{-- Action Box (Fixed bottom or static) --}}
                    <div id="statusBox"
                        class="hidden border-2 border-gray-200 bg-white rounded-lg p-8 shadow-2xl fixed bottom-8 left-1/2 -translate-x-1/2 w-[90%] max-w-[800px] z-50 flex flex-col items-start gap-6">
                        <div class="flex justify-between items-center w-full">
                            <span class="font-bold text-[16px] text-gray-900">เปลี่ยนสถานะ:
                                <span id="s_label" class="text-gray-500 ml-2 font-normal"></span>
                                <span id="s_count" class="ml-2 inline-flex items-center justify-center text-[11px] font-bold bg-[#5271ff] text-white rounded-full px-2 py-0.5"></span>
                            </span>
                            <div class="flex items-center gap-3">
                                <button type="button" onclick="clearAdminSelection()"
                                    class="text-xs text-gray-400 hover:text-red-500 underline underline-offset-2 cursor-pointer transition">
                                    ล้างที่เลือก
                                </button>
                                <button type="button" onclick="closeStatusBox()"
                                    class="text-gray-400 hover:text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-full p-1 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <form id="slotForm" method="POST" action="{{ route('admin.courts.slot') }}"
                            class="flex flex-col sm:flex-row gap-6 w-full">
                            @csrf
                            <input type="hidden" name="court_id" value="{{ $selectedCourt?->id }}">
                            <input type="hidden" name="date" value="{{ $date }}">
                            {{-- ช่วงเวลาที่เลือกทั้งหมด ส่งเป็น JSON array [{start,end}, ...] --}}
                            <input type="hidden" name="slots" id="slots_val">
                            {{-- คงชื่อ start_time/end_time เดิมไว้ (ช่วงแรกที่เลือก) เผื่อ backend เดิมยังใช้อยู่ ระหว่างรอปรับ controller --}}
                            <input type="hidden" name="start_time" id="st_val">
                            <input type="hidden" name="end_time" id="en_val">
                            {{-- สถานะที่จะตั้ง และวันสุดท้ายที่จะทำซ้ำ (ถ้ามี) — เซ็ตค่าโดย JS หลังยืนยัน popup --}}
                            <input type="hidden" name="status" id="status_val">
                            <input type="hidden" name="repeat_until_date" id="repeat_until_val">

                            <button type="button" data-status="available" class="status-trigger-btn
                                flex-1 bg-[#eeeeee] hover:bg-gray-300 text-gray-800 font-medium py-3 px-6 rounded-lg transition text-[15px] shadow-sm cursor-pointer">
                                ว่าง
                            </button>
                            <button type="button" data-status="unavailable" class="status-trigger-btn
                                flex-1 bg-[#ff0000] hover:bg-red-700 text-white font-medium py-3 px-6 rounded-lg transition text-[15px] shadow-sm cursor-pointer">
                                ไม่ว่าง
                            </button>
                            <button type="button" data-status="maintenance" class="status-trigger-btn
                                flex-1 bg-[#dcd700] hover:bg-[#c2bd00] text-gray-900 font-medium py-3 px-6 rounded-lg transition text-[15px] shadow-sm cursor-pointer">
                                ปิดปรับปรุง
                            </button>
                        </form>

                    </div>

                </div>
            </div>
        </div>

        <div id="courtModal" class="hidden fixed inset-0 z-[60] items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl border border-gray-200 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h2 id="courtModalTitle" class="text-2xl font-bold text-gray-900">เพิ่มสนาม</h2>
                        <p id="courtModalSubtitle" class="text-sm text-gray-500">กรอกชื่อและสถานะสนาม</p>
                    </div>
                </div>

                <form id="courtForm" method="POST" action="{{ route('admin.court.create') }}" novalidate
                    class="px-6 py-5 space-y-4">
                    @csrf
                    <input type="hidden" name="_method" id="court_form_method" value="">
                    <input type="hidden" name="court_id" id="court_id" value="{{ old('court_id') }}">
                    <input type="hidden" name="return_date" value="{{ $date }}">
                    <input type="hidden" name="return_court_id" value="{{ $selectedCourt?->id }}">
                    <div>
                        <label for="court_name" class="block text-sm font-medium text-gray-700 mb-1">ชื่อสนาม</label>
                        <input id="court_name" name="name" type="text" required maxlength="255" value="{{ old('name') }}"
                            class="w-full rounded-lg border px-4 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-[#5271ff]/20 outline-none {{ $errors->has('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 focus:border-[#5271ff]' }}"
                            placeholder="เช่น สนาม 1">
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $errors->first('name') }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="court_status" class="block text-sm font-medium text-gray-700 mb-1">สถานะ</label>
                        <select id="court_status" name="court_status"
                            class="w-full rounded-lg border px-4 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-[#5271ff]/20 outline-none {{ $errors->has('court_status') ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 focus:border-[#5271ff]' }}">
                            <option value="open" @selected(old('court_status', 'open') === 'open')>เปิดใช้งาน</option>
                            <option value="closed" @selected(old('court_status') === 'closed')>ปิดใช้งาน</option>
                        </select>
                        @error('court_status')
                            <p class="mt-1 text-xs text-red-600">{{ $errors->first('court_status') }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" onclick="closeCourtModal()"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 cursor-pointer transition">ยกเลิก</button>
                        <button type="submit"
                            class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-medium text-white hover:bg-orange-600 cursor-pointer transition"
                            id="courtModalSubmit">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>

            <script>
                // ===================== Multi-select ช่วงเวลา =====================
                // เก็บช่วงเวลาที่ถูกเลือกไว้เป็น object คีย์ = start time เพื่อกันเลือกซ้ำ
                let selectedSlots = {};

                function toggleAdminTime(start, end, status, label, el) {
                    // ห้ามแก้สถานะทับช่วงที่มีลูกค้าจองอยู่แล้วโดยเด็ดขาด (ล็อกไว้ทั้ง client และ server)
                    // ต้องไปจัดการผ่านหน้ารายการจอง (อนุมัติ/ปฏิเสธ/ยกเลิก) เท่านั้น
                    if (status.includes('book')) {
                        Swal.fire({
                            icon: 'info',
                            title: 'แก้ไขสถานะไม่ได้',
                            text: 'ช่วงเวลานี้มีลูกค้าจองอยู่แล้ว ไม่สามารถแก้ไขสถานะทับได้ กรุณาไปจัดการที่หน้ารายการจอง (อนุมัติ/ปฏิเสธ/ยกเลิก) ก่อน',
                            confirmButtonText: 'เข้าใจแล้ว',
                            confirmButtonColor: '#f97316',
                        });
                        return;
                    }

                    if (selectedSlots[start]) {
                        // เคยเลือกไว้แล้ว -> คลิกซ้ำ = ยกเลิกการเลือกช่วงนั้น
                        delete selectedSlots[start];
                        el.classList.remove('selected');
                    } else {
                        // ยังไม่เคยเลือก -> เพิ่มเข้า selection
                        selectedSlots[start] = { start, end, label };
                        el.classList.add('selected');
                    }

                    renderStatusBox();
                }

                function renderStatusBox() {
                    const keys = Object.keys(selectedSlots);
                    const statusBox = document.getElementById('statusBox');
                    const sLabel = document.getElementById('s_label');
                    const sCount = document.getElementById('s_count');

                    if (keys.length === 0) {
                        statusBox.classList.add('hidden');
                        sLabel.innerText = '';
                        sCount.innerText = '';
                        return;
                    }

                    // เรียงตามเวลาเริ่ม แล้วแสดงตัวอย่างช่วงเวลา (สูงสุด 3 รายการ + ...)
                    const items = Object.values(selectedSlots).sort((a, b) => a.start.localeCompare(b.start));
                    const preview = items.slice(0, 3).map(i => i.label).join(', ');
                    sLabel.innerText = items.length > 3 ? `${preview} ...` : preview;
                    sCount.innerText = `${items.length} ช่วงเวลา`;

                    statusBox.classList.remove('hidden');
                }

                function clearAdminSelection() {
                    Object.keys(selectedSlots).forEach(start => {
                        const el = document.querySelector(`.slot-card[data-start="${CSS.escape(start)}"]`);
                        if (el) el.classList.remove('selected');
                    });
                    selectedSlots = {};
                    renderStatusBox();
                }

                function closeStatusBox() {
                    clearAdminSelection();
                }

                // ก่อน submit ฟอร์ม ให้แนบรายการช่วงเวลาที่เลือกทั้งหมดเป็น JSON
                document.getElementById('slotForm')?.addEventListener('submit', function (e) {
                    const items = Object.values(selectedSlots);
                    if (items.length === 0) {
                        e.preventDefault();
                        return;
                    }

                    const sorted = items.sort((a, b) => a.start.localeCompare(b.start));
                    document.getElementById('slots_val').value = JSON.stringify(
                        sorted.map(i => ({ start: i.start, end: i.end }))
                    );
                    // เผื่อ backend เดิมยังอ่าน start_time/end_time เดี่ยวๆ อยู่ ให้ใส่ค่าของช่วงแรกไว้ด้วย
                    document.getElementById('st_val').value = sorted[0].start;
                    document.getElementById('en_val').value = sorted[0].end;
                });

                // ===================== กดปุ่มสถานะ -> เปิด popup ถามว่าจะ "ทำซ้ำ" ถึงวันไหน =====================
                const STATUS_LABEL_TH = { available: 'ว่าง', unavailable: 'ไม่ว่าง', maintenance: 'ปิดปรับปรุง' };
                const PAGE_DATE = @js($date); // วันที่ที่กำลังดูอยู่บนหน้านี้ (YYYY-MM-DD)

                document.querySelectorAll('.status-trigger-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        const items = Object.values(selectedSlots);
                        if (items.length === 0) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'กรุณาเลือกช่วงเวลาก่อน',
                                confirmButtonColor: '#5271ff',
                            });
                            return;
                        }

                        const statusValue = this.dataset.status;
                        const statusLabel = STATUS_LABEL_TH[statusValue] || statusValue;
                        const timePreview = items
                            .sort((a, b) => a.start.localeCompare(b.start))
                            .map(i => i.label)
                            .join(', ');

                        const result = await Swal.fire({
                            title: `<h2 class="text-black">ตั้งสถานะ "${statusLabel}"</h2>`,
                            html: `
                                <p class="text-sm text-gray-600 mb-1 text-left">ช่วงเวลาที่เลือก: <b>${timePreview}</b></p>
                                <p class="text-sm text-gray-600 mb-3 text-left">
                                    ต้องการทำซ้ำสถานะนี้ทุกวัน (เวลาเดิม) ไปจนถึงวันที่เท่าไหร่?<br>
                                    <span class="text-xs text-gray-400">ถ้าต้องการแค่วันนี้วันเดียว ปล่อยว่างแล้วกด "ยืนยัน" ได้เลย</span>
                                </p>
                                <label class="block text-xs font-medium text-gray-500 mb-1 text-left">ทำซ้ำถึงวันที่ (ไม่บังคับ)</label>
                                <input type="date" id="swal-repeat-until" class="swal2-input" style="margin:0;width:100%;" min="${PAGE_DATE}">
                            `,
                            focusConfirm: false,
                            showCancelButton: true,
                            reverseButtons: true,
                            confirmButtonText: 'ยืนยัน',
                            cancelButtonText: 'ยกเลิก',
                            confirmButtonColor: '#f97316',
                            cancelButtonColor: '#6b7280',
                            preConfirm: () => {
                                const val = document.getElementById('swal-repeat-until').value;
                                if (val && val < PAGE_DATE) {
                                    Swal.showValidationMessage('วันที่ทำซ้ำต้องไม่ก่อนวันที่กำลังดูอยู่');
                                    return false;
                                }
                                return val || '';
                            },
                        });

                        if (!result.isConfirmed) return;

                        document.getElementById('status_val').value = statusValue;
                        document.getElementById('repeat_until_val').value = result.value || '';

                        // ยืนยันอีกครั้งแบบสั้นๆ ถ้ามีการเลือกทำซ้ำ เพื่อกันกดพลาด
                        if (result.value) {
                            const confirmRepeat = await Swal.fire({
                                icon: 'question',
                                title: 'ยืนยันการทำซ้ำ',
                                html: `จะตั้งสถานะ "<b>${statusLabel}</b>" ช่วงเวลา <b>${timePreview}</b><br>ทุกวัน ตั้งแต่ ${PAGE_DATE} ถึง <b>${result.value}</b> ใช่หรือไม่?`,
                                showCancelButton: true,
                                confirmButtonText: 'ใช่, ยืนยัน',
                                cancelButtonText: 'ยกเลิก',
                                confirmButtonColor: '#5271ff',
                            });
                            if (!confirmRepeat.isConfirmed) return;
                        }

                        document.getElementById('slotForm').requestSubmit();
                    });
                });

                // ----- SweetAlert2 Toast (มุมขวาบน, ปิดเองอัตโนมัติ) -----
                // const Toast = Swal.mixin({
                //     toast: true,
                //     position: 'top-end',
                //     showConfirmButton: false,
                //     timer: 2000,
                //     timerProgressBar: true,
                //     didOpen: (toast) => {
                //         toast.onmouseenter = Swal.stopTimer;
                //         toast.onmouseleave = Swal.resumeTimer;
                //     }
                // });

                document.addEventListener('DOMContentLoaded', function () {
                    // Toast ที่ถูกฝากไว้ก่อนเปลี่ยนหน้า (จากฟอร์ม AJAX เช่น สร้าง/แก้ไขสนาม)
                    try {
                        const pending = sessionStorage.getItem('pendingToast');
                        if (pending) {
                            sessionStorage.removeItem('pendingToast');
                            Toast.fire(JSON.parse(pending));
                        }
                    } catch (e) { }

                    @if (session('success'))
                        Toast.fire({
                            icon: 'success',
                            title: @js(session('success'))
                        });
                    @endif

                    @if (session('error'))
                        Toast.fire({
                            icon: 'error',
                            title: @js(session('error'))
                        });
                    @endif

                    @if ($errors->any())
                        Toast.fire({
                            icon: 'error',
                            title: @js($errors->first())
                        });
                    @endif

                    const dateInput = document.getElementById('dateInput');
                    const dateForm = document.getElementById('dateForm');
                    if (dateInput && dateForm) {
                        dateInput.addEventListener('change', function () {
                            clearAdminSelection();
                            dateForm.submit();
                        });
                    }

                                });

                function openCourtModal(mode = 'create', courtId = null, courtName = '', courtStatus = 'open') {
                    const modal = document.getElementById('courtModal');
                    const form = document.getElementById('courtForm');
                    const courtIdInput = document.getElementById('court_id');
                    const methodInput = document.getElementById('court_form_method');
                    const title = document.getElementById('courtModalTitle');
                    const subtitle = document.getElementById('courtModalSubtitle');
                    const submit = document.getElementById('courtModalSubmit');
                    const nameInput = document.getElementById('court_name');
                    const statusInput = document.getElementById('court_status');

                    if (!modal) {
                        return;
                    }

                    // เคลียร์ error message และ error style เก่าทุกครั้งที่เปิด modal
                    clearFormErrors();

                    if (mode === 'edit' && courtId) {
                        if (form) {
                            form.action = `{{ url('/admin/courts') }}/${courtId}`;
                        }
                        if (methodInput) {
                            methodInput.value = 'PUT';
                        }
                        if (courtIdInput) {
                            courtIdInput.value = courtId;
                        }
                        if (title) {
                            title.innerText = 'แก้ไขสนาม';
                        }
                        if (subtitle) {
                            subtitle.innerText = 'อัปเดตชื่อและสถานะสนาม';
                        }
                        if (submit) {
                            submit.innerText = 'บันทึกการแก้ไข';
                        }
                        if (nameInput) {
                            nameInput.value = courtName;
                        }
                        if (statusInput) {
                            statusInput.value = courtStatus;
                        }
                    } else {
                        if (form) {
                            form.action = '{{ route('admin.court.create') }}';
                        }
                        if (methodInput) {
                            methodInput.value = '';
                        }
                        if (courtIdInput) {
                            courtIdInput.value = '';
                        }
                        if (title) {
                            title.innerText = 'เพิ่มสนาม';
                        }
                        if (subtitle) {
                            subtitle.innerText = 'กรอกชื่อและสถานะสนาม';
                        }
                        if (submit) {
                            submit.innerText = 'เพิ่มสนาม';
                        }
                        // reset ค่าฟอร์มกลับเป็นค่าว่างเสมอตอนเปิดโหมด "เพิ่มสนาม"
                        if (nameInput) {
                            nameInput.value = '';
                        }
                        if (statusInput) {
                            statusInput.value = 'open';
                        }
                    }

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');

                    if (nameInput && !nameInput.value) {
                        nameInput.focus();
                    }
                }

                function closeCourtModal() {
                    const modal = document.getElementById('courtModal');
                    if (!modal) {
                        return;
                    }

                    const form = document.getElementById('courtForm');
                    const methodInput = document.getElementById('court_form_method');
                    if (form) {
                        form.action = '{{ route('admin.court.create') }}';
                    }
                    if (methodInput) {
                        methodInput.value = '';
                    }

                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }

                document.getElementById('courtModal')?.addEventListener('click', function (event) {
                    if (event.target === this) {
                        closeCourtModal();
                    }
                });

                // ----- Error helpers -----
                const fieldErrorClasses = ['border-red-500', 'focus:border-red-500', 'focus:ring-red-500/20'];
                const fieldDefaultClasses = {
                    court_name: ['border-gray-300', 'focus:border-[#5271ff]'],
                    court_status: ['border-gray-300', 'focus:border-[#5271ff]'],
                };

                function clearFormErrors() {
                    const form = document.getElementById('courtForm');
                    if (!form) return;
                    form.querySelectorAll('[data-error-for]').forEach(el => el.remove());
                    Object.keys(fieldDefaultClasses).forEach(id => {
                        const el = document.getElementById(id);
                        if (!el) return;
                        el.classList.remove(...fieldErrorClasses);
                        el.classList.add(...fieldDefaultClasses[id]);
                    });
                }

                function setFieldError(fieldName, message) {
                    const inputId = fieldName === 'name' ? 'court_name' : (fieldName === 'court_status' ? 'court_status' : null);
                    if (!inputId) return;
                    const el = document.getElementById(inputId);
                    if (!el) return;

                    el.classList.remove(...(fieldDefaultClasses[inputId] || []));
                    el.classList.add(...fieldErrorClasses);

                    const p = document.createElement('p');
                    p.className = 'mt-1 text-xs text-red-600';
                    p.setAttribute('data-error-for', fieldName);
                    p.innerText = message;
                    el.insertAdjacentElement('afterend', p);
                }

                // ----- AJAX submit (ไม่ปิด modal / ไม่ reload หน้าเวลา validate ไม่ผ่าน) -----
                document.getElementById('courtForm')?.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const form = this;
                    const submitBtn = document.getElementById('courtModalSubmit');
                    const originalText = submitBtn ? submitBtn.innerText : '';

                    clearFormErrors();
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerText = 'กำลังบันทึก...';
                        submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
                    }

                    try {
                        const res = await fetch(form.action, {
                            method: 'POST', // _method ข้างในสั่ง spoof เป็น PUT ให้ตอน edit
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new FormData(form),
                        });

                        if (res.status === 422) {
                            const data = await res.json();
                            Object.entries(data.errors || {}).forEach(([field, messages]) => {
                                setFieldError(field, messages[0]);
                            });
                            Toast.fire({
                                icon: 'error',
                                title: 'กรุณาตรวจสอบข้อมูลให้ครบถ้วน'
                            });
                            return; // ✅ modal ไม่ปิด ไม่ reload — แค่โชว์ error ตรงช่อง
                        }

                        if (!res.ok) {
                            Toast.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง'
                            });
                            return;
                        }

                        const data = await res.json();
                        // ฝาก toast ไว้ใน sessionStorage ก่อนเปลี่ยนหน้า เพราะ response แบบ JSON
                        // ฝั่ง server ไม่ได้ flash session('success') ให้ (flash จะมีเฉพาะ response แบบ redirect ปกติ)
                        try {
                            sessionStorage.setItem('pendingToast', JSON.stringify({
                                icon: 'success',
                                title: data.message || 'บันทึกข้อมูลเรียบร้อยแล้ว'
                            }));
                        } catch (e) { }
                        // สำเร็จ ค่อยพาไปหน้าที่อัปเดตแล้ว (modal จะปิดเพราะเปลี่ยนหน้า)
                        window.location.href = data.redirect || window.location.href;
                    } catch (err) {
                        Toast.fire({
                            icon: 'error',
                            title: 'เชื่อมต่อไม่สำเร็จ กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองใหม่'
                        });
                    } finally {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerText = originalText;
                            submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
                        }
                    }
                });

                setInterval(() => {
                    let d = new Date();
                    let clock = document.getElementById('currentClock');
                    if (clock) {
                        clock.innerText = String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2,
                            '0');
                    }
                }, 60000);

                function deleteCourt(courtId, courtName) {
                    Swal.fire({
                        title: 'ยืนยันการลบสนาม?',
                        html: `คุณต้องการลบ <b>${courtName}</b> ใช่หรือไม่?<br><span class="text-sm text-red-500">(สามารถลบได้เฉพาะสนามที่ยังไม่มีการจองเท่านั้น)</span>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'ใช่, ลบสนาม',
                        cancelButtonText: 'ยกเลิก',
                        reverseButtons: true
                    }).then(async (result) => {
                        if (result.isConfirmed) {
                            try {
                                // ส่ง Request แบบ DELETE ไปยัง Route สำหรับลบ
                                const res = await fetch(`{{ url('/admin/courts') }}/${courtId}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json'
                                    }
                                });

                                const data = await res.json();

                                if (!res.ok) {
                                    // หาก Backend ตอบกลับว่าลบไม่ได้ (เช่น ติดการจอง)
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'ไม่สามารถลบได้!',
                                        text: data.message || 'สนามนี้มีการจองเกิดขึ้นแล้ว หรือมีข้อผิดพลาด'
                                    });
                                    return;
                                }

                                // กรณีลบสำเร็จ
                                Swal.fire({
                                    icon: 'success',
                                    title: 'ลบสำเร็จ!',
                                    text: data.message || 'ลบข้อมูลสนามเรียบร้อยแล้ว',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    // รีโหลดหน้า หรือส่งผู้ใช้กลับไปหน้าหลักของคอร์ท
                                    window.location.href = '{{ route("admin.courts") }}';
                                });

                            } catch (err) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'ข้อผิดพลาด!',
                                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                                });
                            }
                        }
                    });
                }

                function confirmMergeSection() {
                    const courtName = @js($selectedCourt->name ?? '');
                    Swal.fire({
                        icon: 'warning',
                        title: 'ยกเลิกการแบ่งครึ่งสนาม?',
                        html: `ยกเลิกการแบ่งครึ่งสนาม <b>${courtName}</b> ใช่หรือไม่?<br>
                               <span class="text-sm text-gray-500">(ประวัติการจองเดิมจะยังอยู่ แต่ลูกค้าจะจองได้เฉพาะเต็มสนามเท่านั้นต่อจากนี้)</span>`,
                        showCancelButton: true,
                        confirmButtonText: 'ใช่',
                        cancelButtonText: 'ไม่ใช่',
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        reverseButtons: true,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('mergeSectionForm').submit();
                        }
                    });
                }
            </script>
@endsection
