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
        $accentBorder = $n->is_read ? '' : 'border-l-4 border-orange-500';
        $accentText = 'text-gray-800';
        if ($n->title === 'การจองได้รับการอนุมัติ') {
            $accentBorder = $n->is_read ? 'border-l-4 border-green-200' : 'border-l-4 border-green-500';
            $accentText = 'text-green-700';
        } elseif ($n->title === 'การจองถูกปฏิเสธ') {
            $accentBorder = $n->is_read ? 'border-l-4 border-red-200' : 'border-l-4 border-red-500';
            $accentText = 'text-red-700';
        }
    @endphp
    <div class="bg-white p-4 rounded-xl shadow flex justify-between items-center
                {{ $accentBorder }} {{ $n->is_read ? 'opacity-70' : '' }}">
        <div>
            <div class="font-semibold">{{ $n->title }}</div>

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
                    <div class="mt-1 font-medium {{ $accentText }}">{!! nl2br(e(trim($msgParts[1]))) !!}</div>
                @endif
            </div>

            <div class="text-xs text-gray-400 mt-1">{{ $n->created_at->format('d M Y H:i') }}</div>
        </div>

        @if(!$n->is_read)
            <form method="POST" action="{{ route('notifications.read', $n) }}">
                @csrf
                <button class="text-sm text-blue-600 hover:underline">ทำเครื่องหมายว่าอ่านแล้ว</button>
            </form>
        @endif
    </div>
@endforeach
        </div>
    @endif
</div>
@endsection