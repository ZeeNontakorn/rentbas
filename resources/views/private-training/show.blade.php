@extends('layouts.app')

@section('title', 'จองเทรนเนอร์ส่วนตัว: ' . $coach->name)

@section('content')
<style>
    .pt-tooltip {
        visibility: hidden;
        position: fixed;
        background-color: #1f2937;
        color: #ffffff;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 0.75rem;
        pointer-events: none;
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.2s;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
    }
    .pt-slot-selected {
        border: 2px solid #3b82f6 !important;
        transform: scale(0.95);
        border-radius: 4px;
        z-index: 10;
    }
</style>

<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-6 max-w-5xl">

        <a href="{{ route('private-training.index') }}"
            class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-orange-500 mb-6 transition font-medium group">
            <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            กลับไปหน้ารายชื่อโค้ช
        </a>

        <div id="pt-tooltip" class="pt-tooltip">
            <div id="pt-tt-title" class="font-bold border-b border-gray-700 pb-1 mb-1"></div>
            <div id="pt-tt-time" class="text-xs text-blue-300"></div>
        </div>

        {{-- โปรไฟล์โค้ช --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start gap-6">
                <div class="w-20 h-20 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0 border-2 border-white shadow-sm">
                    <span class="text-orange-600 text-3xl font-bold">{{ mb_strtoupper(mb_substr($coach->name, 0, 1)) }}</span>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-800">{{ $coach->name }}</h1>
                        <span class="px-3 py-1 text-xs rounded-full font-medium bg-blue-100 text-blue-700">ผู้ฝึกสอน (Coach)</span>
                    </div>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-y-3 text-sm text-gray-600">
                        <p class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            อีเมล: {{ $coach->email }}
                        </p>
                        <p class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            เบอร์โทรศัพท์: {{ $coach->phone ?? 'ไม่ระบุ' }}
                        </p>
                        <div class="col-span-1 md:col-span-2 mt-2">
                            <p><strong>ความเชี่ยวชาญ:</strong> {{ $staffProfile->specialty ?? 'ผู้ช่วยฝึกสอนเบสิค' }}</p>
                            <p class="mt-1"><strong>แนะนำตัว (Bio):</strong> {{ $staffProfile->bio ?? 'ไม่มีข้อมูล' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- คำขอที่ฉันจองไว้กับโค้ชคนนี้แล้ว --}}
        @if($myUpcoming->isNotEmpty())
            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5 mb-6">
                <h3 class="text-sm font-bold text-blue-700 mb-2">คิวที่คุณจองไว้กับโค้ชคนนี้</h3>
                <ul class="space-y-1 text-sm text-blue-700">
                    @foreach($myUpcoming as $u)
                        <li>
                            {{ \Carbon\Carbon::parse($u->date)->format('d/m/Y') }} — {{ substr($u->start_time, 0, 5) }} - {{ substr($u->end_time, 0, 5) }} น.
                            <span class="text-xs {{ $u->status === 'approved' ? 'text-green-600' : 'text-orange-600' }} font-medium">
                                ({{ $u->status === 'approved' ? 'อนุมัติแล้ว' : 'รออนุมัติ' }})
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ตารางว่าง --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6 select-none" id="pt-timeline-container">
            <div class="mb-4">
                <h3 class="font-bold text-gray-800 text-lg">ตารางว่างวันนี้</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ \Carbon\Carbon::parse($today)->format('d/m/Y') }} (08:00–22:00 น.) — คลิกหรือลากเพื่อเลือกช่วงเวลาที่ต้องการจอง</p>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-6 text-sm font-medium text-gray-700 mb-8 mt-2">
                <div class="flex items-center gap-2"><span class="w-4 h-4 bg-[#10b981] inline-block rounded"></span> ว่าง</div>
                <div class="flex items-center gap-2"><span class="w-4 h-4 bg-[#f97316] inline-block rounded"></span> ไม่ว่าง</div>
                <div class="flex items-center gap-2"><span class="w-4 h-4 bg-[#94a3b8] inline-block rounded"></span> ถูกจองแล้ว</div>
                <div class="flex items-center gap-2"><span class="w-4 h-4 bg-[#e2e8f0] inline-block rounded"></span> เวลาผ่านไปแล้ว</div>
            </div>

            <div class="overflow-x-auto pb-4">
                <div class="min-w-[900px]">
                    <div class="flex-1 flex gap-[2px]">
                        @foreach($timeline as $slot)
                            @php
                                $colorClass = match($slot['status']) {
                                    'unavailable' => 'bg-[#f97316]',
                                    'reserved' => 'bg-[#94a3b8]',
                                    'mine' => 'bg-[#3b82f6]',
                                    'past' => 'bg-[#e2e8f0]',
                                    default => 'bg-[#10b981]',
                                };
                                $isSelectable = $slot['status'] === 'available';
                                $statusLabel = match($slot['status']) {
                                    'unavailable' => 'ไม่ว่าง',
                                    'reserved' => 'ถูกจองแล้ว',
                                    'mine' => 'คุณจองไว้แล้ว',
                                    'past' => 'เวลาผ่านไปแล้ว',
                                    default => 'ว่าง',
                                };
                            @endphp
                            <div class="flex-1 relative group">
                                <div class="pt-time-slot h-12 w-full {{ $colorClass }} {{ $isSelectable ? 'cursor-pointer hover:opacity-85' : 'cursor-not-allowed opacity-90' }} transition-all relative border-2 border-transparent"
                                    data-hour="{{ $slot['hour'] }}" data-time-start="{{ $slot['start'] }}" data-time-end="{{ $slot['end'] }}"
                                    data-selectable="{{ $isSelectable ? '1' : '0' }}" data-status-label="{{ $statusLabel }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex items-center text-xs text-gray-500 mt-2">
                        <div class="flex-1 flex justify-between px-[2px]">
                            @foreach($timeline as $slot)
                                <div class="w-full text-left -ml-4">{{ $slot['start'] }}</div>
                            @endforeach
                            <div class="text-right -mr-4">22:00</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Modal ยืนยันการจอง --}}
