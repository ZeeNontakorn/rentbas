@extends('layouts.app')

@section('title', 'ตั้งค่าราคา')

@php
    $dayTypeLabel = [
        'weekday'  => 'วันจันทร์–วันศุกร์',
        'weekend'  => 'วันเสาร์–วันอาทิตย์',
        'holiday'  => 'วันหยุดนักขัตฤกษ์',
        'everyday' => 'ทุกวัน (ช่วงค่ำ)',
    ];
    $categoryLabel = [
        'personal' => 'Personal Shooting (2 ชั่วโมง)',
        'group'    => 'Group Court (3 ชั่วโมง)',
        'private'  => 'Private Group',
    ];
    $rulesByDayType = $pricingRules->groupBy('day_type');
    $packagesByCategory = $promotionPackages->groupBy('category');
@endphp

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-4 sm:px-6 max-w-7xl">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-[32px] font-bold text-gray-900 tracking-tight">ตั้งค่าราคา</h1>
            <p class="text-sm text-gray-500 mt-1">ปรับราคาต่อชั่วโมงตามช่วงเวลา และราคาแพ็กเกจโปรโมชั่น — มีผลกับการคำนวณราคาจองทันทีที่บันทึก</p>
        </div>

        {{-- ═══ PRICING RULES (รายชั่วโมง) ═══ --}}
        <div class="flex items-center gap-2 mb-3 mt-2">
            <span class="w-2 h-2 rounded-full bg-orange-500"></span>
            <h2 class="font-medium text-gray-700 text-sm">ราคาตามช่วงเวลา (รายชั่วโมง)</h2>
        </div>

        @foreach ($rulesByDayType as $dayType => $rules)
            @php
                // ช่วงเวลาที่ตั้งราคาได้ ผูกกับ $dayType ของการ์ดนี้ (ไม่ hardcode ราย rule/id) — การ์ด
                // "ช่วงค่ำ" (day_type = everyday) ตั้งได้ 16:00–23:00 ส่วนการ์ดกลางวันอื่นๆ ตั้งได้ 06:00–16:00
                $isEveningCard = $dayType === 'everyday';
                $cardMinTime = $isEveningCard ? '16:00' : '06:00';
                $cardMaxTime = $isEveningCard ? '23:00' : '16:00';
            @endphp
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-5">
                <div class="px-6 py-3 border-b border-gray-100 bg-slate-50">
                    <h3 class="font-medium text-gray-700 text-sm">{{ $dayTypeLabel[$dayType] ?? $dayType }}</h3>
                    <p class="text-[11px] text-gray-400 mt-0.5">ตั้งช่วงเวลาได้ระหว่าง {{ $cardMinTime }}–{{ $cardMaxTime }} น.</p>
                </div>

                {{-- One form per card: all rows submit together, single save button at the bottom --}}
                <form method="POST" action="{{ route('admin.pricing.rules.bulkUpdate') }}"
                      class="rules-card-form" id="rules-form-{{ $dayType }}">
                    @csrf
                    @method('PUT')

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-gray-400 text-xs uppercase tracking-wide border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-3 font-medium">รายการ</th>
                                    <th class="px-6 py-3 font-medium">ช่วงเวลา</th>
                                    <th class="px-6 py-3 font-medium">ประเภทสนาม</th>
                                    <th class="px-6 py-3 font-medium">ราคา/ชั่วโมง (บาท)</th>
                                    <th class="px-6 py-3 font-medium">เปิดใช้งาน</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($rules as $rule)
                                    <tr>
                                        <td class="px-6 py-3 text-gray-800 font-medium max-w-[200px]">
                                            {{ $rule->label }}
                                        </td>
                                        <td class="px-6 py-3">
                                            <div class="flex items-center gap-2">
                                                <input type="text" name="rules[{{ $rule->id }}][start_time]"
                                                       value="{{ substr($rule->start_time, 0, 5) }}"
                                                       data-min-time="{{ $cardMinTime }}" data-max-time="{{ $cardMaxTime }}"
                                                       class="time-picker w-32 text-sm text-gray-800 rounded-lg border border-gray-300 px-2 py-1 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                                                <span class="text-gray-400">–</span>
                                                <input type="text" name="rules[{{ $rule->id }}][end_time]"
                                                       value="{{ substr($rule->end_time, 0, 5) }}"
                                                       data-min-time="{{ $cardMinTime }}" data-max-time="{{ $cardMaxTime }}"
                                                       class="time-picker w-32 text-sm text-gray-800 rounded-lg border border-gray-300 px-2 py-1 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                                            </div>
                                        </td>
                                        <td class="px-6 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $rule->court_type === 'full' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                                {{ $rule->court_type === 'full' ? 'เต็มสนาม' : 'ครึ่งสนาม' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3">
                                            <div class="flex items-center gap-1">
                                                <span class="text-gray-400">฿</span>
                                                <input type="number" step="0.01" min="0" name="rules[{{ $rule->id }}][price_per_hour]"
                                                       value="{{ number_format($rule->price_per_hour / 100, 2, '.', '') }}"
                                                       class="w-24 rounded-lg border border-gray-300 px-2 py-1 text-sm text-gray-800 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                                            </div>
                                        </td>
                                        <td class="px-6 py-3 text-center">
                                            <label class="relative inline-flex cursor-pointer items-center">
                                                <input type="hidden" name="rules[{{ $rule->id }}][is_active]" value="0">
                                                <input type="checkbox" name="rules[{{ $rule->id }}][is_active]" value="1" @checked($rule->is_active)
                                                       class="peer sr-only">
                                                <span class="relative h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-emerald-500 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-all after:content-[''] peer-checked:after:translate-x-5"></span>
                                            </label>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end gap-2 px-6 py-3 border-t border-gray-100 bg-slate-50">
                        <button type="button" class="rules-cancel-btn text-xs font-medium text-gray-500 hover:text-gray-700 rounded-lg px-4 py-1.5 transition"
                                data-target="rules-form-{{ $dayType }}">
                            ยกเลิก
                        </button>
                        <button type="submit" class="text-xs font-medium text-white bg-orange-500 hover:bg-orange-600 rounded-lg px-4 py-1.5 transition">
                            บันทึก
                        </button>
                    </div>
                </form>
            </div>
        @endforeach

        {{-- ═══ PROMOTION PACKAGES ═══ --}}
        <div class="flex items-center justify-between mb-3 mt-10">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <h2 class="font-medium text-gray-700 text-sm">แพ็กเกจโปรโมชั่น</h2>
            </div>
            <button type="button" onclick="toggleCreatePkgForm()"
                    class="text-xs font-medium text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg px-4 py-2 transition">
                + เพิ่มแพ็กเกจใหม่
            </button>
        </div>

        {{-- Create form (ซ่อนไว้ก่อน กดปุ่มด้านบนเพื่อเปิด) --}}
        <div id="pkg-create-form" class="hidden bg-white rounded-xl shadow-sm border border-emerald-200 p-5 mb-6">
            <h3 class="font-semibold text-gray-800 text-[15px] mb-4">เพิ่มแพ็กเกจโปรโมชั่นใหม่</h3>
            @include('admin.pricing._package-form', ['package' => null, 'formId' => 'pkg-create-form', 'existingCategories' => $existingPackageCategories])
        </div>

        @foreach ($packagesByCategory as $category => $packages)
            <div class="mb-6">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2 px-1">
                    {{ $categoryLabel[$category] ?? $category }}
                </p>

                {{-- CSS multi-column layout instead of grid: cards flow independently
                     per column, so expanding one card's edit panel only pushes
                     cards below it in the SAME column, not the card beside it.
                     NOTE: the edit drawer (.pkg-drawer, position:fixed) must NEVER be
                     rendered inside this columns-* container — position:fixed elements
                     nested inside a CSS multi-column formatting context render
                     incorrectly in several browsers (can flash/hang blank-white on
                     reflow, e.g. when a peer-checked switch inside the drawer
                     transitions). Drawers are rendered in a separate loop further
                     below, as siblings of this container instead. --}}
                <div class="columns-1 md:columns-2 gap-4">
                    @foreach ($packages as $package)
                        @php
                            $dayLabelsMap = ['weekday' => 'จ-ศ', 'weekend' => 'ส-อา', 'holiday' => 'วันหยุดนักขัตฤกษ์'];
                            $conditionBadges = [];
                            if ($package->court_type) {
                                $conditionBadges[] = $package->court_type === 'full' ? 'เต็มสนามเท่านั้น' : 'ครึ่งสนามเท่านั้น';
                            }
                            if (!empty($package->available_days)) {
                                $conditionBadges[] = implode('/', array_map(fn($d) => $dayLabelsMap[$d] ?? $d, $package->available_days));
                            }
                            if ($package->available_start_time || $package->available_end_time) {
                                $conditionBadges[] = (substr($package->available_start_time ?? '00:00',0,5)) . '-' . (substr($package->available_end_time ?? '23:59',0,5));
                            }
                            $editId = 'pkg-edit-' . $package->id;
                        @endphp

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col gap-3 break-inside-avoid mb-4">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-semibold text-gray-800 text-[15px]">{{ $package->label }}</h3>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ $package->duration_hours !== null ? $package->duration_hours . ' ชั่วโมง' : 'ไม่จำกัดเวลา' }}
                                        @if($package->max_people) · สูงสุด {{ $package->max_people }} คน @endif
                                        @if($package->requires_verification)
                                            · <span class="text-amber-600">ต้องยืนยันสถานะ</span>
                                        @endif
                                    </p>
                                    @if(count($conditionBadges))
                                        <div class="flex flex-wrap gap-1.5 mt-2">
                                            @foreach ($conditionBadges as $badge)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 text-blue-600 border border-blue-100">{{ $badge }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium flex-shrink-0 {{ $package->is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-400' }}">
                                    {{ $package->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs text-gray-500">
                                <div>ราคาปกติ: <span class="font-semibold text-gray-800">฿{{ number_format($package->base_price / 100, 0) }}</span></div>
                                @if($package->holiday_price !== null)
                                    <div>วันหยุดนักขัตฤกษ์: <span class="font-semibold text-gray-800">฿{{ number_format($package->holiday_price / 100, 0) }}</span></div>
                                @endif
                                @if($package->weekend_special_price !== null)
                                    <div class="col-span-2">
                                        เสาร์-อาทิตย์ ({{ substr($package->weekend_special_start,0,5) }}-{{ substr($package->weekend_special_end,0,5) }}):
                                        <span class="font-semibold text-gray-800">฿{{ number_format($package->weekend_special_price / 100, 0) }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="flex justify-end gap-2 pt-1 border-t border-gray-100">
                                <button type="button" onclick="openPkgDrawer('{{ $editId }}')"
                                        class="text-xs font-medium text-blue-600 hover:text-blue-800 rounded-lg px-3 py-1.5 transition">
                                    แก้ไข
                                </button>
                                <button type="button" onclick="confirmDeletePromoPackage('{{ $package->id }}', '{{ addslashes($package->label) }}')"
                                        class="text-xs font-medium text-red-500 hover:text-red-700 rounded-lg px-3 py-1.5 transition">
                                    ลบ
                                </button>
                                <form id="deletePromoPkg{{ $package->id }}" method="POST" action="{{ route('admin.pricing.packages.destroy', $package) }}" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Edit drawers for this category, rendered OUTSIDE the columns-* container
                     on purpose — see note above the container's opening tag. --}}
                @foreach ($packages as $package)
                    @php $editId = 'pkg-edit-' . $package->id; @endphp
                    <div id="{{ $editId }}" class="pkg-drawer hidden fixed inset-y-0 right-0 z-50 w-full sm:w-[50%] bg-white shadow-2xl border-l border-gray-200 overflow-y-auto">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 sticky top-0 bg-white z-10">
                            <h3 class="font-semibold text-gray-800 text-[15px]">แก้ไข: {{ $package->label }}</h3>
                            <button type="button" onclick="closePkgDrawer('{{ $editId }}')"
                                    class="text-gray-400 hover:text-gray-600 rounded-lg p-1 transition">
                                ✕
                            </button>
                        </div>
                        <div class="p-5">
                            @include('admin.pricing._package-form', ['package' => $package, 'formId' => $editId, 'existingCategories' => $existingPackageCategories])
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach

        {{-- Shared backdrop for the edit drawer --}}
        <div id="pkg-drawer-backdrop" class="hidden fixed inset-0 bg-black/30 z-40" onclick="closeAllPkgDrawers()"></div>
    </div>
</div>
@endsection

<script>
    // ─── Toast แจ้งผลลัพธ์แบบไม่บล็อกหน้าจอ (รูปแบบเดียวกับหน้าแพ็กเกจเครดิต) ───
    function showToast(text, isError) {
        const toast = document.getElementById('pageToast');
        if (!toast) return;
        toast.textContent = text;
        toast.classList.remove('hidden', 'bg-gray-900', 'bg-red-600');
        toast.classList.add(isError ? 'bg-red-600' : 'bg-gray-900');
        clearTimeout(showToast._t);
        showToast._t = setTimeout(() => toast.classList.add('hidden'), 2200);
    }

    document.addEventListener('DOMContentLoaded', function() {
        // ดึงช่อง input ทั้งหมดที่มี class .time-picker
        const timePickers = document.querySelectorAll('.time-picker');

        timePickers.forEach(function(input) {
            flatpickr(input, {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                defaultDate: input.value,
                // ช่วงเวลาที่เลือกได้ (ถ้ามี data-min-time/data-max-time) มาจาก server ตาม day_type
                // ของการ์ดนั้นๆ ไม่ได้ hardcode ไว้ใน JS — ดู index.blade.php ส่วน $cardMinTime/$cardMaxTime
                minTime: input.dataset.minTime || undefined,
                maxTime: input.dataset.maxTime || undefined,
            });
        });

        // ─── แจ้งผลลัพธ์จาก server ตอนโหลดหน้า: สำเร็จ → toast เล็กๆ มุมล่างขวา, ผิดพลาด → SweetAlert
        // (มีหลายบรรทัด/สำคัญกว่า จึงใช้ modal แทน toast ที่หายไปเร็วเกินจะอ่านทัน) ───
        @if (session('success'))
            showToast(@js(session('success')));
        @endif
        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'ทำรายการไม่สำเร็จ',
                html: @js('• ' . implode('<br>• ', $errors->all())),
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'เข้าใจแล้ว',
            });
        @endif

        // ─── Pricing rules card: cancel button reverts all fields in that card ───
        document.querySelectorAll('.rules-cancel-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var form = document.getElementById(btn.getAttribute('data-target'));
                if (!form) return;

                form.reset(); // reverts every field back to its page-load value

                // flatpickr keeps its own internal state, so re-sync the visible
                // value after a native reset
                form.querySelectorAll('.time-picker').forEach(function (input) {
                    if (input._flatpickr) {
                        input._flatpickr.setDate(input.value, true);
                    }
                });
            });
        });
    });

    // ─── ยืนยันลบแพ็กเกจโปรโมชั่นด้วย SweetAlert (รูปแบบเดียวกับหน้าแพ็กเกจเครดิต) ───
    function confirmDeletePromoPackage(packageId, packageLabel) {
        Swal.fire({
            title: 'ยืนยันลบแพ็กเกจนี้ใช่ไหม?',
            html: `เมื่อลบแพ็กเกจ "${packageLabel}" แล้วจะไม่สามารถกู้คืนข้อมูลได้<br>`
                + `<span class="text-gray-400 text-sm">(การจองเก่าที่เคยใช้แพ็กเกจนี้จะไม่ถูกลบ แค่ตัดการเชื่อมโยงกับแพ็กเกจนี้)</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ยืนยันการลบ',
            cancelButtonText: 'ยกเลิก',
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deletePromoPkg' + packageId).submit();
            }
        });
    }

    // ─── Package create panel (inline, not a drawer) ───
    function toggleCreatePkgForm() {
        // Any open edit drawer would otherwise sit on top of (and block clicks to)
        // the create panel, so close drawers whenever the create panel is toggled.
        closeAllPkgDrawers();
        document.getElementById('pkg-create-form').classList.toggle('hidden');
    }

    // Reverts a package form's fields back to their page-load values.
    // Used by the edit-drawer "ยกเลิก" button so re-opening a drawer after
    // cancelling doesn't show whatever was typed before.
    function resetPkgForm(form) {
        if (!form) return;
        form.reset();

        // flatpickr keeps its own internal state, so re-sync the visible
        // value after a native reset
        form.querySelectorAll('.time-picker').forEach(function (input) {
            if (input._flatpickr) {
                input._flatpickr.setDate(input.value, true);
            }
        });
    }

    // ─── Package edit drawer (fixed panel, outside document flow) ───
    function closeAllPkgDrawers() {
        document.querySelectorAll('.pkg-drawer').forEach(function(el) {
            el.classList.add('hidden');
        });
        var backdrop = document.getElementById('pkg-drawer-backdrop');
        if (backdrop) backdrop.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function openPkgDrawer(id) {
        closeAllPkgDrawers();
        // The inline "create new" panel isn't a .pkg-drawer, so it survives
        // closeAllPkgDrawers() above — hide it explicitly so it can't sit open
        // behind the edit drawer/backdrop.
        document.getElementById('pkg-create-form').classList.add('hidden');

        var panel = document.getElementById(id);
        var backdrop = document.getElementById('pkg-drawer-backdrop');
        if (panel) panel.classList.remove('hidden');
        if (backdrop) backdrop.classList.remove('hidden');
        document.body.classList.add('overflow-hidden'); // lock page scroll while drawer is open
    }

    function closePkgDrawer(id) {
        var panel = document.getElementById(id);
        if (panel) panel.classList.add('hidden');
        var backdrop = document.getElementById('pkg-drawer-backdrop');
        if (backdrop) backdrop.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeAllPkgDrawers();
    });
</script>
