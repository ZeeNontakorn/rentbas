{{-- =========================================================
    Page : Course Management
    Module : การจัดการคอร์สเรียน (ของ Admin)
    Description :
    - แสดงรายการคอร์สทั้งหมด
    - ค้นหาชื่อคอร์ส 
    - เปิด/ปิดการใช้งาน คอร์สเรียน 
    - แก้ไข / ลบ / เพิ่มคอร์ส

    Author : Pimonphan
    Last Update : 17/07/2026
========================================================= --}}

@extends('layouts.app')

@section('title', 'จัดการคอร์ส')

@section('content')
<div class="min-h-screen bg-slate-50 py-8 text-gray-900">
    <div class="container mx-auto max-w-7xl px-6">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">จัดการคอร์ส</h1>
                <p class="mt-1 text-sm text-gray-500">ค้นหา ดูข้อมูล และจัดการคอร์สเรียนทั้งหมดในระบบ</p>
            </div>
            <!-- Search และ ปุ่มเพิ่มคอร์ส -->
            <div class="flex w-full flex-col gap-3 md:w-auto md:flex-row md:items-center">
                <form method="GET" action="{{ route('admin.courses') }}" class="flex w-full md:w-auto">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="ระบุชื่อคอร์สที่ต้องการค้นหา..."
                           class="w-full rounded-l-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400 md:w-72">
                    <button type="submit" class="flex shrink-0 items-center gap-2 rounded-r-lg bg-orange-500 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-orange-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/></svg>
                        ค้นหาคอร์ส
                    </button>
                </form>
                <a href="{{ route('admin.courses.create') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    เพิ่มคอร์ส
                </a>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <svg class="h-4 w-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 002 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    รายการคอร์สทั้งหมด
                </h2>
                <span class="hidden text-xs text-gray-400 sm:inline">เลื่อนตารางเพื่อดูข้อมูลเพิ่มเติม</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1160px] w-full table-fixed text-left text-sm">
                    <colgroup>
                        <col class="w-[110px]"><col class="w-[260px]"><col class="w-[115px]"><col class="w-[330px]"><col class="w-[170px]"><col class="w-[140px]"><col class="w-[175px]">
                    </colgroup>
                    <thead class="border-b border-gray-200 bg-slate-50 text-xs uppercase tracking-wide text-gray-400">
                        <tr>
                            <th class="px-7 py-4 font-medium">ภาพ</th>
                            <th class="px-5 py-4 font-medium">ชื่อคลาส / กลุ่มเป้าหมาย</th>
                            <th class="px-5 py-4 font-medium">ช่วงอายุ</th>
                            <th class="px-5 py-4 font-medium">วันเรียนและเวลา</th>
                            <th class="px-5 py-4 font-medium">แพ็กเกจ</th>
                            <th class="px-5 py-4 font-medium">สถานะ</th>
                            <th class="px-5 py-4 text-center font-medium">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($courses as $course)
                            @php $package = $course->packages->first(); @endphp
                            <tr class="align-top transition hover:bg-slate-50/80">
                                <td class="px-7 py-6">
                                    @if ($course->image_url)
                                        <img src="{{ $course->image_url }}" alt="{{ $course->course_name }}" class="h-14 w-14 rounded-xl border border-gray-200 object-cover shadow-sm">
                                    @else
                                        <div class="flex h-14 w-14 items-center justify-center rounded-xl border border-gray-200 bg-slate-100 text-slate-300">
                                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-6">
                                    <p class="font-semibold leading-6 text-gray-800">
                                        {{ $course->course_name }}
                                        @if ($package && $package->is_featured)<span class="ml-1" title="แพ็กเกจแนะนำ">⭐</span>@endif
                                    </p>
                                    <div class="mt-1.5 flex items-center gap-2 text-xs leading-5">
                                        <span class="text-gray-400">{{ $course->targetGroups->pluck('target_group')->implode(', ') ?: '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-6"><span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1.5 text-sm font-medium text-slate-600">{{ $course->age_range_label }}</span></td>
                                <td class="px-5 py-5">
                                    <div class="space-y-2.5">
                                        @forelse ($course->schedules as $schedule)
                                            <div class="rounded-lg border border-slate-100 bg-slate-50 px-3.5 py-3">
                                                <p class="font-medium leading-5 text-slate-700">
                                                    {{ $schedule->day_type_label }}
                                                    <span class="ml-1 text-slate-500">{{ \Illuminate\Support\Carbon::parse($schedule->start_time)->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($schedule->end_time)->format('H:i') }}</span>
                                                </p>
                                                <div class="mt-2">
                                                    @if ($schedule->is_limited_spots)
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700">
                                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-2a4 4 0 10-4-4"/></svg>
                                                            {{ $schedule->spots_label }}
                                                        </span>
                                                    @else
                                                        <span class="text-xs text-gray-400">ไม่จำกัดจำนวน</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <span class="text-gray-400">—</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-5 py-6">
                                    @if ($package)
                                        <div class="space-y-1.5">
                                            @foreach($course->packages as $coursePackage)
                                                <p class="font-medium leading-5 text-slate-700">{{ $coursePackage->total_sessions }} ครั้ง <span class="text-gray-300">/</span> {{ number_format($coursePackage->total_price, 0) }} บาท <span class="text-xs font-normal text-gray-400">({{ $coursePackage->validity_label }})</span></p>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-400">ยังไม่มีแพ็กเกจ</span>
                                    @endif
                                </td>
                                <td class="px-5 py-6">
                                    <form action="{{ route('admin.courses.toggleStatus', $course) }}" method="POST" class="inline-block">
                                        @csrf @method('PATCH')
                                        @if ($package)
                                            <button type="submit" title="คลิกเพื่อ{{ $package->is_active ? 'ปิด' : 'เปิด' }}ใช้งานคอร์สนี้" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition {{ $package->is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $package->is_active ? 'bg-green-500' : 'bg-gray-400' }}"></span>{{ $package->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                                            </button>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-400"><span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>ยังไม่มีแพ็กเกจ</span>
                                        @endif
                                    </form>
                                </td>
                                <td class="px-5 py-6 text-center">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.courses.edit', $course) }}" class="inline-flex rounded-lg bg-gray-800 px-4 py-2.5 text-xs font-medium text-white shadow-sm transition hover:bg-gray-600">แก้ไข</a>
                                        <form id="deleteForm-{{ $course->id }}" action="{{ route('admin.courses.destroy', $course) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="button" onclick="confirmDeleteCourse('{{ $course->id }}', '{{ addslashes($course->course_name) }}')" class="inline-flex rounded-lg bg-red-500 px-4 py-2.5 text-xs font-medium text-white shadow-sm transition hover:bg-red-600">ลบ</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-16 text-center text-gray-400">
                                <p class="font-medium text-sm">ยังไม่มีคอร์สในระบบ</p>
                                <a href="{{ route('admin.courses.create') }}" class="mt-3 inline-block text-sm font-medium text-blue-500 hover:text-blue-600">+ เพิ่มคอร์สแรกของคุณ</a>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($courses->hasPages())
                <div class="flex flex-col gap-3 border-t border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-gray-500">
                        แสดง {{ $courses->firstItem() }}–{{ $courses->lastItem() }} จาก {{ $courses->total() }} คอร์ส
                    </p>
                    <div class="flex items-center gap-1.5">
                        @if($courses->onFirstPage())
                            <span class="rounded-lg border border-gray-200 px-3 py-2 text-xs text-gray-300">ก่อนหน้า</span>
                        @else
                            <a href="{{ $courses->previousPageUrl() }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 transition hover:border-orange-300 hover:text-orange-600">ก่อนหน้า</a>
                        @endif

                        @foreach($courses->getUrlRange(max(1, $courses->currentPage() - 1), min($courses->lastPage(), $courses->currentPage() + 1)) as $page => $url)
                            <a href="{{ $url }}" class="rounded-lg px-3 py-2 text-xs font-medium transition {{ $page === $courses->currentPage() ? 'bg-orange-500 text-white shadow-sm' : 'border border-gray-200 text-gray-600 hover:border-orange-300 hover:text-orange-600' }}">{{ $page }}</a>
                        @endforeach

                        @if($courses->hasMorePages())
                            <a href="{{ $courses->nextPageUrl() }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 transition hover:border-orange-300 hover:text-orange-600">ถัดไป</a>
                        @else
                            <span class="rounded-lg border border-gray-200 px-3 py-2 text-xs text-gray-300">ถัดไป</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function confirmDeleteCourse(courseId, courseName) {
    Swal.fire({ title: 'ยืนยันลบคอร์สนี้ใช่ไหม?', 
    text: `เมื่อลบคอร์ส "${courseName}" แล้วจะไม่สามารถกู้คืนข้อมูลได้ (รวมถึงรอบเวลาเรียนและแพ็กเกจทั้งหมดของคอร์สนี้)`, 
    icon: 'warning', 
    showCancelButton: true, 
    confirmButtonColor: '#ef4444', 
    cancelButtonColor: '#3085d6', 
    confirmButtonText: 'ยืนยันการลบ', 
    cancelButtonText: 'ยกเลิก' }).then((result) => {
        if (result.isConfirmed) document.getElementById('deleteForm-' + courseId).submit();
    });
}
</script>
@endsection