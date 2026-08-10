@extends('layouts.app')

@section('title', 'จองสนาม - เลือกสนาม')

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

.court-card {
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    border: 1.5px solid #e5e7eb; border-radius: 12px; padding: 18px 20px; transition: all .15s;
}
.court-card.available { cursor: pointer; }
.court-card.available:hover { border-color: #87D068; box-shadow: 0 4px 14px rgba(0,0,0,0.06); }
.court-card.unavailable { opacity: .5; }
.court-badge {
    font-size: 11px; font-weight: 700; border-radius: 999px; padding: 3px 10px;
    font-family: 'Kanit', sans-serif;
}
.court-badge.best { background: #f0faec; color: #4a8f2c; }
.court-badge.none { background: #f3f4f6; color: #9ca3af; }
</style>

<div class="bk-main max-w-[760px] mx-auto px-4 py-10" data-aos="fade-up">

    <div class="wizard-steps mb-8">
        <div class="step done"><span class="num">✓</span><span>เลือกวัน &amp; เวลาที่ต้องการ</span></div>
        <span class="sep">›</span>
        <div class="step active"><span class="num">2</span><span>เลือกสนาม</span></div>
        <span class="sep">›</span>
        <div class="step"><span class="num">3</span><span>เลือกครึ่งสนาม</span></div>
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
        <h1 class="text-[26px] font-bold text-gray-900 tracking-tight">เลือกสนาม</h1>
        <a href="{{ route('booking.index', ['date' => $date, 'duration_minutes' => $duration]) }}" class="text-[13px] text-gray-500 hover:text-gray-800">← แก้วัน/เวลา</a>
    </div>
    <p class="text-gray-500 text-[14px] mb-8">
        วันที่ {{ $thDays[$cDate->dayOfWeek] }} {{ $cDate->day }} {{ $thMonthsFull[$cDate->month] }} {{ $cDate->year + 543 }}
        · ต้องการเล่น <span class="font-bold text-gray-800">{{ $duration }} นาที</span>
        — ระบบเรียงสนามที่มีช่วงเวลาว่างติดกันพอดี และเริ่มเล่นได้เร็วที่สุดไว้ให้ด้านบน
    </p>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">
            @foreach ($errors->all() as $err)
                <div>• {{ $err }}</div>
            @endforeach
        </div>
    @endif

    <div class="flex flex-col gap-3">
        @forelse($courtOptions as $i => $opt)
            @php $court = $opt['court']; @endphp
            @if($opt['available'])
                <a href="{{ route('booking.sections', ['court_id' => $court->id, 'date' => $date, 'duration_minutes' => $duration]) }}"
                   class="court-card available">
                    <div class="flex items-center gap-3">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                        <div>
                            <div class="font-bold text-[16px] text-gray-900">{{ $court->name }}</div>
                            <div class="text-[12.5px] text-gray-500 mt-0.5">
                                เร็วที่สุด {{ $opt['earliest'] }} น.
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($i === 0)
                            <span class="court-badge best">แนะนำ</span>
                        @endif
                        <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            @else
                <div class="court-card unavailable">
                    <div class="flex items-center gap-3">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-gray-300 flex-shrink-0"></span>
                        <div>
                            <div class="font-bold text-[16px] text-gray-500">{{ $court->name }}</div>
                            <div class="text-[12.5px] text-gray-400 mt-0.5">ไม่มีช่วงเวลาว่างติดกันพอสำหรับ {{ $duration }} นาทีในวันนี้</div>
                        </div>
                    </div>
                    <span class="court-badge none">เต็ม</span>
                </div>
            @endif
        @empty
            <div class="text-center py-16 text-gray-400">ไม่พบสนามในระบบ</div>
        @endforelse
    </div>
</div>
</div>
@endsection
