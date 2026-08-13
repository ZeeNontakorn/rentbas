@extends('layouts.app')

@section('title', 'จองกลุ่มเล่นบาส')

@section('content')
<style>
    .my-group-page { min-height: 100vh; background: #f6f7f9; padding: 40px 24px 56px; color: #111827; }
    .my-group-container { max-width: 1120px; margin: 0 auto; }
    .my-group-heading { margin-bottom: 28px; }
    .my-booking-card { overflow: hidden; border: 1px solid #e5e7eb; border-radius: 16px; background: #fff; box-shadow: 0 2px 8px rgba(15, 23, 42, .06); }
    .my-booking-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 18px 22px; background: #0d0f1e; color: #fff; }
    .my-booking-content { display: grid; grid-template-columns: 310px minmax(0, 1fr); gap: 28px; padding: 22px; }
    .my-booking-details { border-right: 1px solid #e5e7eb; padding-right: 28px; }
    .my-player-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin: 0; padding: 0; list-style: none; }
    .my-player { display: flex; min-width: 0; align-items: center; gap: 10px; border-radius: 8px; background: #f8fafc; padding: 9px 10px; }
    .my-player-name { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    @media (max-width: 720px) {
        .my-group-page { padding: 28px 16px 40px; }
        .my-booking-header { align-items: flex-start; flex-direction: column; }
        .my-booking-content { grid-template-columns: 1fr; gap: 22px; padding: 18px; }
        .my-booking-details { border-right: 0; border-bottom: 1px solid #e5e7eb; padding: 0 0 18px; }
        .my-player-list { grid-template-columns: 1fr; }
    }
</style>
<div class="my-group-page">
    <div class="my-group-container">
        <div class="my-group-heading">
            <p class="mb-1 text-sm font-semibold uppercase tracking-[0.18em] text-orange-500">Group Play</p>
            <h1 class="text-3xl font-bold">กลุ่มเล่นบาสที่คุณจอง</h1>
            <p class="mt-2 text-sm text-gray-500">ดูรายละเอียดรอบและรายชื่อผู้เล่นที่ยืนยันการจองแล้ว</p>
        </div>

        @if($signups->isEmpty())
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
                <div class="text-4xl">🏀</div>
                <h2 class="mt-3 text-lg font-bold">ยังไม่มีรายการจองกลุ่มเล่นบาส</h2>
                <p class="mt-1 text-sm text-gray-500">เลือกรอบที่ต้องการจากหน้าแรกเพื่อเริ่มลงชื่อจอง</p>
                <a href="{{ route('home') }}#group-sessions" class="mt-5 inline-block rounded-lg bg-orange-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-orange-600">ดูรอบที่เปิดอยู่</a>
            </div>
        @else
            <div class="space-y-6">
                @foreach($signups as $signup)
                    @php
                        $round = $signup->round;
                        $players = $round->confirmedSignups;
                        $thaiMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
                    @endphp
                    <article class="my-booking-card">
                        <div class="my-booking-header">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.15em] text-orange-400">ยืนยันการจองแล้ว</p>
                                <h2 class="mt-1 text-xl font-bold">{{ $round->title }}</h2>
                            </div>
                            <span class="w-fit rounded-full bg-orange-500 px-3 py-1 text-sm font-semibold">ลำดับของคุณ: {{ $signup->order_number }}</span>
                        </div>

                        <div class="my-booking-content">
                            <div class="my-booking-details">
                                <h3 class="mb-3 font-bold">รายละเอียดรอบ</h3>
                                <dl class="space-y-3 text-sm">
                                    <div class="flex justify-between gap-4 border-b border-gray-100 pb-2"><dt class="text-gray-500">วันที่</dt><dd class="text-right font-semibold">{{ $round->play_date->day }} {{ $thaiMonths[$round->play_date->month] }} {{ $round->play_date->year + 543 }}</dd></div>
                                    <div class="flex justify-between gap-4 border-b border-gray-100 pb-2"><dt class="text-gray-500">เวลา</dt><dd class="text-right font-semibold">{{ \Carbon\Carbon::parse($round->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($round->end_time)->format('H:i') }} น.</dd></div>
                                    <div class="flex justify-between gap-4 border-b border-gray-100 pb-2"><dt class="text-gray-500">สนาม</dt><dd class="text-right font-semibold">{{ $round->court?->name ?? '-' }}</dd></div>
                                    <div class="flex justify-between gap-4"><dt class="text-gray-500">ผู้เล่น</dt><dd class="text-right font-semibold">{{ $players->count() }}/{{ $round->max_players }} คน</dd></div>
                                </dl>
                            </div>

                            <div>
                                <h3 class="mb-3 font-bold">รายชื่อผู้เล่น</h3>
                                <ol class="my-player-list">
                                    @foreach($players as $player)
                                        <li class="my-player text-sm">
                                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-orange-100 text-xs font-bold text-orange-700">{{ $player->order_number }}</span>
                                            <span class="my-player-name font-medium text-gray-800" title="{{ $player->user?->name ?? $player->guest_name ?? 'ผู้จองภายนอก' }}">{{ $player->user?->name ?? $player->guest_name ?? 'ผู้จองภายนอก' }}</span>
                                            @if($player->user_id === auth()->id())
                                                <span class="ml-auto text-xs font-semibold text-orange-600">คุณ</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
