@extends('layouts.app')

@section('title', 'แจ้งเตือนของฉัน')

@section('content')
<div class="container mx-auto max-w-4xl py-10 px-4">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">แจ้งเตือนของฉัน</h2>

        @if($notifications->where('is_read', false)->count() > 0)
            <form method="POST" action="{{ route('notifications.readAll') }}">
                @csrf
                <button class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-4 py-2 rounded-lg transition">
                    ทำเครื่องหมายว่าอ่านแล้วทั้งหมด
                </button>
            </form>
        @endif
    </div>

    @if($notifications->isEmpty())
        <div class="bg-white border border-dashed border-gray-300 rounded-xl p-6 text-center text-gray-500">
            ไม่มีแจ้งเตือน
        </div>
    @else
        <div class="space-y-3">
@foreach($notifications as $n)
    @php
        $isCourtBookingNotification = str_starts_with($n->title ?? '', 'คำขอจองใหม่')
            || ($n->title ?? '') === 'มีการจองสนามบาสใหม่';
        $visual = match ($n->visualType()) {
            'success' => ['border' => 'border-l-emerald-500', 'bg' => 'bg-emerald-50/70', 'title' => 'text-emerald-800', 'iconBg' => 'bg-emerald-100', 'icon' => 'text-emerald-600', 'path' => 'M5 13l4 4L19 7'],
            'danger' => ['border' => 'border-l-rose-500', 'bg' => 'bg-rose-50/70', 'title' => 'text-rose-800', 'iconBg' => 'bg-rose-100', 'icon' => 'text-rose-600', 'path' => 'M6 18L18 6M6 6l12 12'],
            'warning' => ['border' => 'border-l-amber-500', 'bg' => 'bg-amber-50/70', 'title' => 'text-amber-800', 'iconBg' => 'bg-amber-100', 'icon' => 'text-amber-600', 'path' => 'M12 8v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z'],
            default => ['border' => 'border-l-sky-500', 'bg' => 'bg-sky-50/70', 'title' => 'text-sky-800', 'iconBg' => 'bg-sky-100', 'icon' => 'text-sky-600', 'path' => 'M12 8h.01M11 12h1v4h1m8-4a9 9 0 11-18 0 9 9 0 0118 0z'],
        };
    @endphp
    <div class="{{ $visual['bg'] }} p-4 rounded-xl border border-gray-200 border-l-4 {{ $visual['border'] }} shadow-sm flex items-start gap-3
                {{ $n->is_read ? 'opacity-65' : '' }}">
        <span class="mt-0.5 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full {{ $visual['iconBg'] }} {{ $visual['icon'] }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $visual['path'] }}"></path></svg>
        </span>
        <div class="min-w-0 flex-1">
            @if($isCourtBookingNotification)
                <span class="font-semibold {{ $visual['title'] }}">{{ $n->title }}</span>
            @else
                <a href="{{ route('notifications.open', $n) }}" class="font-semibold {{ $visual['title'] }} hover:underline">{{ $n->title }}</a>
            @endif

            {{-- ส่วนที่แก้ไข --}}
            <div class="text-sm text-gray-600">
                @php
                    // split message by '|' and show the first part; fallback to full message
                    $msgParts = explode('|', $n->message);
                @endphp

                {{-- first segment (main text) --}}
                {{ $msgParts[0] ?? $n->message }}

                {{-- if there's a second segment, show it on the next line --}}
                @if(isset($msgParts[1]) && trim($msgParts[1]) !== '')
                    <div class="mt-1 font-medium {{ $visual['title'] }}">{!! nl2br(e(trim($msgParts[1]))) !!}</div>
                @endif
            </div>

            <div class="text-xs text-gray-400 mt-1">{{ $n->created_at->format('d M Y H:i') }}</div>
            @unless($isCourtBookingNotification)
                <a href="{{ route('notifications.open', $n) }}" class="mt-2 inline-flex text-xs font-semibold {{ $visual['title'] }} hover:underline">เปิดดูรายละเอียด →</a>
            @endunless
        </div>

        @if(!$n->is_read)
            <form class="flex-shrink-0" method="POST" action="{{ route('notifications.read', $n) }}">
                @csrf
                <button class="rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 shadow-sm hover:bg-gray-50">อ่านแล้ว</button>
            </form>
        @endif
    </div>
@endforeach
        </div>
    @endif
</div>
@endsection
