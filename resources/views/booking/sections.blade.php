@extends('layouts.app')

@section('title', 'จองสนาม - เลือกครึ่งสนาม')

@section('content')
<div class="bg-white min-h-screen text-[#111827]">

<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');
.bk-main { font-family: 'Sarabun', 'Kanit', sans-serif; }
.bk-main h1, .bk-main h2, .bk-main h3 { font-family: 'Kanit', sans-serif; }

.wizard-steps { display: flex; align-items: center; gap: 6px; font-family: 'Kanit', sans-serif; font-size: 12px; color: #9ca3af; flex-wrap: wrap; }
.wizard-steps .step { display: flex; align-items: center; gap: 6px; }
.wizard-steps .num {
    width: 22px; height: 22px; border-radius: 999px; display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 11px; background: #f3f4f6; color: #9ca3af;
}
.wizard-steps .step.active .num, .wizard-steps .step.done .num { background: #87D068; color: #fff; }
.wizard-steps .step.active { color: #111827; font-weight: 700; }
.wizard-steps .sep { color: #d1d5db; }

.sec-card {
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    border: 1.5px solid #e5e7eb; border-radius: 12px; padding: 20px; transition: all .15s;
}
.sec-card.available { cursor: pointer; }
.sec-card.available:hover { border-color: #87D068; box-shadow: 0 4px 14px rgba(0,0,0,0.06); }
.sec-card.unavailable { opacity: .5; }
.sec-icon {
    width: 48px; height: 48px; border-radius: 10px; background: #f7fdf4; display: flex;
    align-items: center; justify-content: center; flex-shrink: 0;
}
</style>

<div class="bk-main max-w-[720px] mx-auto px-4 py-10" data-aos="fade-up">

    <div class="wizard-steps mb-8">
        <div class="step done"><span class="num">✓</span><span>เลือกวัน &amp; เวลาที่ต้องการ</span></div>
        <span class="sep">›</span>
        <div class="step done"><span class="num">✓</span><span>เลือกสนาม</span></div>
        <span class="sep">›</span>
        <div class="step active"><span class="num">3</span><span>เลือกครึ่งสนาม</span></div>
        <span class="sep">›</span>
        <div class="step"><span class="num">4</span><span>เลือกเวลา</span></div>
        <span class="sep">›</span>
        <div class="step"><span class="num">5</span><span>ชำระเงิน</span></div>
    </div>

    @php
        $cDate = \Carbon\Carbon::parse($date);
        $thDays = ['อาทิตย์','จันทร์','อังคาร','พุธ','พฤหัสบดี','ศุกร์','เสาร์'];
        $thMonthsFull = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
    @endphp

    <div class="flex items-baseline justify-between flex-wrap gap-3 mb-2">
        <h1 class="text-[26px] font-bold text-gray-900 tracking-tight">{{ $court->name }} — เลือกครึ่งสนาม</h1>
        <a href="{{ route('booking.courts', ['date' => $date, 'duration_minutes' => $duration]) }}" class="text-[13px] text-gray-500 hover:text-gray-800">← เลือกสนามอื่น</a>
    </div>
    <p class="text-gray-500 text-[14px] mb-8">
        วันที่ {{ $thDays[$cDate->dayOfWeek] }} {{ $cDate->day }} {{ $thMonthsFull[$cDate->month] }} {{ $cDate->year + 543 }}
        · ต้องการเล่น <span class="font-bold text-gray-800">{{ $duration }} นาที</span>
    </p>

    <div class="flex flex-col gap-3">
        @forelse($sectionOptions as $sec)
            @if($sec['available'])
                <a href="{{ route('booking.calendar', ['court_id' => $court->id, 'section_id' => $sec['id'], 'date' => $date, 'duration_minutes' => $duration]) }}"
                   class="sec-card available">
                    <div class="flex items-center gap-4">
                        <div class="sec-icon">
                            <svg class="w-6 h-6 text-[#87D068]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="3" y="4" width="18" height="16" rx="2" stroke-width="2"/>
                                @if($sec['code'] !== 'full')
                                    <path d="M12 4v16" stroke-width="2"/>
                                @endif
                            </svg>
                        </div>
                        <div>
                            <div class="font-bold text-[16px] text-gray-900">{{ $sec['name'] }}</div>
                            <div class="text-[12.5px] text-gray-500 mt-0.5">
                                เร็วที่สุด {{ $sec['earliest'] }} น.
                            </div>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @else
                <div class="sec-card unavailable">
                    <div class="flex items-center gap-4">
                        <div class="sec-icon" style="background:#f3f4f6;">
                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="3" y="4" width="18" height="16" rx="2" stroke-width="2"/>
                                @if($sec['code'] !== 'full')
                                    <path d="M12 4v16" stroke-width="2"/>
                                @endif
                            </svg>
                        </div>
                        <div>
                            <div class="font-bold text-[16px] text-gray-500">{{ $sec['name'] }}</div>
                            <div class="text-[12.5px] text-gray-400 mt-0.5">ไม่มีช่วงเวลาว่างติดกันพอสำหรับ {{ $duration }} นาที</div>
                        </div>
                    </div>
                </div>
            @endif
        @empty
            <div class="text-center py-16 text-gray-400">สนามนี้ยังไม่เปิดให้จอง</div>
        @endforelse
    </div>
</div>
</div>
@endsection
