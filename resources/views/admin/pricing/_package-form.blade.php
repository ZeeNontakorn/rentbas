{{--
    Shared form for creating and editing promotion packages.
--}}
@php
    $isEdit = $package !== null;
    $availableDays = old('available_days', $isEdit ? ($package->available_days ?? []) : []);
    $existingCategories = $existingCategories ?? [];
@endphp

<form method="POST"
      action="{{ $isEdit ? route('admin.pricing.packages.update', $package) : route('admin.pricing.packages.store') }}"
      class="grid md:grid-cols-2 gap-4">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="md:col-span-2">
        <label class="block text-[11px] font-medium text-gray-500 mb-1">ชื่อแพ็กเกจ (แสดงให้ลูกค้าเห็น) <span class="text-red-500">*</span></label>
        <input type="text" name="label" maxlength="150"
               value="{{ old('label', $isEdit ? $package->label : '') }}"
               class="package-label-input w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 {{ $errors->has('label') ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
        @error('label')
            <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
        @enderror
        <p class="text-[11px] text-gray-400 mt-1.5">
            รหัสภายใน (auto):
            <code class="package-code-preview font-mono text-gray-600">{{ old('code', $isEdit ? $package->code : '') ?: '—' }}</code>
            <button type="button" class="package-code-edit-toggle text-emerald-600 hover:text-emerald-700 font-medium ml-1 underline underline-offset-2">แก้ไขเอง</button>
        </p>
        <input type="text" name="code" maxlength="50" placeholder="เช่น personal-shooting-2-hours"
               value="{{ old('code', $isEdit ? $package->code : '') }}"
               class="package-code-input {{ $errors->has('code') ? '' : 'hidden' }} w-full mt-1.5 rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 {{ $errors->has('code') ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
        @error('code')
            <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="relative" data-category-combobox>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">หมวดหมู่ (คลิกเลือกของเดิม หรือพิมพ์เพื่อสร้างใหม่) <span class="text-red-500">*</span></label>
        <input type="text" name="category" maxlength="50" autocomplete="off"
               value="{{ old('category', $isEdit ? $package->category : '') }}"
               placeholder="เช่น personal, group, private, หรือชื่อกลุ่มใหม่"
               class="category-combobox-input w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 {{ $errors->has('category') ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
        <div class="category-combobox-dropdown hidden absolute z-20 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
            @forelse ($existingCategories as $existingCategory)
                <button type="button" class="category-combobox-option w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 transition" data-value="{{ $existingCategory }}">
                    {{ $existingCategory }}
                </button>
            @empty
            @endforelse
            <div class="category-combobox-empty hidden px-3 py-2 text-xs text-gray-400">ไม่พบหมวดหมู่ที่ตรงกัน — พิมพ์ต่อเพื่อสร้างหมวดหมู่ใหม่</div>
        </div>
        @error('category')
            <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">ประเภทสนามที่ใช้โปรได้ (เงื่อนไขจริงตอนจอง)</label>
        <select name="court_type" class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 {{ $errors->has('court_type') ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
            @php $ct = old('court_type', $isEdit ? $package->court_type : null); @endphp
            <option value="" @selected($ct === null || $ct === '')>ใช้ได้ทั้งเต็มสนามและครึ่งสนาม</option>
            <option value="full" @selected($ct === 'full')>เต็มสนามเท่านั้น</option>
            <option value="half" @selected($ct === 'half')>ครึ่งสนามเท่านั้น</option>
        </select>
        @error('court_type')
            <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-[11px] font-medium text-gray-500 mb-1">วันที่ใช้โปรนี้ได้ (เงื่อนไขจริงตอนจอง — ไม่เลือก = ใช้ได้ทุกวัน)</label>
        <div data-field-wrap="available_days" class="flex flex-wrap gap-x-6 gap-y-2 bg-slate-50 rounded-lg px-3 py-2.5 border {{ $errors->has('available_days') || $errors->has('available_days.*') ? 'border-red-500' : 'border-gray-200' }}">
            @foreach (['weekday' => 'จันทร์-ศุกร์', 'weekend' => 'เสาร์-อาทิตย์', 'holiday' => 'วันหยุดนักขัตฤกษ์'] as $val => $lbl)
                <label class="inline-flex items-center gap-3 text-sm text-gray-600 cursor-pointer">
                    <input type="checkbox" name="available_days[]" value="{{ $val }}" class="peer sr-only"
                           @checked(in_array($val, $availableDays ?? []))>
                    <span class="relative h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-emerald-500 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-all after:content-[''] peer-checked:after:translate-x-5"></span>
                    {{ $lbl }}
                </label>
            @endforeach
        </div>
        @error('available_days.*')
            <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">ใช้โปรได้ตั้งแต่เวลา (ไม่ระบุ = ไม่จำกัด)</label>
        <input type="text" name="available_start_time" placeholder="เช่น 09:00"
               value="{{ old('available_start_time', $isEdit && $package->available_start_time ? substr($package->available_start_time,0,5) : '') }}"
               class="time-picker w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 {{ $errors->has('available_start_time') ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
        @error('available_start_time')
            <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">ใช้โปรได้ถึงเวลา (ไม่ระบุ = ไม่จำกัด)</label>
        <input type="text" name="available_end_time" placeholder="เช่น 16:00"
               value="{{ old('available_end_time', $isEdit && $package->available_end_time ? substr($package->available_end_time,0,5) : '') }}"
               class="time-picker w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 {{ $errors->has('available_end_time') ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
        @error('available_end_time')
            <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">ระยะเวลาที่ต้องจอง (ชั่วโมง, เว้นว่าง = ไม่บังคับ/จองกี่ชั่วโมงก็ได้)</label>
        <input type="number" step="1" min="1" max="24" name="duration_hours"
               value="{{ old('duration_hours', $isEdit ? $package->duration_hours : '') }}"
               placeholder="เว้นว่าง = ไม่บังคับ"
               class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 {{ $errors->has('duration_hours') ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
        @error('duration_hours')
            <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">จำนวนคนสูงสุด <span class="text-red-500">*</span></label>
        <input type="number" min="1" max="1000" name="max_people"
               value="{{ old('max_people', $isEdit ? $package->max_people : 1) }}"
               class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 {{ $errors->has('max_people') ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
        @error('max_people')
            <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">ราคาปกติ (บาท) <span class="text-red-500">*</span></label>
        <input type="number" step="0.01" min="0" name="base_price"
               value="{{ old('base_price', $isEdit ? number_format($package->base_price / 100, 2, '.', '') : '') }}"
               class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 {{ $errors->has('base_price') ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
        @error('base_price')
            <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">ราคาวันหยุดนักขัตฤกษ์ (ไม่ระบุ = ใช้ราคาปกติ)</label>
        <input type="number" step="0.01" min="0" name="holiday_price"
               value="{{ old('holiday_price', $isEdit && $package->holiday_price !== null ? number_format($package->holiday_price / 100, 2, '.', '') : '') }}"
               placeholder="ไม่มี"
               class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 {{ $errors->has('holiday_price') ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
        @error('holiday_price')
            <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2 grid grid-cols-3 gap-3 bg-slate-50 border border-gray-200 rounded-lg p-3">
        <div>
            <label class="block text-[11px] font-medium text-gray-500 mb-1">ราคาพิเศษเสาร์-อาทิตย์ (บาท)</label>
            <input type="number" step="0.01" min="0" name="weekend_special_price"
                   value="{{ old('weekend_special_price', $isEdit && $package->weekend_special_price !== null ? number_format($package->weekend_special_price / 100, 2, '.', '') : '') }}"
                   placeholder="ไม่มี"
                   class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 {{ $errors->has('weekend_special_price') ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
            @error('weekend_special_price')
                <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-[11px] font-medium text-gray-500 mb-1">ตั้งแต่เวลา <span class="text-red-500">*</span><span class="text-gray-400 font-normal">(ถ้าใส่ราคาพิเศษ)</span></label>
            <input type="text" name="weekend_special_start" placeholder="เช่น 07:00"
                   value="{{ old('weekend_special_start', $isEdit && $package->weekend_special_start ? substr($package->weekend_special_start,0,5) : '') }}"
                   class="time-picker w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 {{ $errors->has('weekend_special_start') ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
            @error('weekend_special_start')
                <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-[11px] font-medium text-gray-500 mb-1">ถึงเวลา <span class="text-red-500">*</span><span class="text-gray-400 font-normal">(ถ้าใส่ราคาพิเศษ)</span></label>
            <input type="text" name="weekend_special_end" placeholder="เช่น 11:00"
                   value="{{ old('weekend_special_end', $isEdit && $package->weekend_special_end ? substr($package->weekend_special_end,0,5) : '') }}"
                   class="time-picker w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 {{ $errors->has('weekend_special_end') ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
            @error('weekend_special_end')
                <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">จำนวนครั้ง/เซสชัน (ถ้าเป็นแพ็กเกจหลายครั้ง)</label>
        <input type="number" min="1" max="255" name="session_count"
               value="{{ old('session_count', $isEdit ? $package->session_count : '') }}"
               placeholder="ไม่มี"
               class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 {{ $errors->has('session_count') ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
        @error('session_count')
            <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">อายุแพ็กเกจ (วัน)</label>
        <input type="number" min="1" max="3650" name="validity_days"
               value="{{ old('validity_days', $isEdit ? $package->validity_days : '') }}"
               placeholder="ไม่มี"
               class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 {{ $errors->has('validity_days') ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500/20 focus:border-emerald-500' }}">
        @error('validity_days')
            <p class="text-[11px] text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2 flex items-center gap-6 pt-1">
        <label class="inline-flex items-center gap-3 text-sm text-gray-600 cursor-pointer">
            <input type="checkbox" name="requires_verification" value="1" class="peer sr-only"
                   @checked(old('requires_verification', $isEdit ? $package->requires_verification : false))>
            <span class="relative h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-amber-500 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-all after:content-[''] peer-checked:after:translate-x-5"></span>
            <span>ต้องยืนยันสถานะ (เช่น บัตรนักเรียน/นักศึกษา)</span>
        </label>
        <label class="inline-flex items-center gap-3 text-sm text-gray-600 cursor-pointer">
            <input type="checkbox" name="is_active" value="1" class="peer sr-only"
                   @checked(old('is_active', $isEdit ? $package->is_active : true))>
            <span class="relative h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-emerald-500 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-all after:content-[''] peer-checked:after:translate-x-5"></span>
            <span>เปิดใช้งาน</span>
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

<script>
    (function () {
        const form = document.currentScript?.previousElementSibling;
        if (!form || !(form instanceof HTMLFormElement)) return;

        // ─── รหัสแพ็กเกจ: gen อัตโนมัติจากชื่อแพ็กเกจแบบ live (ทุกครั้งที่พิมพ์ ไม่ต้องรอ blur)
        // แสดงเป็น preview อ่านอย่างเดียว ซ่อน input จริงไว้ (แต่ยังส่งค่าไปกับฟอร์มตามปกติ)
        // มีปุ่ม "แก้ไขเอง" ให้เปิด input ออกมาแก้ตรงๆ ได้ ถ้าเปิดแล้วจะหยุด auto-sync ทันที ───
        const labelInput = form.querySelector('.package-label-input');
        const codeInput = form.querySelector('.package-code-input');
        const codePreview = form.querySelector('.package-code-preview');
        const codeEditToggle = form.querySelector('.package-code-edit-toggle');

        function slugify(value) {
            return value
                .trim()
                .toLowerCase()
                .normalize('NFKD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        if (labelInput && codeInput && codePreview) {
            // ถ้ามีค่า code อยู่แล้วตอนโหลดหน้า (เช่นตอนแก้ไขแพ็กเกจเดิม หรือ validation error แล้ว
            // ฟอร์ม refill ค่าเดิมกลับมา) ถือว่าแอดมินตั้งใจกำหนดเองไว้แล้ว ไม่ auto-sync ทับ
            let manualMode = codeInput.value.trim() !== '';

            function renderPreview() {
                const slug = slugify(labelInput.value) || 'package';
                codePreview.textContent = manualMode ? (codeInput.value.trim() || '—') : slug;
                if (!manualMode) {
                    codeInput.value = slug;
                }
            }

            labelInput.addEventListener('input', renderPreview);
            codeInput.addEventListener('input', () => {
                codePreview.textContent = codeInput.value.trim() || '—';
            });

            if (codeEditToggle) {
                codeEditToggle.addEventListener('click', () => {
                    manualMode = true;
                    codeInput.classList.remove('hidden');
                    codeInput.focus();
                });
            }

            renderPreview();
        }

        // ─── หมวดหมู่: กดเลือกของเดิมได้ (dropdown เปิดตอน focus, filter ตามที่พิมพ์) หรือพิมพ์
        // ชื่อใหม่ที่ไม่มีในลิสต์ก็สร้างหมวดหมู่ใหม่ได้เลย (backend รับ string อิสระอยู่แล้ว) ───
        const categoryWrap = form.querySelector('[data-category-combobox]');
        if (categoryWrap) {
            const categoryInput = categoryWrap.querySelector('.category-combobox-input');
            const dropdown = categoryWrap.querySelector('.category-combobox-dropdown');
            const options = Array.from(categoryWrap.querySelectorAll('.category-combobox-option'));
            const emptyState = categoryWrap.querySelector('.category-combobox-empty');

            function filterCategoryOptions() {
                const q = categoryInput.value.trim().toLowerCase();
                let anyVisible = false;
                options.forEach((opt) => {
                    const match = opt.dataset.value.toLowerCase().includes(q);
                    opt.classList.toggle('hidden', !match);
                    if (match) anyVisible = true;
                });
                emptyState.classList.toggle('hidden', anyVisible);
            }

            categoryInput.addEventListener('focus', () => {
                filterCategoryOptions();
                dropdown.classList.remove('hidden');
            });
            categoryInput.addEventListener('input', filterCategoryOptions);

            options.forEach((opt) => {
                opt.addEventListener('click', () => {
                    categoryInput.value = opt.dataset.value;
                    dropdown.classList.add('hidden');
                });
            });

            document.addEventListener('click', (e) => {
                if (!categoryWrap.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        }

        // ─── ส่งฟอร์มด้วย AJAX แทนการ submit ปกติ: กันไม่ให้หน้ารีโหลด/ลิ้นชักฟอร์มหุบไปตอน
        // validation ไม่ผ่าน — error จะโชว์กรอบแดง + ข้อความใต้ช่องนั้นๆ ทันทีโดยไม่ต้องเปิดฟอร์มใหม่
        // (ถ้า JS พังหรือถูกปิด ฟอร์มยัง fallback เป็น native submit ตามปกติได้อยู่) ───
        const submitBtn = form.querySelector('button[type="submit"]');

        function clearFieldErrors() {
            form.querySelectorAll('.js-field-error').forEach((el) => el.remove());
            form.querySelectorAll('input:not([type=checkbox]), select').forEach((el) => {
                el.classList.remove('border-red-500', 'focus:ring-red-500/20', 'focus:border-red-500');
                el.classList.add('border-gray-300', 'focus:ring-emerald-500/20', 'focus:border-emerald-500');
            });
            const daysWrap = form.querySelector('[data-field-wrap="available_days"]');
            if (daysWrap) {
                daysWrap.classList.remove('border-red-500');
                daysWrap.classList.add('border-gray-200');
            }
        }

        function setFieldError(field, message) {
            let target;
            if (field.startsWith('available_days')) {
                target = form.querySelector('[data-field-wrap="available_days"]');
                if (target) {
                    target.classList.remove('border-gray-200');
                    target.classList.add('border-red-500');
                }
            } else {
                target = form.querySelector(`[name="${field}"]`);
                if (target) {
                    target.classList.remove('border-gray-300', 'focus:ring-emerald-500/20', 'focus:border-emerald-500', 'hidden');
                    target.classList.add('border-red-500', 'focus:ring-red-500/20', 'focus:border-red-500');
                }
            }
            if (!target) return;
            const p = document.createElement('p');
            p.className = 'js-field-error text-[11px] text-red-500 mt-1';
            p.textContent = message;
            target.insertAdjacentElement('afterend', p);
        }

        function showToast(icon, title) {
            if (window.Swal) {
                Swal.fire({
                    toast: true, position: 'top-end', icon, title,
                    showConfirmButton: false, timer: 3000, timerProgressBar: true,
                });
            } else {
                alert(title);
            }
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearFieldErrors();

            const originalLabel = submitBtn ? submitBtn.textContent : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'กำลังบันทึก...';
            }

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                });

                if (res.status === 422) {
                    const payload = await res.json();
                    const errors = payload.errors || {};
                    Object.entries(errors).forEach(([field, messages]) => {
                        setFieldError(field, Array.isArray(messages) ? messages[0] : messages);
                    });
                    const firstErrorEl = form.querySelector('.border-red-500');
                    if (firstErrorEl) {
                        firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    showToast('error', 'กรอกข้อมูลไม่ถูกต้อง กรุณาตรวจสอบช่องที่มีกรอบสีแดง');
                    return;
                }

                if (!res.ok) {
                    throw new Error('save failed: ' + res.status);
                }

                // สำเร็จ: รีโหลดหน้าเพื่อให้รายการแพ็กเกจอัปเดตตามข้อมูลล่าสุด
                window.location.reload();
            } catch (err) {
                console.error(err);
                showToast('error', 'บันทึกไม่สำเร็จ กรุณาลองใหม่');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalLabel;
                }
            }
        });
    })();
</script>
