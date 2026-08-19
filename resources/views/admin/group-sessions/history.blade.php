@extends('layouts.app')
@section('title', 'ประวัติกลุ่มเล่นบาส')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">ประวัติกลุ่มเล่นบาส</h1>
            <p class="text-sm text-gray-500 mt-1">รอบที่ผ่านไปแล้ว หรือปิดรับสมัครแล้ว</p>
        </div>
        <a href="{{ route('admin.group-sessions.index') }}"
            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
            ← กลับหน้าจัดการ
        </a>
    </div>

    {{-- ฟอร์มค้นหา --}}
    <form method="GET" action="{{ route('admin.group-sessions.history') }}"
        class="bg-white rounded-xl border border-gray-200 p-4 mb-4 flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-medium text-gray-600 mb-1">ค้นหาชื่อรอบ</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="เช่น กินข้าวกันครับอ้าย"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">วันที่เล่น</label>
            <input type="date" name="date" value="{{ $date }}"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-900">
        </div>
        <div class="flex gap-2">
            <button type="submit"
                class="px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700">
                ค้นหา
            </button>
            @if($search || $date)
            <a href="{{ route('admin.group-sessions.history') }}"
                class="px-4 py-2 bg-gray-100 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-200">
                ล้างตัวกรอง
            </a>
            @endif
        </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b border-gray-100">
                    <th class="px-5 py-2 font-medium">รอบ</th>
                    <th class="px-5 py-2 font-medium">วันที่</th>
                    <th class="px-5 py-2 font-medium">เวลา</th>
                    <th class="px-5 py-2 font-medium">สนาม</th>
                    <th class="px-5 py-2 font-medium">คนลงชื่อ</th>
                    <th class="px-5 py-2 font-medium">สถานะ</th>
                    <th class="px-5 py-2 font-medium text-right">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($pastRounds as $r)
                <tr>
                    <td class="px-5 py-3 text-gray-900">{{ $r->title }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $r->play_date->format('d/m/Y') }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ \Carbon\Carbon::parse($r->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($r->end_time)->format('H:i') }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $r->court->name ?? '-' }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $r->players_count }}/{{ $r->max_players }}</td>
                    <td class="px-5 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-500">จบแล้ว</span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('admin.group-sessions.history.show', $r) }}" class="text-orange-600 hover:text-orange-800 font-medium">ดูรายชื่อ</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-5 py-6 text-center text-gray-400">ไม่พบรอบที่ตรงกับเงื่อนไข</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $pastRounds->links() }}
    </div>

</div>
@endsection