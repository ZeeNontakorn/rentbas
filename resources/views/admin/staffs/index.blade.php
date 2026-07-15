@extends('layouts.app')

@section('title', 'จัดการผู้ช่วยสนามและโค้ช')

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-6 max-w-7xl">

        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">จัดการโค้ช / ผู้ช่วยสนาม</h1>
                <p class="text-sm text-gray-500 mt-1">ค้นหา ดูข้อมูลโปรไฟล์ ตารางเวลาว่างของโค้ช และผู้ช่วยสนาม</p>
            </div>

            <form method="GET" action="{{ route('admin.staffs.index') }}" class="flex w-full md:w-auto">
                @if(request('role'))
                    <input type="hidden" name="role" value="{{ request('role') }}">
                @endif
                
                <input type="text" name="search" value="{{ $search }}" placeholder="ค้นหาชื่อ หรืออีเมล..."
                       class="w-full md:w-72 border border-gray-300 rounded-l-lg px-4 py-2.5 text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-5 py-2.5 rounded-r-lg text-sm font-medium transition flex items-center gap-2 flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/>
                    </svg>
                    ค้นหา
                </button>
            </form>
        </div>

        <div class="flex border-b border-gray-200 mb-6 gap-2">
            @foreach([
                ['role' => null, 'label' => 'บุคลากรทั้งหมด', 'active' => !request('role'), 'color' => 'border-orange-500 text-orange-600 font-semibold'],
                ['role' => 'coach', 'label' => 'เฉพาะโค้ช (Coaches)', 'active' => request('role') == 'coach', 'color' => 'border-blue-500 text-blue-600 font-semibold'],
                ['role' => 'staff', 'label' => 'เฉพาะผู้ช่วยสนาม (Staffs)', 'active' => request('role') == 'staff', 'color' => 'border-purple-500 text-purple-600 font-semibold']
            ] as $tab)
                <a href="{{ route('admin.staffs.index', array_filter(['role' => $tab['role'], 'search' => $search])) }}" 
                   class="px-5 py-2.5 text-sm font-medium border-b-2 transition-all {{ $tab['active'] ? $tab['color'] : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-medium text-gray-700 text-sm flex items-center gap-2">
                    @if(request('role') == 'coach')
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                        </svg>
                        รายชื่อผู้ฝึกสอน (Coaches)
                    @elseif(request('role') == 'staff')
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                        </svg>
                        รายชื่อผู้ช่วยสนาม (Staffs)
                    @else
                        <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        รายชื่อบุคลากรทั้งหมด
                    @endif
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
                            <th class="px-6 py-3 font-medium">ชื่อบุคลากร</th>
                            <th class="px-6 py-3 font-medium">ตำแหน่ง/หน้าที่</th>
                            <th class="px-6 py-3 font-medium text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($staffs as $s)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 text-gray-400 text-xs font-mono">#{{ $s->id }}</td>
                                <td class="px-6 py-4 font-medium text-gray-700">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 font-bold text-xs {{ $s->role == 'coach' ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600' }}">
                                            {{ mb_strtoupper(mb_substr($s->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p>{{ $s->name }}</p>
                                            <p class="text-xs text-gray-500 font-normal">{{ $s->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs rounded-full font-medium {{ $s->role == 'coach' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                        {{ $s->role == 'coach' ? 'ผู้ฝึกสอน (Coach)' : 'ผู้ช่วยสนาม (Staff)' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('admin.staffs.show', $s->id) }}" class="inline-flex items-center gap-1.5 bg-gray-800 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-xs font-medium transition shadow-sm">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        ดูโปรไฟล์และตารางงาน
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-16 text-center">
                                    <p class="text-gray-400 font-medium text-sm">ไม่พบข้อมูลบุคลากรในหมวดหมู่นี้</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($staffs->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-slate-50">
                    {{ $staffs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection