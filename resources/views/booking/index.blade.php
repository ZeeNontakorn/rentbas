@extends('layouts.app')

@section('title', 'จองสนาม - เลือกวันและเวลา')

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
.wizard-steps .step.active .num { background: #87D068; color: #fff; }
.wizard-steps .step.active { color: #111827; font-weight: 700; }
.wizard-steps .sep { color: #d1d5db; }

.duration-btn {
    width: 44px; height: 44px; border-radius: 999px; border: 1.5px solid #e5e7eb; background: #fff;
    display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700;
    color: #111827; cursor: pointer; transition: all .15s; user-select: none;
}
.duration-btn:hover { border-color: #87D068; color: #87D068; }
.duration-btn:active { transform: scale(0.94); }
.duration-btn:disabled { opacity: .35; cursor: not-allowed; }
.duration-btn:disabled:hover { border-color: #e5e7eb; color: #111827; }

.duration-quick {
    padding: 6px 14px; border-radius: 999px; border: 1.5px solid #e5e7eb; font-size: 13px; font-weight: 600;
    color: #4b5563; cursor: pointer; transition: all .15s; font-family: 'Kanit', sans-serif;
}
.duration-quick:hover { border-color: #87D068; color: #87D068; }
.duration-quick.active { border-color: #87D068; background: #87D068; color: #fff; }
</style>

<div class="bk-main max-w-[720px] mx-auto px-4 py-10" data-aos="fade-up">

    {{-- Wizard progress --}}
    <div class="wizard-steps mb-8">
        <div class="step active"><span class="num">1</span><span>เลือกวัน &amp; เวลาที่ต้องการ</span></div>
        <span class="sep">›</span>
        <div class="step"><span class="num">2</span><span>เลือกสนาม</span></div>
        <span class="sep">›</span>
        <div class="step"><span class="num">3</span><span>เลือกครึ่งสนาม</span></div>
        <span class="sep">›</span>
        <div class="step"><span class="num">4</span><span>เลือกเวลา</span></div>
        <span class="sep">›</span>
        <div class="step"><span class="num">5</span><span>ชำระเงิน</span></div>
    </div>

    <h1 class="text-[28px] font-bold text-gray-900 tracking-tight mb-2">จองสนาม</h1>
    <p class="text-gray-500 text-[14px] mb-8">เริ่มจากเลือกวันที่และระบุจำนวนเวลาที่ต้องการเล่น ระบบจะช่วยเรียงสนามที่มีช่วงว่างพอดีให้เลือกในขั้นตอนถัดไป</p>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">
            @foreach ($errors->all() as $err)
                <div>• {{ $err }}</div>
            @endforeach
        </div>
    @endif

    <form id="wizardForm" method="GET" action="{{ route('booking.courts') }}" class="flex flex-col gap-8">

        {{-- Step 1: เลือกวัน --}}
        <div class="border border-gray-300 rounded-xl p-6">
            <span class="font-bold text-[15px] text-gray-900 block mb-4">1. เลือกวัน</span>
            <div class="relative w-full max-w-[280px] rounded border border-purple-400 p-[1px] focus-within:border-purple-600 focus-within:ring-1 focus-within:ring-purple-600">
                <span class="absolute -top-2.5 left-2 bg-white px-1 text-[10px] font-bold text-purple-600 tracking-wider">Date</span>
                <input type="date" name="date" id="dateInput" value="{{ $date }}"
                       min="{{ now()->toDateString() }}" max="{{ now()->addDays($maxAdvanceDays)->toDateString() }}"
                       class="w-full text-sm text-gray-700 p-2.5 outline-none bg-transparent" required>
            </div>
            <p class="text-[12px] text-gray-400 mt-2">จองล่วงหน้าได้สูงสุด {{ $maxAdvanceDays }} วัน</p>
        </div>

        {{-- Step 2: กรอกจำนวนเวลา --}}
        <div class="border border-gray-300 rounded-xl p-6">
            <span class="font-bold text-[15px] text-gray-900 block mb-1">2. ระบุจำนวนเวลาที่ต้องการเล่น</span>
            <p class="text-[12px] text-gray-400 mb-5">เริ่มต้น {{ $minDuration }} นาที ปรับได้ทีละ {{ $stepDuration }} นาที สูงสุด {{ $maxDuration }} นาที ({{ $maxDuration / 60 }} ชม.)</p>

            <div class="flex items-center gap-5">
                <button type="button" id="durMinus" class="duration-btn" aria-label="ลดเวลา">−</button>
                <div class="text-center min-w-[140px]">
                    <div id="durationLabel" class="font-black text-[32px] text-gray-900 leading-none" style="font-family:'Kanit',sans-serif;">{{ $duration }} <span class="text-[16px] font-bold text-gray-400">นาที</span></div>
                    <div id="durationHoursLabel" class="text-[12px] text-gray-400 mt-1"></div>
                </div>
                <button type="button" id="durPlus" class="duration-btn" aria-label="เพิ่มเวลา">+</button>
            </div>
            <input type="hidden" name="duration_minutes" id="durationInput" value="{{ $duration }}">

            <div class="flex flex-wrap gap-2 mt-6">
                @foreach([60, 120, 180, 240, 300] as $q)
                    <div class="duration-quick" data-value="{{ $q }}">{{ $q }} นาที</div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-[#87D068] hover:bg-[#76bc5a] text-white font-bold py-3 px-10 rounded-lg shadow transition" style="font-family:'Kanit',sans-serif;">
                ถัดไป — ดูสนามที่ว่าง
            </button>
        </div>
    </form>
</div>
</div>

@push('scripts')
<script>
(function () {
    const MIN = {{ $minDuration }};
    const MAX = {{ $maxDuration }};
    const STEP = {{ $stepDuration }};
    const input = document.getElementById('durationInput');
    const label = document.getElementById('durationLabel');
    const hoursLabel = document.getElementById('durationHoursLabel');
    const minusBtn = document.getElementById('durMinus');
    const plusBtn = document.getElementById('durPlus');

    let value = parseInt(input.value, 10) || 60;

    function formatHours(mins) {
        const h = Math.floor(mins / 60);
        const m = mins % 60;
        if (h === 0) return '';
        return h + ' ชม.' + (m ? ' ' + m + ' นาที' : '');
    }

    function render() {
        input.value = value;
        label.innerHTML = value + ' <span class="text-[16px] font-bold text-gray-400">นาที</span>';
        hoursLabel.textContent = formatHours(value);
        minusBtn.disabled = value <= MIN;
        plusBtn.disabled = value >= MAX;
        document.querySelectorAll('.duration-quick').forEach(el => {
            el.classList.toggle('active', parseInt(el.dataset.value, 10) === value);
        });
    }

    minusBtn.addEventListener('click', () => { value = Math.max(MIN, value - STEP); render(); });
    plusBtn.addEventListener('click', () => { value = Math.min(MAX, value + STEP); render(); });
    document.querySelectorAll('.duration-quick').forEach(el => {
        el.addEventListener('click', () => { value = parseInt(el.dataset.value, 10); render(); });
    });

    render();
})();
</script>
@endpush
@endsection
