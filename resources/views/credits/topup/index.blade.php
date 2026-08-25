@extends('layouts.app')

@section('title', 'เติมเครดิต')

@section('content')
<div class="bg-white min-h-screen text-[#111827]">
<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');
.tu-main { font-family: 'Sarabun', 'Kanit', sans-serif; }
.tu-main h1, .tu-main h2, .tu-main h3 { font-family: 'Kanit', sans-serif; }

.pkg-card {
    border: 1.5px solid #e5e7eb; border-radius: 14px; padding: 22px 16px; text-align: center; cursor: pointer;
    transition: all .15s; position: relative;
}
.pkg-card:hover { border-color: #87D068; box-shadow: 0 4px 14px rgba(0,0,0,0.06); }
.pkg-card input { position: absolute; opacity: 0; }
.pkg-card input:checked ~ .pkg-check {
    border-color: #87D068; background: #87D068;
}
.pkg-card input:checked ~ .pkg-check svg { opacity: 1; }
.pkg-card:has(input:checked) { border-color: #87D068; background: #f7fdf4; box-shadow: 0 0 0 2px rgba(135,208,104,0.25); }
.pkg-check {
    width: 20px; height: 20px; border-radius: 999px; border: 1.5px solid #d1d5db; margin: 0 auto 10px;
    display: flex; align-items: center; justify-content: center; transition: all .15s;
}
.pkg-check svg { width: 12px; height: 12px; color: #fff; opacity: 0; }
.pkg-bonus {
    position: absolute; top: -10px; right: -8px; background: #fbbf24; color: #78350f; font-size: 10px;
    font-weight: 700; border-radius: 999px; padding: 2px 8px; font-family: 'Kanit', sans-serif;
}
.line-cta {
    display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%;
    background: #06C755; color: #fff; font-weight: 700; padding: 14px; border-radius: 12px;
    font-family: 'Kanit', sans-serif; transition: all .15s;
}
.line-cta:hover { background: #05b34c; }
.status-pill { font-size: 11px; font-weight: 700; border-radius: 999px; padding: 2px 10px; font-family: 'Kanit', sans-serif; }
.status-pending { background: #fef3c7; color: #92400e; }
.status-approved { background: #dcfce7; color: #166534; }
.status-rejected { background: #fee2e2; color: #991b1b; }
</style>

<div class="tu-main max-w-2xl mx-auto px-4 py-10" data-aos="fade-up">
    @php
        $expiresAt = auth()->user()->credit_expires_at;

        // เครดิตจะถูกตัดยอดจริงตอน scheduler รอบ 01:00 ที่ >= credit_expires_at
        // (job credits:expire-due รันวันละครั้งตอนตี 1 — ดู routes/console.php)
        // ไม่ใช่ตัดทันทีตอนถึงเวลา credit_expires_at เป๊ะๆ จึงต้องเลื่อนไปหารอบตัดจริงก่อนเอาไปนับถอยหลัง
        $creditCutoff = null;
        if ($expiresAt) {
            $creditCutoff = $expiresAt->copy()->setTime(1, 0, 0);
            if ($creditCutoff->lt($expiresAt)) {
                $creditCutoff->addDay();
            }
        }
    @endphp

    <div class="flex items-start justify-between flex-wrap gap-2 mb-1">
        <h1 class="text-[28px] font-bold text-gray-900 tracking-tight">เติมเครดิต</h1>
        <div class="flex flex-col items-end gap-0.5">
            <span class="text-[13px] text-gray-400">ยอดคงเหลือ <span class="font-bold text-gray-700">฿{{ number_format(auth()->user()->credit_balance / 100, 2) }}</span></span>
            @if($creditCutoff)
                <span class="text-[12px] text-gray-400">
                    หมดอายุภายใน {{ $creditCutoff->format('d/m/Y H:i') }} น.
                </span>
                <span id="creditCountdown" data-expires-at="{{ $creditCutoff->toIso8601String() }}" class="font-bold text-amber-700" style="font-family:'Kanit',sans-serif;">—</span>
            @endif
        </div>
    </div>
    <p class="text-gray-500 text-[14px] mb-6">เลือกแพ็กเกจ หรือกรอกจำนวนเงินที่ต้องการเติมเอง</p>

    @if (session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">
            @foreach ($errors->all() as $err)<div>• {{ $err }}</div>@endforeach
        </div>
    @endif

    @if($lineUrl)
        <a href="{{ $lineUrl }}" target="_blank" rel="noopener" class="line-cta mb-8">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 5.94 2 10.7c0 4.27 3.55 7.85 8.34 8.55.32.07.77.21.88.49.1.25.07.65.03.9l-.14.9c-.04.25-.2.98.86.53 1.06-.44 5.7-3.36 7.78-5.75C21.24 13.9 22 12.4 22 10.7 22 5.94 17.52 2 12 2z"/></svg>
            เติมผ่านไลน์ — แอดไลน์แอดมิน
        </a>
    @endif

    <form method="GET" action="{{ route('credits.topup.checkout') }}" id="pkgForm">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            @foreach($packages as $pkg)
                <label class="pkg-card">
                    <input type="radio" name="package_id" value="{{ $pkg->id }}" onchange="document.getElementById('customAmount').value=''" {{ $loop->first ? 'checked' : '' }}>
                    @if($pkg->bonus_satang > 0)
                        <span class="pkg-bonus">+฿{{ number_format($pkg->bonus_satang / 100, 0) }}</span>
                    @endif
                    <span class="pkg-check"><svg fill="currentColor" viewBox="0 0 20 20"><path d="M16.7 5.3a1 1 0 010 1.4l-7.4 7.4a1 1 0 01-1.4 0L3.3 9.5a1 1 0 111.4-1.4l3.9 3.9 6.7-6.7a1 1 0 011.4 0z"/></svg></span>
                    <div class="font-black text-[22px] text-gray-900" style="font-family:'Kanit',sans-serif;">฿{{ number_format($pkg->price_satang / 100, 0) }}</div>
                    <div class="text-[11px] text-gray-400 mt-0.5">รับ {{ number_format($pkg->credit_satang / 100, 0) }} เครดิต</div>
                </label>
            @endforeach
        </div>

        <div class="border border-gray-300 rounded-xl p-5 mb-6">
            <label class="font-bold text-[14px] text-gray-900 block mb-2">หรือระบุจำนวนเงินเอง</label>
            <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold">฿</span>
                <input type="number" name="custom_amount" id="customAmount" min="20" max="100000" step="1"
                       placeholder="ขั้นต่ำ 20 บาท" onfocus="document.querySelectorAll('#pkgForm input[type=radio]').forEach(r => r.checked = false)"
                       class="w-full pl-8 pr-4 py-3 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
            </div>
        </div>

        <button type="submit" class="w-full bg-[#87D068] hover:bg-[#76bc5a] text-white font-bold py-3.5 rounded-lg shadow transition" style="font-family:'Kanit',sans-serif;">
            ถัดไป — เติมเครดิต
        </button>
    </form>



    @if($myRequests->isNotEmpty())
        <div class="mt-10">
            <h2 class="font-bold text-[15px] text-gray-900 mb-3">คำขอเติมเครดิตล่าสุด</h2>
            <div class="flex flex-col gap-2">
                @foreach($myRequests as $req)
                    @php
                        $pillClass = ['pending' => 'status-pending', 'approved' => 'status-approved', 'rejected' => 'status-rejected'][$req->status];
                    @endphp
                    <div class="flex items-center justify-between border border-gray-200 rounded-lg px-4 py-3 text-sm">
                        <div>
                            <span class="font-semibold text-gray-800">฿{{ number_format($req->price_satang / 100, 0) }}</span>
                            <span class="text-gray-400 text-[12px] ml-2">{{ $req->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <span class="status-pill {{ $pillClass }}">{{ $req->status_label }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
</div>

@if($creditCutoff)
    @push('scripts')
    <script>
    (function () {
        const el = document.getElementById('creditCountdown');
        if (!el) return;
        const expiresAt = new Date(el.dataset.expiresAt).getTime();

        function tick() {
            const diff = expiresAt - Date.now();
            if (diff <= 0) {
                el.textContent = 'ตัดยอดแล้ว';
                clearInterval(timer);
                return;
            }
            const d = Math.floor(diff / 86400000);
            const h = Math.floor((diff % 86400000) / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);
            el.textContent = `เหลือ ${d} วัน ${h} ชม. ${m} นาที ${s} วิ`;
        }

        tick();
        const timer = setInterval(tick, 1000);
    })();
    </script>
    @endpush
@endif
@endsection
