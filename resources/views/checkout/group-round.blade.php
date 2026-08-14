@extends('layouts.app')

@section('title', 'ยืนยันการชำระเงิน')

@section('content')
@php
$price = (int) $round->credit_cost;        // หน่วยบาท
$priceSatang = $price * 100;               // แปลงเป็นสตางค์เพื่อเทียบกับ credit_balance
$balance = (int) $user->credit_balance;     // หน่วยสตางค์
$sufficient = $balance >= $priceSatang;
    $thaiMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
@endphp

<div class="min-h-screen bg-white px-4 py-10 text-gray-900">
    <style>
        .group-checkout { max-width: 880px; margin: 0 auto; font-family: 'Sarabun', 'Kanit', sans-serif; }
        .group-checkout h1, .group-checkout h2, .group-checkout h3, .group-checkout button { font-family: 'Kanit', sans-serif; }
        .gc-card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 22px; }
        .gc-row { display: flex; justify-content: space-between; gap: 16px; padding: 9px 0; border-bottom: 1px solid #f1f3f5; font-size: 14px; }
        .gc-row:last-child { border-bottom: 0; }
        .gc-label { color: #6b7280; }
        .gc-value { color: #111827; font-weight: 700; text-align: right; }
        .gc-pay { border: 2px solid #87D068; border-radius: 12px; padding: 20px; }
        .gc-button { width: 100%; border: 0; border-radius: 10px; background: #87D068; color: #fff; cursor: pointer; padding: 12px 20px; font-size: 14px; font-weight: 700; }
        .gc-button:hover { background: #76bc5a; }
        .gc-button:disabled { background: #e5e7eb; color: #9ca3af; cursor: not-allowed; }
    </style>

    <div class="group-checkout" data-aos="fade-up">
        <div class="mb-6">
            <h1 class="text-[28px] font-bold">ยืนยันการชำระเงิน</h1>
            <p class="mt-1 text-sm text-gray-500">ตรวจสอบรายละเอียดรอบและยอดเครดิตก่อนยืนยันการลงชื่อจอง</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-600">
                @foreach ($errors->all() as $error)
                    <div>• {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="grid gap-6 md:grid-cols-5">
            <div class="flex flex-col gap-6 md:col-span-3">
                <div class="gc-card">
                    <h2 class="mb-4 text-[16px] font-bold">รายละเอียดรอบกลุ่มเล่นบาส</h2>
                    <div class="gc-row"><span class="gc-label">รอบ</span><span class="gc-value">{{ $round->title }}</span></div>
                    <div class="gc-row"><span class="gc-label">วันที่</span><span class="gc-value">{{ $round->play_date->day }} {{ $thaiMonths[$round->play_date->month] }} {{ $round->play_date->year + 543 }}</span></div>
                    <div class="gc-row"><span class="gc-label">เวลา</span><span class="gc-value">{{ \Carbon\Carbon::parse($round->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($round->end_time)->format('H:i') }} น.</span></div>
                    <div class="gc-row"><span class="gc-label">สนาม</span><span class="gc-value">{{ $round->court?->name ?? '-' }}</span></div>
                </div>

                <div class="gc-card">
                    <h2 class="mb-4 text-[16px] font-bold">รายละเอียดราคา</h2>
                    <div class="gc-row border-t-2 border-gray-900 pt-3">
                        <span class="gc-label font-bold text-gray-900">ยอดชำระทั้งหมด</span>
                        <span class="gc-value text-xl text-[#87D068]">฿{{ number_format($price ) }}</span>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2">
                <div class="gc-pay">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-[15px] font-bold">ชำระด้วยเครดิต</h3>
                        <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700">พร้อมใช้งาน</span>
                    </div>
                    <p class="my-3 text-xs text-gray-500">หักจากยอดเครดิตคงเหลือของคุณทันที และยืนยันการลงชื่อจองอัตโนมัติ</p>
                    <div class="gc-row"><span class="gc-label">ยอดเครดิตปัจจุบัน</span><span class="gc-value">฿{{ number_format($balance / 100, 2) }}</span></div>
                    <div class="gc-row"><span class="gc-label">ยอดชำระ</span><span class="gc-value">฿-{{ number_format($price ) }}</span></div>
                    <div class="gc-row"><span class="gc-label">ยอดเครดิตคงเหลือ</span><span class="gc-value">฿{{ number_format(max(0, $balance - $priceSatang) / 100, 2) }}</span></div>

                    @if (! $sufficient)
                        <p class="my-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600">เครดิตไม่เพียงพอ ขาดอีก ฿{{ number_format(($priceSatang - $balance) / 100, 2) }}</p>
                    @endif

                    <form class="mt-4" method="POST" action="{{ route('group-rounds.signup', $round) }}">
                        @csrf
                        <button type="submit" class="gc-button" {{ $sufficient ? '' : 'disabled' }}>ยืนยันชำระด้วยเครดิต ฿{{ number_format($price ) }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
