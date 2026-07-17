@extends('layouts.app')

@section('title', 'ตั้งค่าราคา')

@php
    $dayTypeLabel = [
        'everyday' => 'ทุกวัน (Sunset Time)',
        'weekday' => 'จันทร์-ศุกร์ (Weekday Time)',
        'weekend' => 'เสาร์-อาทิตย์',
        'holiday' => 'วันหยุดนักขัตฤกษ์ (Holiday Time)',
    ];
    $categoryLabel = [
        'personal' => 'Personal Shooting (2 ชั่วโมง)',
        'group' => 'Group Court (3 ชั่วโมง)',
        'private' => 'Private Group',
    ];
    $rulesByDayType = $pricingRules->groupBy('day_type');
    $packagesByCategory = $promotionPackages->groupBy('category');
@endphp

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-6 max-w-5xl">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">ตั้งค่าราคา</h1>
            <p class="text-sm text-gray-500 mt-1">ปรับราคาต่อชั่วโมงตามช่วงเวลา และราคาแพ็กเกจโปรโมชั่น — มีผลกับการคำนวณราคาจองทันทีที่บันทึก</p>
        </div>

        @if (session('success'))
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">
                @foreach ($errors->all() as $err)
                    <div>• {{ $err }}</div>
                @endforeach
            </div>
        @endif

        {{-- ═══ PRICING RULES (รายชั่วโมง) ═══ --}}
        <div class="flex items-center gap-2 mb-3 mt-2">
            <span class="w-2 h-2 rounded-full bg-orange-500"></span>
            <h2 class="font-medium text-gray-700 text-sm">ราคาตามช่วงเวลา (รายชั่วโมง)</h2>
        </div>

        @foreach ($rulesByDayType as $dayType => $rules)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-5">
                <div class="px-6 py-3 border-b border-gray-100 bg-slate-50">
                    <h3 class="font-medium text-gray-700 text-sm">{{ $dayTypeLabel[$dayType] ?? $dayType }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-gray-400 text-xs uppercase tracking-wide border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 font-medium">รายการ</th>
                                <th class="px-6 py-3 font-medium">ช่วงเวลา</th>
                                <th class="px-6 py-3 font-medium">ประเภทสนาม</th>
                                <th class="px-6 py-3 font-medium">ราคา/ชั่วโมง (บาท)</th>
                                <th class="px-6 py-3 font-medium">เปิดใช้งาน</th>
                                <th class="px-6 py-3 font-medium text-right">บันทึก</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($rules as $rule)
                                <tr>
                                    <form method="POST" action="{{ route('admin.pricing.rules.update', $rule) }}">
                                        @csrf
                                        @method('PUT')
                                        <td class="px-6 py-3 text-gray-800 font-medium">{{ $rule->label }}</td>
                                        <td class="px-6 py-3 text-gray-500">{{ substr($rule->start_time, 0, 5) }} - {{ substr($rule->end_time, 0, 5) }}</td>
                                        <td class="px-6 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $rule->court_type === 'full' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                                {{ $rule->court_type === 'full' ? 'เต็มสนาม' : 'ครึ่งสนาม' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3">
                                            <div class="flex items-center gap-1">
                                                <span class="text-gray-400">฿</span>
                                                <input type="number" step="0.01" min="0" name="price_per_hour"
                                                       value="{{ number_format($rule->price_per_hour / 100, 2, '.', '') }}"
                                                       class="w-24 rounded-lg border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                                            </div>
                                        </td>
                                        <td class="px-6 py-3">
                                            <label class="inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="is_active" value="1" @checked($rule->is_active)
                                                       class="rounded border-gray-300 text-orange-500 focus:ring-orange-500">
                                            </label>
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <button type="submit" class="text-xs font-medium text-white bg-orange-500 hover:bg-orange-600 rounded-lg px-3.5 py-1.5 transition">
                                                บันทึก
                                            </button>
                                        </td>
                                    </form>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        {{-- ═══ PROMOTION PACKAGES ═══ --}}
        <div class="flex items-center gap-2 mb-3 mt-10">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <h2 class="font-medium text-gray-700 text-sm">แพ็กเกจโปรโมชั่น</h2>
        </div>

        @foreach ($packagesByCategory as $category => $packages)
            <div class="mb-6">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2 px-1">
                    {{ $categoryLabel[$category] ?? $category }}
                </p>

                <div class="grid md:grid-cols-2 gap-4">
                    @foreach ($packages as $package)
                        <form method="POST" action="{{ route('admin.pricing.packages.update', $package) }}"
                              class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col gap-3">
                            @csrf
                            @method('PUT')

                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-semibold text-gray-800 text-[15px]">{{ $package->label }}</h3>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ $package->duration_hours }} ชั่วโมง
                                        @if($package->max_people) · สูงสุด {{ $package->max_people }} คน @endif
                                        @if($package->requires_verification)
                                            · <span class="text-amber-600">ต้องยืนยันสถานะ</span>
                                        @endif
                                    </p>
                                </div>
                                <label class="inline-flex items-center gap-1.5 text-xs text-gray-500 cursor-pointer flex-shrink-0">
                                    <input type="checkbox" name="is_active" value="1" @checked($package->is_active)
                                           class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
                                    เปิดใช้งาน
                                </label>
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[11px] text-gray-400 mb-1">ราคาปกติ (บาท)</label>
                                    <input type="number" step="0.01" min="0" name="base_price"
                                           value="{{ number_format($package->base_price / 100, 2, '.', '') }}"
                                           class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] text-gray-400 mb-1">ราคาวันหยุดนักขัตฤกษ์</label>
                                    <input type="number" step="0.01" min="0" name="holiday_price"
                                           value="{{ $package->holiday_price !== null ? number_format($package->holiday_price / 100, 2, '.', '') : '' }}"
                                           placeholder="ไม่มี"
                                           class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] text-gray-400 mb-1">
                                        ราคาเสาร์-อาทิตย์
                                        @if($package->weekend_special_start)
                                            <span class="block text-gray-300">({{ substr($package->weekend_special_start,0,5) }}-{{ substr($package->weekend_special_end,0,5) }})</span>
                                        @endif
                                    </label>
                                    <input type="number" step="0.01" min="0" name="weekend_special_price"
                                           value="{{ $package->weekend_special_price !== null ? number_format($package->weekend_special_price / 100, 2, '.', '') : '' }}"
                                           placeholder="ไม่มี"
                                           class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
                                </div>
                            </div>

                            <button type="submit" class="self-end text-xs font-medium text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg px-4 py-1.5 transition">
                                บันทึก
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endforeach

    </div>
</div>
@endsection
