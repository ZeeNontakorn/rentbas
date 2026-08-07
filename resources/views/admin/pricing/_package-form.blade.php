{{--
    Shared form for creating and editing promotion packages.
--}}
@php
    $isEdit = $package !== null;
    $availableDays = old('available_days', $isEdit ? ($package->available_days ?? []) : []);
@endphp

<form method="POST"
      action="{{ $isEdit ? route('admin.pricing.packages.update', $package) : route('admin.pricing.packages.store') }}"
      class="grid md:grid-cols-2 gap-4">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">รหัสแพ็กเกจ (ใช้ผูกกับระบบจอง, a-z 0-9 _ -)</label>
        <input type="text" name="code" required maxlength="50"
               value="{{ old('code', $isEdit ? $package->code : '') }}"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">ชื่อแพ็กเกจ (แสดงให้ลูกค้าเห็น)</label>
        <input type="text" name="label" required maxlength="150"
               value="{{ old('label', $isEdit ? $package->label : '') }}"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">หมวดหมู่ (ตั้งชื่อใหม่ได้อิสระ ใช้จัดกลุ่มแสดงผลเท่านั้น)</label>
        <input type="text" name="category" required maxlength="50"
               value="{{ old('category', $isEdit ? $package->category : '') }}"
               placeholder="เช่น personal, group, private, หรือชื่อกลุ่มใหม่"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">ประเภทสนามที่ใช้โปรได้ (เงื่อนไขจริงตอนจอง)</label>
        <select name="court_type" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
            @php $ct = old('court_type', $isEdit ? $package->court_type : null); @endphp
            <option value="" @selected($ct === null || $ct === '')>ใช้ได้ทั้งเต็มสนามและครึ่งสนาม</option>
            <option value="full" @selected($ct === 'full')>เต็มสนามเท่านั้น</option>
            <option value="half" @selected($ct === 'half')>ครึ่งสนามเท่านั้น</option>
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="block text-[11px] font-medium text-gray-500 mb-1">วันที่ใช้โปรนี้ได้ (เงื่อนไขจริงตอนจอง — ไม่เลือก = ใช้ได้ทุกวัน)</label>
        <div class="flex flex-wrap gap-4 bg-slate-50 border border-gray-200 rounded-lg px-3 py-2.5">
            @foreach (['weekday' => 'จันทร์-ศุกร์', 'weekend' => 'เสาร์-อาทิตย์', 'holiday' => 'วันหยุดนักขัตฤกษ์'] as $val => $lbl)
                <label class="inline-flex items-center gap-1.5 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="available_days[]" value="{{ $val }}"
                           @checked(in_array($val, $availableDays ?? []))
                           class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
                    {{ $lbl }}
                </label>
            @endforeach
        </div>
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">ใช้โปรได้ตั้งแต่เวลา (ไม่ระบุ = ไม่จำกัด)</label>
        <input type="text" name="available_start_time" placeholder="เช่น 09:00"
               value="{{ old('available_start_time', $isEdit && $package->available_start_time ? substr($package->available_start_time,0,5) : '') }}"
               class="time-picker w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
    </div>
    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">ใช้โปรได้ถึงเวลา (ไม่ระบุ = ไม่จำกัด)</label>
        <input type="text" name="available_end_time" placeholder="เช่น 16:00"
               value="{{ old('available_end_time', $isEdit && $package->available_end_time ? substr($package->available_end_time,0,5) : '') }}"
               class="time-picker w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">ระยะเวลาที่ต้องจอง (ชั่วโมง, เว้นว่าง = ไม่บังคับ/จองกี่ชั่วโมงก็ได้)</label>
        <input type="number" step="1" min="1" max="24" name="duration_hours"
               value="{{ old('duration_hours', $isEdit ? $package->duration_hours : '') }}"
               placeholder="เว้นว่าง = ไม่บังคับ"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
    </div>
    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">จำนวนคนสูงสุด</label>
        <input type="number" min="1" max="1000" name="max_people" required
               value="{{ old('max_people', $isEdit ? $package->max_people : 1) }}"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">ราคาปกติ (บาท)</label>
        <input type="number" step="0.01" min="0" name="base_price" required
               value="{{ old('base_price', $isEdit ? number_format($package->base_price / 100, 2, '.', '') : '') }}"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
    </div>
    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">ราคาวันหยุดนักขัตฤกษ์ (ไม่ระบุ = ใช้ราคาปกติ)</label>
        <input type="number" step="0.01" min="0" name="holiday_price"
               value="{{ old('holiday_price', $isEdit && $package->holiday_price !== null ? number_format($package->holiday_price / 100, 2, '.', '') : '') }}"
               placeholder="ไม่มี"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
    </div>

    <div class="md:col-span-2 grid grid-cols-3 gap-3 bg-slate-50 border border-gray-200 rounded-lg p-3">
        <div>
            <label class="block text-[11px] font-medium text-gray-500 mb-1">ราคาพิเศษเสาร์-อาทิตย์ (บาท)</label>
            <input type="number" step="0.01" min="0" name="weekend_special_price"
                   value="{{ old('weekend_special_price', $isEdit && $package->weekend_special_price !== null ? number_format($package->weekend_special_price / 100, 2, '.', '') : '') }}"
                   placeholder="ไม่มี"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
        </div>
        <div>
            <label class="block text-[11px] font-medium text-gray-500 mb-1">ตั้งแต่เวลา</label>
            <input type="text" name="weekend_special_start" placeholder="เช่น 07:00"
                   value="{{ old('weekend_special_start', $isEdit && $package->weekend_special_start ? substr($package->weekend_special_start,0,5) : '') }}"
                   class="time-picker w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
        </div>
        <div>
            <label class="block text-[11px] font-medium text-gray-500 mb-1">ถึงเวลา</label>
            <input type="text" name="weekend_special_end" placeholder="เช่น 11:00"
                   value="{{ old('weekend_special_end', $isEdit && $package->weekend_special_end ? substr($package->weekend_special_end,0,5) : '') }}"
                   class="time-picker w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
        </div>
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">จำนวนครั้ง/เซสชัน (ถ้าเป็นแพ็กเกจหลายครั้ง)</label>
        <input type="number" min="1" max="255" name="session_count"
               value="{{ old('session_count', $isEdit ? $package->session_count : '') }}"
               placeholder="ไม่มี"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
    </div>
    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">อายุแพ็กเกจ (วัน)</label>
        <input type="number" min="1" max="3650" name="validity_days"
               value="{{ old('validity_days', $isEdit ? $package->validity_days : '') }}"
               placeholder="ไม่มี"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
    </div>

    <div class="md:col-span-2 flex items-center gap-6 pt-1">
        <label class="inline-flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="checkbox" name="requires_verification" value="1"
                   @checked(old('requires_verification', $isEdit ? $package->requires_verification : false))
                   class="rounded border-gray-300 text-amber-500 focus:ring-amber-500">
            ต้องยืนยันสถานะ (เช่น บัตรนักเรียน/นักศึกษา)
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="checkbox" name="is_active" value="1"
                   @checked(old('is_active', $isEdit ? $package->is_active : true))
                   class="rounded border-gray-300 text-emerald-500 focus:ring-emerald-500">
            เปิดใช้งาน
        </label>
    </div>

    <div class="md:col-span-2 flex justify-end gap-2 pt-2 border-t border-gray-100 mt-1">
        @if($isEdit)
            <button type="button" onclick="resetPkgForm(this.closest('form')); closePkgDrawer('{{ $formId ?? null }}')"
                    class="text-xs font-medium text-gray-500 hover:text-gray-700 rounded-lg px-4 py-2 transition">
                ยกเลิก
            </button>
        @endif
        <button type="submit" class="text-xs font-medium text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg px-5 py-2 transition">
            {{ $isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มแพ็กเกจ' }}
        </button>
    </div>
</form>
