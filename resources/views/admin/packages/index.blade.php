@extends('layouts.app')

@section('title', 'จัดการ Package')

@section('content')
<div class="min-h-screen bg-slate-50 py-8 text-gray-900">
    <div class="container mx-auto px-4 sm:px-6 max-w-7xl">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-[32px] font-bold text-gray-900 tracking-tight">จัดการแพ็กเกจ</h1>
                <p class="font-sarabun mt-1 text-sm text-gray-500">ค้นหา ดูข้อมูล และจัดการแพ็กเกจทั้งหมดในระบบ</p>
            </div>
            <!-- Search และ ปุ่มเพิ่มแพ็กเกจ -->
            <div class="flex w-full flex-col gap-3 md:w-auto md:flex-row md:items-center">
                <form method="GET" action="{{ route('admin.packages.index') }}" class="flex w-full md:w-auto">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="ระบุชื่อแพ็กเกจที่ต้องการค้นหา..."
                           class="w-full rounded-l-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400 md:w-72">
                    <button type="submit" class="flex shrink-0 items-center gap-2 rounded-r-lg bg-orange-500 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-orange-600 shadow-sm cursor-pointer">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/></svg>
                        ค้นหาแพ็กเกจ
                    </button>
                </form>
                <a href="{{ route('admin.packages.create') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                    เพิ่มแพ็กเกจ
                </a>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 bg-slate-50">
                <h2 class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <svg class="h-4 w-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    รายการแพ็กเกจทั้งหมด
                </h2>
            </div>

            <div class="overflow-x-hidden">
                <table class="w-full table-auto text-left text-sm">
                    <thead class="border-b border-gray-200 bg-slate-50 text-xs uppercase tracking-wide text-gray-400">
                        <tr>
                            <th class="px-3 py-4 font-medium sm:px-6">รูปภาพ</th>
                            <th class="px-3 py-4 font-medium sm:px-6">ชื่อแพ็กเกจ</th>
                            <th class="hidden px-5 py-4 font-medium xl:table-cell">รายละเอียด</th>
                            <th class="hidden px-5 py-4 font-medium lg:table-cell">วันที่ใช้ได้</th>
                            <th class="px-3 py-4 font-medium sm:px-5">ราคา</th>
                            <th class="hidden px-5 py-4 font-medium md:table-cell">จำนวนครั้ง</th>
                            <th class="hidden px-5 py-4 font-medium lg:table-cell">จำนวนวัน</th>
                            <th class="px-3 py-4 font-medium sm:px-5">สถานะ</th>
                            <th class="px-3 py-4 text-center font-medium sm:px-5">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($packages as $package)
                            <tr class="align-middle transition">
                                <td class="px-3 py-4 sm:px-7 sm:py-6 w-[120px] sm:w-[160px] md:w-[200px]">
                                    @if ($package->image)
                                        <img src="{{ asset('storage/' . $package->image) }}" alt="{{ $package->name }}"
                                             class="w-24 h-16 sm:w-32 sm:h-20 md:w-40 md:h-24 rounded-xl border border-gray-200 object-cover shadow-sm transition-all duration-300 hover:scale-105 hover:shadow-md cursor-pointer">
                                    @else
                                        <div class="flex w-24 h-16 sm:w-32 sm:h-20 md:w-40 md:h-24 items-center justify-center rounded-xl border border-gray-200 bg-slate-100 text-slate-300 transition-all duration-300 hover:scale-105 cursor-pointer">
                                            <svg class="h-6 w-6 sm:h-8 sm:w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-4 sm:px-6 sm:py-6">
                                    <p class="font-semibold leading-6 text-gray-800 break-words">{{ $package->name }}</p>
                                    <!-- ข้อมูลย่อสำหรับจอเล็ก ที่ซ่อนคอลัมน์ไป -->
                                    <p class="mt-1 text-xs text-gray-400 md:hidden">{{ $package->num_of_use }} ครั้ง @if(!is_null($package->day)) · {{ $package->day }} วัน @endif</p>
                                </td>
                                <td class="hidden max-w-[120px] px-5 py-6 xl:table-cell">
                                    <p class="line-clamp-1 text-sm leading-6 text-gray-500 break-words">{{ $package->description ?: '—' }}</p>
                                </td>
                                <td class="hidden px-5 py-6 lg:table-cell">
                                    @php
                                        $dayLabels = ['mon'=>'จ','tue'=>'อ','wed'=>'พ','thu'=>'พฤ','fri'=>'ศ','sat'=>'ส','sun'=>'อา'];
                                        $usableDays = $package->usable_days ?? [];
                                    @endphp
                                    @if(empty($usableDays))
                                        <span class="inline-flex rounded-lg bg-green-50 px-2.5 py-1.5 text-xs font-medium text-green-700">ทุกวัน</span>
                                    @else
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($usableDays as $day)
                                                <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-md bg-slate-100 px-1.5 text-xs font-medium text-slate-600">
                                                    {{ $dayLabels[$day] ?? $day }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-4 sm:px-5 sm:py-6">
                                    <span class="font-medium text-slate-700">{{ number_format($package->price, 2) }}</span>
                                    <span class="text-xs text-gray-400">บาท</span>
                                </td>
                                <td class="hidden px-5 py-6 md:table-cell">
                                    <span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1.5 text-sm font-medium text-slate-600">{{ $package->num_of_use }} ครั้ง</span>
                                </td>
                                <td class="hidden px-5 py-6 lg:table-cell">
                                    @if (! is_null($package->day))
                                        <span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1.5 text-sm font-medium text-slate-600">{{ $package->day }} วัน</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 sm:px-5 sm:py-6">
                                    <form action="{{ route('admin.packages.toggleStatus', $package) }}" method="POST" class="inline-flex items-center gap-2">
                                        @csrf @method('PATCH')
                                        <label class="switch" title="คลิกเพื่อ{{ $package->is_active ? 'ปิด' : 'เปิด' }}ใช้งานแพ็กเกจนี้">
                                            <input type="checkbox" {{ $package->is_active ? 'checked' : '' }} onchange="this.form.submit()">
                                            <div class="slider">
                                                <div class="circle">
                                                    <svg class="cross" viewBox="0 0 365.696 365.696" y="0" x="0" height="6" width="6" xmlns="http://www.w3.org/2000/svg">
                                                        <g><path fill="currentColor" d="M243.188 182.86 356.32 69.726c12.5-12.5 12.5-32.766 0-45.247L341.238 9.398c-12.504-12.503-32.77-12.503-45.25 0L182.86 122.528 69.727 9.374c-12.5-12.5-32.766-12.5-45.247 0L9.375 24.457c-12.5 12.504-12.5 32.77 0 45.25l113.152 113.152L9.398 295.99c-12.503 12.503-12.503 32.769 0 45.25L24.48 356.32c12.5 12.5 32.766 12.5 45.247 0l113.132-113.132L295.99 356.32c12.503 12.5 32.769 12.5 45.25 0l15.081-15.082c12.5-12.504 12.5-32.77 0-45.25zm0 0"></path></g>
                                                    </svg>
                                                    <svg class="checkmark" viewBox="0 0 24 24" y="0" x="0" height="10" width="10" xmlns="http://www.w3.org/2000/svg">
                                                        <g><path fill="currentColor" d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path></g>
                                                    </svg>
                                                </div>
                                            </div>
                                        </label>
                                        <span class="hidden text-xs font-medium sm:inline {{ $package->is_active ? 'text-green-700' : 'text-gray-400' }}">
                                            {{ $package->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                                        </span>
                                    </form>
                                </td>
                                <td class="px-3 py-4 text-center sm:px-5 sm:py-6">
                                    <div class="inline-flex flex-wrap items-center justify-center gap-1.5 sm:gap-2">
                                        <a href="{{ route('admin.packages.edit', $package) }}" class="inline-flex rounded-lg bg-blue-500 px-2.5 py-2 text-xs font-medium text-white shadow-sm transition hover:bg-blue-600 sm:w-17 justify-center sm:px-4 sm:py-2.5">แก้ไข</a>
                                        <form id="deleteForm-{{ $package->id }}" action="{{ route('admin.packages.delete', $package) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="button" onclick="confirmDeletePackage('{{ $package->id }}', '{{ addslashes($package->name) }}')" class="inline-flex items-center gap-1.5 rounded-lg bg-red-500 px-2.5 py-2 text-xs font-medium text-white shadow-sm transition hover:bg-red-600 sm:px-4 sm:py-2.5 cursor-pointer">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>ลบ</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-6 py-16 text-center text-gray-400">
                                <p class="font-medium text-sm">ยังไม่มีแพ็กเกจในระบบ</p>
                                <a href="{{ route('admin.packages.create') }}" class="mt-3 inline-block text-sm font-medium text-blue-500 hover:text-blue-600">+ เพิ่มแพ็กเกจแรกของคุณ</a>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($packages->hasPages())
                <div class="flex flex-col gap-3 border-t border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-gray-500">
                        แสดง {{ $packages->firstItem() }}–{{ $packages->lastItem() }} จาก {{ $packages->total() }} แพ็กเกจ
                    </p>
                    <div class="flex items-center gap-1.5">
                        @if($packages->onFirstPage())
                            <span class="rounded-lg border border-gray-200 px-3 py-2 text-xs text-gray-300">ก่อนหน้า</span>
                        @else
                            <a href="{{ $packages->previousPageUrl() }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 transition hover:border-orange-300 hover:text-orange-600">ก่อนหน้า</a>
                        @endif

                        @foreach($packages->getUrlRange(max(1, $packages->currentPage() - 1), min($packages->lastPage(), $packages->currentPage() + 1)) as $page => $url)
                            <a href="{{ $url }}" class="rounded-lg px-3 py-2 text-xs font-medium transition {{ $page === $packages->currentPage() ? 'bg-orange-500 text-white shadow-sm' : 'border border-gray-200 text-gray-600 hover:border-orange-300 hover:text-orange-600' }}">{{ $page }}</a>
                        @endforeach

                        @if($packages->hasMorePages())
                            <a href="{{ $packages->nextPageUrl() }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 transition hover:border-orange-300 hover:text-orange-600">ถัดไป</a>
                        @else
                            <span class="rounded-lg border border-gray-200 px-3 py-2 text-xs text-gray-300">ถัดไป</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
/* From Uiverse.io by Galahhad — สไตล์เดียวกับหน้าคอร์ส */
.switch {
    --switch-width: 38px;
    --switch-height: 20px;
    --switch-bg: rgb(180, 180, 180);
    --switch-checked-bg: rgb(0, 200, 83);
    --switch-offset: calc((var(--switch-height) - var(--circle-diameter)) / 2);
    --switch-transition: all .2s cubic-bezier(0.27, 0.2, 0.25, 1.51);
    --circle-diameter: 15px;
    --circle-bg: #fff;
    --circle-shadow: 1px 1px 2px rgba(146, 146, 146, 0.45);
    --circle-checked-shadow: -1px 1px 2px rgba(163, 163, 163, 0.45);
    --circle-transition: var(--switch-transition);
    --icon-transition: all .2s cubic-bezier(0.27, 0.2, 0.25, 1.51);
    --icon-cross-color: var(--switch-bg);
    --icon-cross-size: 5px;
    --icon-checkmark-color: var(--switch-checked-bg);
    --icon-checkmark-size: 8px;
    --effect-width: calc(var(--circle-diameter) / 2);
    --effect-height: calc(var(--effect-width) / 2 - 1px);
    --effect-bg: var(--circle-bg);
    --effect-border-radius: 1px;
    --effect-transition: all .2s ease-in-out;
}
.switch input { display: none; }
.switch { display: inline-block; vertical-align: middle; }
.switch svg { transition: var(--icon-transition); position: absolute; height: auto; }
.switch .checkmark { width: var(--icon-checkmark-size); color: var(--icon-checkmark-color); transform: scale(0); }
.switch .cross { width: var(--icon-cross-size); color: var(--icon-cross-color); }
.switch .slider {
    box-sizing: border-box; width: var(--switch-width); height: var(--switch-height);
    background: var(--switch-bg); border-radius: 999px; display: flex; align-items: center;
    position: relative; transition: var(--switch-transition); cursor: pointer;
}
.switch .circle {
    width: var(--circle-diameter); height: var(--circle-diameter); background: var(--circle-bg);
    border-radius: inherit; box-shadow: var(--circle-shadow); display: flex; align-items: center;
    justify-content: center; transition: var(--circle-transition); z-index: 1; position: absolute;
    left: var(--switch-offset);
}
.switch .slider::before {
    content: ""; position: absolute; width: var(--effect-width); height: var(--effect-height);
    left: calc(var(--switch-offset) + (var(--effect-width) / 2)); background: var(--effect-bg);
    border-radius: var(--effect-border-radius); transition: var(--effect-transition);
}
.switch input:checked + .slider { background: var(--switch-checked-bg); }
.switch input:checked + .slider .checkmark { transform: scale(1); }
.switch input:checked + .slider .cross { transform: scale(0); }
.switch input:checked + .slider::before {
    left: calc(100% - var(--effect-width) - (var(--effect-width) / 2) - var(--switch-offset));
}
.switch input:checked + .slider .circle {
    left: calc(100% - var(--circle-diameter) - var(--switch-offset));
    box-shadow: var(--circle-checked-shadow);
}
</style>

<script>
function confirmDeletePackage(packageId, packageName) {
    Swal.fire({
        title: 'ยืนยันลบแพ็กเกจนี้ใช่ไหม?',
        text: `เมื่อลบแพ็กเกจ "${packageName}" แล้วจะไม่สามารถกู้คืนข้อมูลได้`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        confirmButtonText: 'ยืนยันการลบ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) document.getElementById('deleteForm-' + packageId).submit();
    });
}
</script>
@endsection
