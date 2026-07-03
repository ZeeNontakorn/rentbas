@extends('layouts.app')

@section('title', 'จัดการผู้ใช้งาน')

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-6 max-w-7xl">

        {{-- Header --}}
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">จัดการผู้ใช้งาน</h1>
                <p class="text-sm text-gray-500 mt-1">ค้นหา ดูข้อมูล และจัดการผู้ใช้ทั้งหมดในระบบ</p>
            </div>

            {{-- ฟอร์มค้นหา --}}
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex w-full md:w-auto">
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="ระบุชื่อผู้ใช้ที่ต้องการค้นหา..."
                       class="w-full md:w-72 border border-gray-300 rounded-l-lg px-4 py-2.5 text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition">
                <button type="submit"
                        class="bg-orange-500 hover:bg-orange-600 text-white px-5 py-2.5 rounded-r-lg text-sm font-medium transition flex items-center gap-2 flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/>
                    </svg>
                    ค้นหาผู้ใช้
                </button>
            </form>
        </div>

        {{-- ตารางผู้ใช้งาน --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

            {{-- Table Header --}}
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-medium text-gray-700 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    รายชื่อผู้ใช้งานทั้งหมด
                </h2>
                @if($search)
                    <span class="text-xs bg-orange-50 text-orange-600 border border-orange-200 px-3 py-1 rounded-full font-medium">
                        ค้นหา: "{{ $search }}"
                    </span>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-gray-400 text-xs uppercase tracking-wide border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 font-medium">รหัส</th>
                            <th class="px-6 py-3 font-medium">ชื่อผู้ใช้</th>
                            <th class="px-6 py-3 font-medium">อีเมล</th>
                            <th class="px-6 py-3 font-medium">สถานะยืนยัน (OTP)</th>
                            <th class="px-6 py-3 font-medium text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($users as $u)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 text-gray-400 text-xs font-mono">#{{ $u->id }}</td>
                                <td class="px-6 py-4 font-medium text-gray-700">{{ $u->name }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $u->email }}</td>
                                <td class="px-6 py-4">
                                    @if($u->is_verified)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs rounded-full bg-green-100 text-green-700 font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                            ยืนยันแล้ว
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs rounded-full bg-red-100 text-red-700 font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                            ยังไม่ยืนยัน
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('admin.users.show', $u) }}"
                                       class="inline-flex items-center gap-1.5 bg-gray-800 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-xs font-medium transition shadow-sm">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        ดูข้อมูลและประวัติ
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div class="text-gray-400">
                                        <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <p class="font-medium text-sm">ไม่พบข้อมูลผู้ใช้งานที่ค้นหา</p>
                                        @if($search)
                                            <p class="text-xs mt-1">ลองเปลี่ยนคำค้นหาใหม่</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-slate-50">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