<div id="bookTrainingModal"
    class="fixed inset-0 z-[60] hidden bg-gray-900/60 backdrop-blur-sm items-center justify-center p-4 transition-all">
    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden transform border border-gray-100 flex flex-col mx-auto">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-800">ยืนยันจองเทรนเนอร์ส่วนตัว</h3>
        </div>

        <form action="{{ route('private-training.store') }}" method="POST" class="p-6 space-y-4 bg-white">
            @csrf
            <input type="hidden" name="coach_id" value="{{ $coach->id }}">
            <input type="hidden" name="start_time" id="pt-modal-start">
            <input type="hidden" name="end_time" id="pt-modal-end">

            <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-center mb-2">
                <p class="text-xs text-blue-500 font-semibold mb-1">โค้ช {{ $coach->name }}</p>
                <p class="text-xs text-blue-500 mb-1">{{ \Carbon\Carbon::parse($today)->format('d/m/Y') }}</p>
                <div class="text-lg font-extrabold text-blue-700" id="pt-modal-time-range">00:00 - 00:00 น.</div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">หมายเหตุถึงโค้ช (ถ้ามี)</label>
                <textarea name="note" rows="2" maxlength="500" placeholder="เช่น อยากฝึกลูกยิงสามแต้ม"
                    class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition text-gray-900 bg-white resize-none"></textarea>
            </div>

            <p class="text-xs text-gray-400">คำขอของคุณจะถูกส่งไปให้แอดมินตรวจสอบและอนุมัติก่อนยืนยันการจอง</p>

            <div class="pt-2 flex gap-2">
                <button type="button" onclick="ptToggleModal(false)"
                    class="w-1/2 px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition font-medium">ยกเลิก</button>
                <button type="submit"
                    class="w-1/2 px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition font-medium shadow-sm">ส่งคำขอจอง</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function ptToggleModal(show) {
        const modal = document.getElementById('bookTrainingModal');
        if (!modal) return;
        modal.classList.toggle('hidden', !show);
        modal.classList.toggle('flex', show);
        if (!show) ptClearSelection();
    }

    let ptDragging = false;
    let ptSelected = [];
    const ptSlots = document.querySelectorAll('.pt-time-slot');
    const ptTooltip = document.getElementById('pt-tooltip');

    function ptClearSelection() {
        ptSlots.forEach(s => s.classList.remove('pt-slot-selected'));
        ptSelected = [];
    }

    function ptAddToSelection(el) {
        if (!ptSelected.includes(el)) {
            ptSelected.push(el);
            el.classList.add('pt-slot-selected');
        }
    }

    ptSlots.forEach(slot => {
        slot.addEventListener('mousedown', function (e) {
            if (this.getAttribute('data-selectable') !== '1') return;
            e.preventDefault();
            ptDragging = true;
            ptClearSelection();
            ptAddToSelection(this);
        });

        slot.addEventListener('mouseenter', function () {
            if (ptDragging && this.getAttribute('data-selectable') === '1') {
                ptAddToSelection(this);
            }
        });

        slot.addEventListener('mousemove', function (e) {
            if (!ptTooltip) return;
            ptTooltip.style.left = (e.clientX + 15) + 'px';
            ptTooltip.style.top = (e.clientY + 15) + 'px';
            ptTooltip.style.visibility = 'visible';
            ptTooltip.style.opacity = '1';
            document.getElementById('pt-tt-title').innerText = this.getAttribute('data-status-label');
            document.getElementById('pt-tt-time').innerText = `เวลา: ${this.getAttribute('data-time-start')} - ${this.getAttribute('data-time-end')} น.`;
        });

        slot.addEventListener('mouseleave', function () {
            if (!ptTooltip) return;
            ptTooltip.style.visibility = 'hidden';
            ptTooltip.style.opacity = '0';
        });
    });

    window.addEventListener('mouseup', function () {
        if (ptDragging && ptSelected.length > 0) {
            ptDragging = false;
            ptSelected.sort((a, b) => parseInt(a.getAttribute('data-hour')) - parseInt(b.getAttribute('data-hour')));
            const first = ptSelected[0];
            const last = ptSelected[ptSelected.length - 1];

            document.getElementById('pt-modal-time-range').innerText = `${first.getAttribute('data-time-start')} - ${last.getAttribute('data-time-end')} น.`;
            document.getElementById('pt-modal-start').value = first.getAttribute('data-time-start');
            document.getElementById('pt-modal-end').value = last.getAttribute('data-time-end');

            ptToggleModal(true);
        } else {
            ptDragging = false;
        }
    });

    document.addEventListener('click', function (e) {
        const modal = document.getElementById('bookTrainingModal');
        if (e.target === modal) ptToggleModal(false);
    });

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        @if (session()->has('success')) Toast.fire({ icon: 'success', title: @js(session('success')) }); @endif
        @if ($errors->any()) Toast.fire({ icon: 'error', title: @js($errors->first()) }); @endif
    });
</script>
@endsection
