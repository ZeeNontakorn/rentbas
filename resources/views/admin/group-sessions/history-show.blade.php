@extends('layouts.app')
@section('title', 'รายชื่อ - ' . $round->title)

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $round->title }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ $round->play_date->format('d/m/Y') }} ·
                {{ \Carbon\Carbon::parse($round->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($round->end_time)->format('H:i') }} ·
                {{ $round->court->name ?? '-' }}
            </p>
        </div>
        <a href="{{ route('admin.group-sessions.history') }}"
            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
            ← กลับหน้าประวัติ
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700">รายชื่อผู้เล่น</h2>
            <span class="text-sm text-gray-500">{{ $round->confirmedSignups->count() }}/{{ $round->max_players }} คน</span>
        </div>
        <table class="w-full text-sm">
    <thead>
        <tr class="text-left text-gray-500 border-b border-gray-100">
            <th class="px-5 py-2 font-medium">ลำดับ</th>
            <th class="px-5 py-2 font-medium">ชื่อ</th>
            <th class="px-5 py-2 font-medium">ประเภท</th>
            <th class="px-5 py-2 font-medium">เพิ่มโดย</th>
            <th class="px-5 py-2 font-medium">เครดิตที่ใช้</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-50">
        @forelse($round->confirmedSignups as $signup)
        <tr>
            <td class="px-5 py-3 text-gray-600">{{ $signup->order_number }}</td>
            <td class="px-5 py-3 text-gray-900">{{ $signup->displayName() }}</td>
            <td class="px-5 py-3">
                <span @class([
                    'px-2 py-0.5 rounded-full text-xs',
                    'bg-green-100 text-green-700' => ! $signup->is_reserve,
                    'bg-amber-100 text-amber-700' => $signup->is_reserve,
                ])>
                    {{ $signup->is_reserve ? 'สำรอง' : 'ตัวจริง' }}
                </span>
            </td>
            <td class="px-5 py-3 text-gray-600">
                @php
                    $addedByAccount = $signup->addedBy ?? $signup->bookedBy;
                @endphp
                {{ $addedByAccount ? $addedByAccount->name.' ('.$addedByAccount->email.')' : 'ลงชื่อเอง' }}
            </td>
            <td class="px-5 py-3 text-gray-600">฿{{ number_format($signup->credit_used ?? 0, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="px-5 py-6 text-center text-gray-400">ไม่มีคนลงชื่อในรอบนี้</td></tr>
        @endforelse
    </tbody>
</table>
    </div>

</div>
@endsection