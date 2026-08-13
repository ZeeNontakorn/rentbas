@extends('layouts.app')
@section('title', $round->title)

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6">

    <a href="{{ route('admin.group-sessions.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; กลับไปหน้ารายการรอบ</a>

    <div class="flex items-center justify-between mt-2 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $round->title }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ $round->play_date->format('d/m/Y') }} &middot;
                {{ \Carbon\Carbon::parse($round->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($round->end_time)->format('H:i') }}
                @if($round->court) &middot; {{ $round->court->name }} @endif
                &middot; เครดิต {{ $round->credit_cost }}/คน
            </p>
        </div>
        <div class="flex gap-2">
            @if($round->status === 'open')
                <form action="{{ route('admin.group-sessions.rounds.close', $round) }}" method="POST">
    @csrf
    @method('PATCH')
    <button ...>ปิดรับสมัคร</button>
</form>
            @else
                <form action="{{ route('admin.group-sessions.rounds.reopen', $round) }}" method="POST">
    @csrf
    @method('PATCH')
    <button class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">เปิดรับสมัครอีกครั้ง</button>
</form>
            @endif
            <form action="{{ route('admin.group-sessions.rounds.cancel', $round) }}" method="POST"
                onsubmit="return confirm('ยกเลิกรอบนี้และคืนเครดิตให้ทุกคนที่ลงชื่อไว้?');">
                @csrf
                @method('DELETE')
                <button class="px-3 py-1.5 text-sm border border-red-200 text-red-600 rounded-lg hover:bg-red-50">ยกเลิกรอบ</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="flex items-center justify-between mb-3">
        <p class="text-sm text-gray-600">
            ลงชื่อแล้ว <span class="font-semibold text-gray-900">{{ $round->confirmedSignups->count() }}</span> / {{ $round->max_players }} คน
        </p>
    </div>

    {{-- เพิ่มคนเข้ารอบ (สำหรับกรณีลูกค้ายังแจ้งผ่านไลน์/โอนเงินอยู่) --}}
    @if($round->status === 'open')
    <form action="{{ route('admin.group-sessions.rounds.addPlayer', $round) }}" method="POST" class="flex gap-2 mb-6">
        @csrf
        <select name="user_id" required class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">-- เลือกสมาชิกเพื่อเพิ่มเข้ารอบ --</option>
            @isset($members)
                @foreach($members as $member)
                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                @endforeach
            @endisset
        </select>
        <button class="px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 whitespace-nowrap">
            + เพิ่มเข้ารอบ (ตัดเครดิต)
        </button>
    </form>
    @endif

    {{-- รายชื่อคนลงเล่น เรียงตามลำดับจริง --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b border-gray-100 bg-gray-50">
                    <th class="px-5 py-2 font-medium w-16">ลำดับ</th>
                    <th class="px-5 py-2 font-medium">ชื่อ</th>
                    <th class="px-5 py-2 font-medium">เวลาลงชื่อ</th>
                    <th class="px-5 py-2 font-medium">เครดิตที่ใช้</th>
                    <th class="px-5 py-2 font-medium">เพิ่มโดย</th>
                    <th class="px-5 py-2 font-medium text-right">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($round->confirmedSignups as $signup)
                <tr @class(['bg-amber-50/50' => $signup->order_number > $round->max_players])>
                    <td class="px-5 py-3 font-semibold text-gray-900">{{ $signup->order_number }}</td>
                    <td class="px-5 py-3 text-gray-900">{{ $signup->user->name }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $signup->signed_up_at->format('d/m H:i:s') }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $signup->credit_used }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $signup->addedBy->name ?? 'ลงชื่อเอง' }}</td>
                    <td class="px-5 py-3 text-right">
                        <form action="{{ route('admin.group-sessions.rounds.removePlayer', [$round, $signup]) }}" method="POST"
                            onsubmit="return confirm('นำ {{ addslashes($signup->user->name) }} ออกจากรอบ และคืนเครดิต {{ $signup->credit_used }} หน่วย?');">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-500 hover:text-red-700">นำออก</button>
                        </form>
                    </td>
                </tr>
                @if($signup->order_number === $round->max_players)
                    <tr><td colspan="6" class="px-5 py-1 text-center text-xs text-amber-600 bg-amber-50">— เต็มจำนวนตัวจริง ({{ $round->max_players }} คน) รายชื่อถัดไปคือตัวสำรอง —</td></tr>
                @endif
                @empty
                <tr><td colspan="6" class="px-5 py-6 text-center text-gray-400">ยังไม่มีคนลงชื่อ</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection