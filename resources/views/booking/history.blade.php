@extends('layouts.app')

@section('title', 'ประวัติการจอง - Booking History')

@section('content')
<div class="bg-white min-h-screen text-[#111827]">

<style>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600&display=swap');

.hist-main { font-family: 'Sarabun', 'Kanit', sans-serif; }
.hist-main h1, .hist-main h2, .hist-main h3 { font-family: 'Kanit', sans-serif; }

/* ─── CARD STYLES ─── */
.b-card {
    background: #fff;
    border-radius: 8px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    border: 1px solid #e5e7eb;
    transition: border-color 0.2s;
}
@media(min-width: 768px) {
    .b-card {
        flex-direction: row;
        align-items: center;
        padding: 16px 20px;
    }
}
.b-card:hover {
    border-color: #d1d5db;
}

.badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 6px;
    font-size: 11px; font-weight: 600; font-family: 'Kanit', sans-serif;
}
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-pending_payment { background: #ffedd5; color: #9a3412; }
.badge-approved { background: #ecfdf5; color: #047857; }
.badge-rejected { background: #fef2f2; color: #b91c1c; }
.badge-cancelled { background: #f3f4f6; color: #4b5563; }
.badge-expired { background: #e5e7eb; color: #4b5563; }

/* ─── PAY NOW BUTTON + COUNTDOWN ─── */
.btn-pay-now {
    background: #87D068;
    color: #fff;
    border: none;
    font-size: 12px; font-weight: 700; font-family: 'Kanit', sans-serif;
    padding: 7px 14px;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: 5px;
}
.btn-pay-now:hover { background: #76bc5a; }
.pay-countdown {
    display: inline-flex; align-items: center; gap: 4px;
    font-family: 'Kanit', sans-serif; font-size: 11px; font-weight: 700;
    color: #b45309;
}
.pay-countdown.danger { color: #b91c1c; }

/* ─── CANCEL BUTTON ─── */
.btn-cancel {
    background: #fff;
    color: #ef4444;
    border: 1px solid #fecaca;
    font-size: 12px; font-weight: 600;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
}
.btn-cancel:hover { background: #fef2f2; }

/* ─── TABS ─── */
.tab-btn {
    padding: 8px 16px;
    font-size: 15px; font-weight: 500;
    color: #64748b;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    font-family: 'Kanit', sans-serif;
}
.tab-btn.active {
    color: #111827;
    border-bottom-color: #87D068;
}
.tab-btn:hover:not(.active) { color: #334155; }
</style>

@php
    $thMonths = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];

    function getStatusDetails($status) {
        return match($status) {
            'pending' => ['st-pending', 'badge-pending', 'รอการอนุมัติ', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />'],
            'pending_payment' => ['st-pending-payment', 'badge-pending_payment', 'รอชำระเงิน', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-9 4h16a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />'],
            'approved' => ['st-approved', 'badge-approved', 'อนุมัติแล้ว', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />'],
            'rejected' => ['st-rejected', 'badge-rejected', 'ถูกปฏิเสธ', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />'],
            'cancelled' => ['st-cancelled', 'badge-cancelled', 'ยกเลิก', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />'],
            'expired' => ['st-expired', 'badge-expired', 'เลยกำหนด', '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />'],
            default => ['st-cancelled', 'badge-cancelled', $status, ''],
        };
    }
@endphp

<div class="hist-main max-w-[1200px] mx-auto px-4 py-8" x-data="{ tab: 'current' }" data-aos="fade-up">

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-baseline gap-4" data-aos="fade-right">
        <div class="flex flex-col gap-1">
            <h1 class="text-[32px] font-bold text-gray-900 tracking-tight">การจองของฉัน</h1>
            <span class="text-gray-600 text-[15px]">ประวัติการจองสนามบาสเกตบอล</span>
        </div>
        <a href="{{ route('booking.index') }}" style="font-family: 'Kanit', sans-serif;" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-2 rounded-lg transition text-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
            จองสนามเพิ่ม
        </a>
    </div>

    {{-- Banner Image --}}
    <div class="w-full h-[220px] rounded-[16px] overflow-hidden mb-8 shadow-sm relative group" data-aos="zoom-in" data-aos-delay="100">
        <img src="https://images.unsplash.com/photo-1546519638-68e109498ffc?q=80&w=1400&auto=format&fit=crop"
             style="filter: grayscale(80%) sepia(10%);"
             alt="History Banner" class="w-full h-full object-cover transition duration-500 group-hover:scale-105 group-hover:grayscale-0">

        <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/40 to-transparent"></div>
        <div class="absolute inset-y-0 left-0 flex flex-col justify-center px-10 text-white">
            <svg class="w-10 h-10 mb-3 text-[#87D068]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <h2 class="text-2xl font-bold tracking-wide">HISTORY RECORD</h2>
            <p class="text-gray-300 text-sm mt-1 max-w-md">ตรวจสอบรายการจองปัจจุบันและประวัติการเล่นที่ผ่านมาของคุณได้เลย</p>
        </div>
    </div>

    {{-- TABS --}}
    <div class="flex border-b border-gray-200 mb-6 overflow-x-auto hide-scrollbar">
        <button @click="tab = 'current'" :class="tab === 'current' ? 'active' : ''" class="tab-btn whitespace-nowrap">
            รายการปัจจุบัน <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-[10px]">{{ $current->count() }}</span>
        </button>
        <button @click="tab = 'past'" :class="tab === 'past' ? 'active' : ''" class="tab-btn whitespace-nowrap">
            ประวัติ <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-[10px]">{{ $past->count() }}</span>
        </button>
        <button @click="tab = 'transactions'" :class="tab === 'transactions' ? 'active' : ''" class="tab-btn whitespace-nowrap">
            ประวัติการใช้จ่าย <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-[10px]">{{ $transactions->count() }}</span>
        </button>
    </div>

    {{-- WRAPPER --}}
    <div class="relative min-h-[400px]">

        {{-- ─── CURRENT BOOKINGS ─── --}}
        <div x-show="tab === 'current'" x-transition.opacity.duration.400ms>
            @if($current->isEmpty())
                <div class="border-2 border-dashed border-gray-300 rounded-2xl py-20 text-center flex flex-col items-center">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                 </div>
                    <h3 class="text-base font-medium text-gray-600">ไม่มีรายการจองปัจจุบัน</h3>
                    <p class="text-sm text-gray-500 mt-1">คุณยังไม่มีตารางเล่นบาสในเร็วๆ นี้</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($current as $b)
                        @php
                            $bookingEndTime = \Carbon\Carbon::parse($b->booking_date)->setTimeFromTimeString($b->end_time);
                            $currentStatus = ($b->status === 'pending' && $bookingEndTime->isPast()) ? 'expired' : $b->status;

                            [$stClass, $bdgClass, $stLabel, $stIcon] = getStatusDetails($currentStatus);
                            $bDate = \Carbon\Carbon::parse($b->booking_date);
                        @endphp

                        <div class="b-card">
                            {{-- 1. Details --}}
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-1">
                                    <h3 class="text-[14px] font-medium text-gray-700">{{ $b->court->name }}</h3>
                                    <span class="text-xs text-gray-400">ID: #{{ str_pad($b->id, 5, '0', STR_PAD_LEFT) }}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[13px] text-gray-600">
                                    <div class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2v-5H3v5a2 2 0 002 2z"/></svg>
                                        {{ $bDate->day }} {{ $thMonths[$bDate->month] }} '{{ substr($bDate->year + 543, 2, 2) }}
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <span>{{ substr($b->start_time, 0, 5) }} - {{ substr($b->end_time, 0, 5) }} น.</span>
                                    </div>
                                </div>
                            </div>

                            {{-- 3. Status Badge --}}
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 md:gap-4 mt-2 md:mt-0 border-t md:border-none border-gray-100 pt-3 md:pt-0">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="badge {{ $bdgClass }}" data-status-badge="{{ $b->id }}">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $stIcon !!}</svg>
                                        <span data-status-label="{{ $b->id }}">{{ $stLabel }}</span>
                                    </span>

                                    @if($currentStatus === 'pending_payment')
                                        <span class="pay-countdown" data-locked-until="{{ $b->locked_until?->toIso8601String() }}" data-booking-id="{{ $b->id }}">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span class="pay-clock">--:--</span>
                                        </span>
                                    @endif
                                </div>

                                @if($currentStatus === 'pending_payment')
                                    <a href="{{ route('checkout.show', $b) }}" class="btn-pay-now w-full md:w-auto justify-center md:ml-auto" data-pay-btn="{{ $b->id }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a4 4 0 00-8 0v2M5 9h14l1 12H4L5 9z"/></svg>
                                        กลับไปชำระเงิน
                                    </a>
                                @endif

                                @if(!$b->isStarted() && $b->status === 'pending')
                                    <form method="POST" action="{{ route('booking.cancel', $b) }}" class="cancel-form md:ml-auto block">
                                        @csrf
                                        <button type="button" class="btn-cancel w-full md:w-auto text-center btn-cancel-trigger">ยกเลิก</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ─── PAST BOOKINGS ─── --}}
        <div x-show="tab === 'past'" x-transition.opacity.duration.400ms style="display: none;">
            @if($past->isEmpty())
                <div class="border-2 border-dashed border-gray-300 rounded-2xl py-20 text-center flex flex-col items-center">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <h3 class="text-base font-medium text-gray-600">ไม่มีประวัติการจอง</h3>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($past as $b)
                        @php
                            $bookingEndTime = \Carbon\Carbon::parse($b->booking_date)->setTimeFromTimeString($b->end_time);
                            $currentStatus = ($b->status === 'pending' && $bookingEndTime->isPast()) ? 'expired' : $b->status;

                            [$stClass, $bdgClass, $stLabel, $stIcon] = getStatusDetails($currentStatus);
                            $bDate = \Carbon\Carbon::parse($b->booking_date);
                        @endphp

                        <div class="b-card opacity-80 hover:opacity-100">
                            {{-- 2. Details --}}
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-1">
                                    <h3 class="text-[14px] font-medium text-gray-600">{{ $b->court->name }}</h3>
                                    <span class="text-xs text-gray-400">ID: #{{ str_pad($b->id, 5, '0', STR_PAD_LEFT) }}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[13px] text-gray-500">
                                    <div class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2v-5H3v5a2 2 0 002 2z"/></svg>
                                        {{ $bDate->day }} {{ $thMonths[$bDate->month] }} '{{ substr($bDate->year + 543, 2, 2) }}
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <span class="font-medium">{{ substr($b->start_time, 0, 5) }} - {{ substr($b->end_time, 0, 5) }} น.</span>
                                    </div>
                                    @if($b->reject_reason)
                                    <div class="flex items-center gap-1 text-red-500 text-[12px] bg-red-50 px-2 py-0.5 rounded">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        เหตุผล: {{ $b->reject_reason }}
                                    </div>
                                    @endif
                                </div>
                            </div>

                            {{-- 3. Status Badge --}}
                            <div class="flex flex-col md:items-end justify-center mt-2 md:mt-0 border-t md:border-none border-gray-100 pt-3 md:pt-0">
                                <span class="badge {{ $bdgClass }}">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $stIcon !!}</svg>
                                    {{ $stLabel }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ─── CREDIT TRANSACTION HISTORY ─── --}}
        <div x-show="tab === 'transactions'" x-transition.opacity.duration.400ms style="display: none;">
            @php
                $typeMeta = [
                    'topup' => ['label' => 'เติมเครดิต', 'bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'sign' => '+'],
                    'deduct' => ['label' => 'หักเครดิต', 'bg' => 'bg-red-100', 'text' => 'text-red-600', 'sign' => '-'],
                    'refund' => ['label' => 'คืนเครดิต', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'sign' => '+'],
                    'expire' => ['label' => 'หมดอายุอัตโนมัติ', 'bg' => 'bg-gray-200', 'text' => 'text-gray-600', 'sign' => '-'],
                ];
            @endphp
            @if($transactions->isEmpty())
                <div class="border-2 border-dashed border-gray-300 rounded-2xl py-20 text-center flex flex-col items-center">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m-6 4h6m-6 4h4M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z" /></svg>
                    </div>
                    <h3 class="text-base font-medium text-gray-600">ยังไม่มีประวัติการใช้จ่าย</h3>
                </div>
            @else
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-400 text-[11px] uppercase tracking-wide border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 font-medium">วันที่</th>
                                    <th class="px-4 py-3 font-medium">ประเภท</th>
                                    <th class="px-4 py-3 font-medium">รายละเอียด</th>
                                    <th class="px-4 py-3 font-medium text-right">จำนวนเงิน</th>
                                    <th class="px-4 py-3 font-medium text-right">คงเหลือ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($transactions as $tx)
                                    @php $meta = $typeMeta[$tx->type] ?? ['label' => $tx->type, 'bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'sign' => ''] @endphp
                                    <tr>
                                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $meta['bg'] }} {{ $meta['text'] }}">
                                                {{ $meta['label'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 max-w-[240px] truncate" title="{{ $tx->note }}">{{ $tx->note ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right font-medium {{ $meta['text'] }} whitespace-nowrap">{{ $meta['sign'] }}฿{{ number_format($tx->amount / 100, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-gray-700 font-medium whitespace-nowrap">฿{{ number_format($tx->balance_after / 100, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

    </div>

</div>
</div>

{{-- SweetAlert2: layout หลักยังไม่ได้โหลดไว้ จึงต้องโหลดที่นี่ --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-cancel-trigger').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const form = btn.closest('form');

            Swal.fire({
                title: "ยืนยันยกเลิกการจอง",
                text: "คุณต้องการยกเลิกการจองใช่หรือไม่",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "ใช่, ยกเลิกเลย",
                cancelButtonText: "ไม่ใช่"
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "ยกเลิกแล้ว!",
                        text: "รายการจองของคุณถูกยกเลิกเรียบร้อย",
                        icon: "success",
                        confirmButtonColor: "#87D068",
                        confirmButtonText: "ตกลง",
                    }).then(() => {
                        form.submit();
                    });
                }
            });
        });
    });

    // นับถอยหลังเวลาที่เหลือของรายการ "รอชำระเงิน" ทุกรายการในหน้านี้พร้อมกัน
    // (ใช้กลไก data-locked-until เดียวกับหน้า checkout/show.blade.php)
    document.querySelectorAll('.pay-countdown').forEach(function (el) {
        const lockedUntilStr = el.dataset.lockedUntil;
        const bookingId = el.dataset.bookingId;
        const clockEl = el.querySelector('.pay-clock');
        if (!lockedUntilStr) { clockEl.textContent = '—'; return; }

        const lockedUntil = new Date(lockedUntilStr).getTime();

        function tick() {
            const remainMs = lockedUntil - Date.now();

            if (remainMs <= 0) {
                clearInterval(interval);
                el.classList.add('danger');
                clockEl.textContent = 'หมดเวลา';

                // อัปเดต badge สถานะ + ซ่อนปุ่มจ่ายเงินให้ตรงกับความจริงทันที โดยไม่ต้อง reload หน้า
                const badge = document.querySelector('[data-status-badge="' + bookingId + '"]');
                const label = document.querySelector('[data-status-label="' + bookingId + '"]');
                const payBtn = document.querySelector('[data-pay-btn="' + bookingId + '"]');
                if (badge) { badge.classList.remove('badge-pending_payment'); badge.classList.add('badge-expired'); }
                if (label) { label.textContent = 'หมดเวลาชำระเงิน'; }
                if (payBtn) { payBtn.remove(); }
                return;
            }

            const totalSec = Math.floor(remainMs / 1000);
            const m = Math.floor(totalSec / 60);
            const s = totalSec % 60;
            clockEl.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');

            if (totalSec <= 120) {
                el.classList.add('danger');
            }
        }

        tick();
        const interval = setInterval(tick, 1000);
    });
});
</script>
@endpush
@endsection
