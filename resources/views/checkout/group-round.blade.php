@extends('layouts.app')

@section('title', 'ยืนยันการชำระเงิน')

@section('content')
@php
    $pricePerSeat = (int) $round->credit_cost;      // หน่วยบาท
    $balance = (int) $user->credit_balance;          // หน่วยสตางค์
    $thaiMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
@endphp

<div class="min-h-screen bg-white px-4 py-10 text-gray-900"
    x-data="{
        names: [''],
        max: {{ (int) $remaining }},
        pricePerSeat: {{ $pricePerSeat }},
        balanceBaht: {{ $balance / 100 }},
        add() { if (this.names.length < this.max) this.names.push(''); },
        remove(i) { if (this.names.length > 1) this.names.splice(i, 1); },
        get count() { return this.names.filter(n => n.trim() !== '').length || 1; },
        get total() { return this.count * this.pricePerSeat; },
        get remainingAfter() { return Math.max(0, this.balanceBaht - this.total); },
        get sufficient() { return this.balanceBaht >= this.total; }
    }">
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
        .gc-name-input { flex: 1; border: 1px solid #d1d5db; border-radius: 8px; padding: 9px 12px; font-size: 14px; }
        .gc-remove-btn { flex-shrink: 0; padding: 9px 12px; border-radius: 8px; border: 1px solid #fca5a5; color: #dc2626; font-size: 12px; font-weight: 600; }
        .gc-add-btn { width: 100%; border: 1px dashed #d1d5db; border-radius: 8px; padding: 10px; font-size: 13px; font-weight: 600; color: #6b7280; }
        .gc-add-btn:disabled { opacity: .4; cursor: not-allowed; }
    </style>

    <div class="group-checkout" data-aos="fade-up">
        <div class="mb-6">
            <h1 class="text-[28px] font-bold">ยืนยันการชำระเงิน</h1>
            <p class="mt-1 text-sm text-gray-500">กรอกชื่อผู้เล่น จองแทนเพื่อนได้ในครั้งเดียวกัน (สูงสุด {{ \App\Models\GroupRound::MAX_SEATS_PER_USER }} คนต่อรอบต่อผู้ใช้)</p>
        </div>

        @if($bookedCount > 0)
            <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                คุณจองไปแล้ว {{ $bookedCount }}/{{ \App\Models\GroupRound::MAX_SEATS_PER_USER }} คนในรอบนี้ จองเพิ่มได้อีก {{ $remaining }} คน
            </div>
        @endif

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
                    <div class="gc-row">
                        <span class="gc-label">ยกเลิกจองได้ถึง</span>
                        <span class="gc-value">
                            @if($round->cancel_deadline)
                                {{ $round->cancel_deadline->format('d/m/Y H:i') }} น.
                            @else
                                ได้ตลอดเวลา
                            @endif
                        </span>
                    </div>
                </div>

                @if($willBeReserve)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-700">
                    ตอนนี้ตัวจริงเต็มแล้ว ที่นั่งที่จองตอนนี้อาจเป็น <strong>คิวสำรอง</strong> — เสียเครดิตทันทีเหมือนตัวจริง แต่ถ้ามีคนสละสิทธิ์ก่อนเดดไลน์ จะเลื่อนเป็นตัวจริงอัตโนมัติ ถ้าไม่มีใครสละสิทธิ์ ระบบจะคืนเครดิตให้อัตโนมัติหลังหมดเวลา
                </div>
                @endif

                <div class="gc-card">
                    <h2 class="mb-4 text-[16px] font-bold">ชื่อผู้เล่น</h2>
                    <div class="space-y-2">
                        <template x-for="(name, i) in names" :key="i">
                            <div class="flex gap-2">
                                <input type="text" class="gc-name-input" :name="'names[' + i + ']'"
                                    x-model="names[i]" placeholder="ชื่อผู้เล่น" maxlength="255">
                                <button type="button" class="gc-remove-btn" x-show="names.length > 1" @click="remove(i)">ลบ</button>
                            </div>
                        </template>
                    </div>
                    <button type="button" class="gc-add-btn mt-3" x-show="names.length < max" @click="add()">
                        + เพิ่มคน (จองแทนเพื่อนได้อีก <span x-text="max - names.length"></span> คน)
                    </button>
                </div>
            </div>

            <div class="md:col-span-2">
                <form method="POST" action="{{ route('group-rounds.signup', $round) }}">
                    @csrf
                    <template x-for="(name, i) in names" :key="'hidden-' + i">
                        <input type="hidden" :name="'names[' + i + ']'" :value="name">
                    </template>

                    <div class="gc-pay">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-[15px] font-bold">ชำระด้วยเครดิต</h3>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700">พร้อมใช้งาน</span>
                        </div>
                        <p class="my-3 text-xs text-gray-500">หักจากยอดเครดิตคงเหลือของคุณทันที และยืนยันการลงชื่อจองอัตโนมัติ</p>

                        <div class="gc-row"><span class="gc-label">ราคา/คน</span><span class="gc-value">฿{{ number_format($pricePerSeat) }}</span></div>
                        <div class="gc-row"><span class="gc-label">จำนวนคน</span><span class="gc-value" x-text="count"></span></div>
                        <div class="gc-row"><span class="gc-label">ยอดเครดิตปัจจุบัน</span><span class="gc-value">฿{{ number_format($balance / 100, 2) }}</span></div>
                        <div class="gc-row"><span class="gc-label">ยอดชำระ</span><span class="gc-value" x-text="'฿-' + total.toLocaleString()"></span></div>
                        <div class="gc-row"><span class="gc-label">ยอดเครดิตคงเหลือ</span><span class="gc-value" x-text="'฿' + remainingAfter.toLocaleString()"></span></div>

                        <p class="my-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600" x-show="!sufficient">
                            เครดิตไม่เพียงพอสำหรับจำนวนคนที่กรอก
                        </p>

                        <button type="submit" class="gc-button mt-4" :disabled="!sufficient">
                            ยืนยันชำระด้วยเครดิต <span x-text="'฿' + total.toLocaleString()"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection