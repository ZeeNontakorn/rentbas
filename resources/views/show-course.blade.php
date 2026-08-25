@extends('layouts.app')

@section('title', ($course->course_name ?? 'คอร์สเรียน') . ' - THATA Homecourt')

@section('content')
@php
    $package = $course->packages->first();
    $imagePlaceholder = "data:image/svg+xml;charset=UTF-8," . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500"><defs><linearGradient id="g" x1="0" x2="1" y1="0" y2="1"><stop stop-color="#242b4b"/><stop stop-color="#111526" offset="1"/></linearGradient></defs><rect width="800" height="500" fill="url(#g)"/><circle cx="400" cy="210" r="58" fill="#ff7a2f" fill-opacity=".25"/><path d="M280 340h240" stroke="#ff7a2f" stroke-width="18" stroke-linecap="round" stroke-opacity=".5"/></svg>');
    $imageFallback = "onerror=\"this.onerror=null;this.src='" . $imagePlaceholder . "';\"";
@endphp

<div class="min-h-screen bg-[#f7f7f3] pb-16 font-sans text-[#12182d]">
    <div class="mx-auto max-w-6xl px-4 pt-6 sm:px-6 lg:px-8">
        <p class="mb-5 text-sm text-slate-500">
            <a href="{{ route('home') }}" class="font-semibold text-[#d95512] transition hover:text-[#f36f21]">หน้าแรก</a>
            <span class="mx-1">/</span> คอร์สเรียนบาสเกตบอล <span class="mx-1">/</span> {{ $course->course_name }}
        </p>

        <section class="relative isolate min-h-[330px] overflow-hidden rounded-3xl bg-[#18203b] p-6 shadow-xl shadow-slate-900/15 sm:p-9">
            <img src="{{ $course->image_url ?: 'https://images.unsplash.com/photo-1546519638-68e109498ffc?q=80&w=1200&auto=format&fit=crop' }}"
                 alt="{{ $course->course_name }}" {!! $imageFallback !!}
                 class="absolute inset-0 -z-20 h-full w-full object-cover">
            <div class="absolute inset-0 -z-10 bg-gradient-to-r from-[#0a0e1e]/95 via-[#0a0e1e]/60 to-[#0a0e1e]/10"></div>

            <div class="flex min-h-[282px] max-w-3xl flex-col justify-end text-white">
                <span class="w-fit rounded-full bg-[#f36f21] px-3 py-1 text-xs font-bold">
                    {{ $package && $package->is_featured ? '★ คอร์สแนะนำ' : 'THATA HOMECOURT' }}
                </span>
                <h1 class="mt-3 text-3xl font-extrabold leading-tight sm:text-5xl">{{ $course->course_name }}</h1>
                @if($course->description)
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-white/80 sm:text-base">{{ $course->description }}</p>
                @endif
                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach($course->targetGroups as $group)
                        <span class="rounded-full border border-white/30 bg-slate-950/20 px-3 py-1 text-xs">{{ $group->target_group }}</span>
                    @endforeach
                    @if($course->course_type === 'schedule')<span class="rounded-full border border-white/30 bg-slate-950/20 px-3 py-1 text-xs">อายุ {{ $course->age_range_label }}</span>@else<span class="rounded-full border border-white/30 bg-slate-950/20 px-3 py-1 text-xs">Session Course</span>@endif
                    @if($package)<span class="rounded-full border border-white/30 bg-slate-950/20 px-3 py-1 text-xs">{{ $package->package_type_label }}</span>@endif
                </div>
            </div>
        </section>

        <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1.45fr)_minmax(290px,.8fr)]">
            <main class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-xl font-extrabold">{{ $course->course_type === 'session' ? 'รายละเอียด Session' : 'รอบเวลาเรียน' }}</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">{{ $course->course_type === 'session' ? 'ดูภาพประกอบและรายละเอียดราคา แล้วแอดไลน์สอบถามเพื่อสมัครได้เลย' : 'เลือกรอบที่สะดวกไว้ในใจ แล้วแอดไลน์สอบถามที่ว่างและสมัครเรียนกับแอดมินได้เลย' }}</p>

                <div class="mt-5 grid gap-3">
                    @forelse($course->schedules as $schedule)
                        @php
                            $time = \Illuminate\Support\Carbon::parse($schedule->start_time)->format('H:i') . '–' . \Illuminate\Support\Carbon::parse($schedule->end_time)->format('H:i');
                        @endphp
                        <div class="grid grid-cols-[auto_1fr] items-center gap-x-3 gap-y-2 rounded-xl border border-slate-200 p-4 sm:grid-cols-[auto_1fr_auto]">
                            <span class="grid h-5 w-5 place-items-center rounded-full bg-orange-100 text-[10px] text-[#d95512]">●</span>
                            <div>
                                <p class="font-bold text-slate-800">{{ $schedule->day_type_label }}</p>
                                <p class="mt-0.5 text-sm text-slate-500">{{ $time }} · {{ $schedule->duration_label }}</p>
                            </div>
                            <span class="col-start-2 w-fit rounded-full px-2.5 py-1 text-xs font-bold sm:col-start-auto {{ $schedule->is_limited_spots ? 'bg-orange-50 text-amber-700' : 'bg-slate-100 text-slate-500' }}">{{ $schedule->spots_label }}</span>
                        </div>
                    @empty
                        <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">{{ $course->course_type === 'session' ? 'Session นี้เป็นคอร์สรายครั้ง กรุณาดูภาพประกอบและติดต่อแอดมินเพื่อสอบถามวันจัดกิจกรรม' : 'ยังไม่มีการกำหนดรอบเวลาเรียน กรุณาติดต่อแอดมิน' }}</p>
                    @endforelse
                </div>

                <div class="mt-5 flex items-center gap-3 rounded-xl bg-slate-50 p-3.5 text-sm leading-6 text-slate-600">
                    <span class="text-base">💬</span>
                    <span>จำนวนที่ว่างอาจเปลี่ยนแปลง กรุณายืนยันกับแอดมินก่อนสมัครเรียน</span>
                </div>
            </main>

            <aside class="lg:sticky lg:top-5 lg:self-start">
                @if($course->packages->isNotEmpty())
                    <section class="overflow-hidden rounded-2xl bg-[#18203b] p-6 text-white shadow-lg shadow-slate-900/10">
                        <p class="text-xs text-white/60">เลือกแพ็กเกจที่ต้องการ</p>
                        <div class="mt-4 space-y-3">
                            @foreach($course->packages as $coursePackage)
                                <div class="rounded-xl border border-white/10 bg-white/5 p-3">
                                    <div class="flex items-baseline justify-between gap-2"><p class="font-bold">{{ $coursePackage->total_sessions }} ครั้ง</p><p class="text-xl font-extrabold text-[#ff914d]">฿{{ number_format($coursePackage->total_price, 0) }}</p></div>
                                    <p class="mt-1 text-xs text-white/60">เฉลี่ย {{ number_format($coursePackage->price_per_session, 0) }} บาท/ครั้ง · อายุ {{ $coursePackage->validity_label }}</p>
                                    @if($coursePackage->recommendation_text)<p class="mt-1 text-xs text-white/80">{{ $coursePackage->recommendation_text }}</p>@endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <div class="mt-4 rounded-xl border-l-4 border-[#f36f21] bg-orange-50 p-4 text-sm leading-6 text-stone-600">
                    ตอนนี้ระบบยังไม่มีฟีเจอร์ซื้อคอร์สเรียนออนไลน์ กรุณาติดต่อแอดมินผ่านแอปพลิเคชันไลน์ เพื่อสอบถามรายละเอียด และจองคอร์สเรียนได้โดยตรง
                </div>
                <a href="{{ \App\Models\Setting::getVal('line_course_url', 'https://line.me/R/ti/p/%40THATA-HC') }}" target="_blank" rel="noopener"
                   class="mt-4 flex w-full items-center justify-center rounded-xl bg-green-500 px-4 py-3.5 text-sm font-bold text-white shadow-md shadow-green-200 transition hover:-translate-y-0.5 hover:bg-green-600">
                    แอดไลน์สอบถามและสมัครเรียน
                </a>
                @php
                    $contactPhone = \App\Models\Setting::getVal('contact_phone', '081-246-0000');
                @endphp
                <p class="mt-3 text-center text-xs text-slate-500">หรือติดต่อ <a class="font-bold text-[#d95512]" href="tel:{{ preg_replace('/\D/', '', $contactPhone) }}">{{ $contactPhone }}</a></p>
            </aside>
        </div>

    </div>
</div>
@endsection
