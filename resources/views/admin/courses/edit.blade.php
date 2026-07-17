@extends('layouts.app')

@section('content')
@php
    $package = $course->packages->first();
@endphp
<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    <div class="flex items-center justify-between mb-8 pb-5 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">แก้ไขคอร์ส / แพ็กเกจ</h1>
            <p class="text-sm text-slate-500 mt-1">แก้ไขข้อมูลคอร์ส "{{ $course->course_name }}"</p>
        </div>
        <span class="bg-blue-50 text-blue-600 text-xs font-semibold px-3 py-1 rounded-full border border-blue-100">
            TTBC Admin Portal
        </span>
    </div>

    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-4">
            <p class="font-semibold mb-1">กรอกข้อมูลไม่ครบหรือไม่ถูกต้อง:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.courses.update', $course) }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="courseForm">
        @csrf
        @method('PUT')

        {{-- ============ 1. ข้อมูลทั่วไปของคลาสเรียน ============ --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center space-x-2 mb-4 pb-3 border-b border-slate-100">
                <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-blue-50 text-blue-600 text-sm font-bold">1</span>
                <h2 class="text-lg font-semibold text-slate-900">ข้อมูลทั่วไปของคลาสเรียน (General Info)</h2>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">ชื่อคลาสเรียน (Course/Class Name)</label>
                <input type="text" name="course_name" required value="{{ old('course_name', $course->course_name) }}"
                       placeholder="เช่น Standard Class, Special Class, Kinder Class"
                       class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm">
            </div>

            @php $selectedGroups = old('target_groups', $course->targetGroups->pluck('target_group')->toArray()); @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-3">กลุ่มผู้เรียนเป้าหมาย (Target Groups)</label>
                    <div class="grid grid-cols-2 gap-y-2">
                        @foreach (['Rookie', 'Beginner', 'Junior', 'Player'] as $group)
                            <label class="flex items-center space-x-2 text-sm text-slate-700 cursor-pointer">
                                <input type="checkbox" name="target_groups[]" value="{{ $group }}"
                                       {{ in_array($group, $selectedGroups) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                <span>{{ $group }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-3">เกณฑ์อายุผู้เรียน (Age Range)</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" name="min_age" min="0" required value="{{ old('min_age', $course->min_age) }}"
                               placeholder="ขั้นต่ำ"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                        <span class="text-sm text-slate-400 flex-shrink-0">ถึง</span>
                        <input type="number" name="max_age" min="0" value="{{ old('max_age', $course->max_age) }}"
                               placeholder="สูงสุด"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    </div>
                    <p class="text-xs text-slate-400 mt-1.5">*ถ้าไม่มีเกณฑ์อายุสูงสุด ให้ปล่อยเว้นว่างไว้ (เช่น 6 ปีขึ้นไป)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">รายละเอียดคอร์ส (ไม่บังคับ)</label>
                    <textarea name="description" rows="3"
                              placeholder="รายละเอียดเพิ่มเติมเกี่ยวกับคลาสนี้"
                              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm">{{ old('description', $course->description) }}</textarea>
                </div>
            <div class="md:col-span-2 pt-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    ภาพประกอบคอร์ส (ไม่บังคับ, 1 ภาพ)
                </label>

                <div id="img-preview-wrap">
                    <img id="img-preview"
                        src="{{ $course->image_url ?: 'https://images.unsplash.com/photo-1546519638-68e109498ffc?q=80&w=800&auto=format&fit=crop' }}"
                        class="h-40 w-full rounded-lg object-cover border-2 border-slate-200 shadow-sm">
                    <p id="img-preview-caption" class="text-xs text-center text-slate-400 mt-1">
                        {{ $course->image_url ? 'รูปปัจจุบัน' : 'ยังไม่มีภาพประกอบ' }}
                    </p>

                    <div class="flex items-start gap-4 mt-3">
                        <div class="flex-1">
                            <input type="file" id="course_image_input" name="image" accept="image/*"
                                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100 cursor-pointer"
                                onchange="previewImg(this)">
                            <p class="text-xs text-slate-400 mt-1.5">อัปโหลดภาพใหม่เพื่อแทนที่ภาพเดิม (JPG, PNG ไม่เกิน 2MB)</p>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>

        {{-- ============ 2. กำหนดวันเรียนและเวลา ============ --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center space-x-2 mb-4 pb-3 border-b border-slate-100">
                <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-blue-50 text-blue-600 text-sm font-bold">2</span>
                <h2 class="text-lg font-semibold text-slate-900">กำหนดวันเรียนและเวลา (Schedule &amp; Time)</h2>
            </div>

            @php $defaultDayType = old('global_day_type', optional($course->schedules->first())->day_type ?? 'weekday'); @endphp
            <label class="block text-sm font-medium text-slate-700 mb-3">ประเภทวันเรียน (Day Type)</label>
            <div class="grid grid-cols-2 gap-4 mb-6" id="dayTypeOptions">
                <label class="day-type-option flex items-center p-3 border-2 {{ $defaultDayType === 'weekday' ? 'border-blue-500 bg-blue-50' : 'border-slate-200' }} rounded-lg cursor-pointer hover:bg-slate-50 transition">
                    <input type="radio" name="global_day_type" value="weekday" {{ $defaultDayType === 'weekday' ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 border-slate-300 focus:ring-blue-500">
                    <div class="ml-3">
                        <span class="block text-sm font-medium text-slate-900">Weekday (วันธรรมดา)</span>
                        <span class="block text-xs text-slate-400">จันทร์, พุธ, ศุกร์</span>
                    </div>
                </label>
                <label class="day-type-option flex items-center p-3 border-2 {{ $defaultDayType === 'weekend' ? 'border-blue-500 bg-blue-50' : 'border-slate-200' }} rounded-lg cursor-pointer hover:bg-slate-50 transition">
                    <input type="radio" name="global_day_type" value="weekend" {{ $defaultDayType === 'weekend' ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 border-slate-300 focus:ring-blue-500">
                    <div class="ml-3">
                        <span class="block text-sm font-medium text-slate-900">Weekend (วันหยุด)</span>
                        <span class="block text-xs text-slate-400">เสาร์, อาทิตย์</span>
                    </div>
                </label>
            </div>

            <label class="block text-sm font-medium text-slate-700 mb-3">รอบเวลาเรียน (Time Slots)</label>
            <div id="scheduleRows" class="space-y-3 mb-3"></div>

            <template id="scheduleRowTemplate">
                <div class="schedule-row flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-lg px-4 py-3">
                    <span class="text-sm text-slate-500 flex-shrink-0 w-16 schedule-label">รอบที่ 1:</span>
                    <input type="hidden" class="schedule-day-type" value="weekday">
                    <input type="time" class="schedule-start w-28 px-2 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" value="16:00" required>
                    <span class="text-slate-400">-</span>
                    <input type="time" class="schedule-end w-28 px-2 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" value="17:30" required>
                    <span class="schedule-duration text-xs bg-blue-100 text-blue-700 font-medium px-2 py-1 rounded-full whitespace-nowrap">0.00 ชั่วโมง</span>
                    <label class="flex items-center space-x-1.5 text-xs text-slate-600 cursor-pointer ml-auto flex-shrink-0">
                        <input type="checkbox" class="schedule-limited w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                        <span>จำกัดจำนวน (Limited Spots)</span>
                    </label>
                    <div class="schedule-capacity-wrap hidden items-center gap-1 flex-shrink-0">
                        <input type="number" min="1" placeholder="จำนวนคน"
                               class="schedule-capacity w-24 px-2 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        <span class="text-xs text-slate-400 whitespace-nowrap">คน</span>
                    </div>
                    <button type="button" class="remove-schedule-row text-slate-400 hover:text-red-500 flex-shrink-0" title="ลบรอบนี้">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </template>

            <button type="button" id="addScheduleRow" class="text-sm font-medium text-blue-600 hover:text-blue-700 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                เพิ่มรอบเวลาเรียน
            </button>

            <div id="scheduleHiddenInputs"></div>
        </div>

        {{-- ============ 3. การตั้งค่าแพ็กเกจและราคา ============ --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center space-x-2 mb-4 pb-3 border-b border-slate-100">
                <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-blue-50 text-blue-600 text-sm font-bold">3</span>
                <h2 class="text-lg font-semibold text-slate-900">การตั้งค่าแพ็กเกจและราคา (Packages &amp; Pricing)</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">ประเภทคอร์ส (Package Type)</label>
                    <select name="package_type" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                        <option value="group" {{ old('package_type', optional($package)->package_type ?? 'group') === 'group' ? 'selected' : '' }}>Standard Group Class (กลุ่มเรียนรวม)</option>
                        <option value="private" {{ old('package_type', optional($package)->package_type) === 'private' ? 'selected' : '' }}>Private Class (ส่วนตัว)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">จำนวนครั้งในแพ็กเกจ</label>
                    <div class="relative">
                        <input type="number" id="total_sessions" name="total_sessions" min="1" required
                               value="{{ old('total_sessions', optional($package)->total_sessions ?? 1) }}" placeholder="เช่น 4, 8, 15"
                               class="w-full pl-3 pr-12 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                        <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-medium">ครั้ง</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">ราคาแพ็กเกจเต็ม (THB)</label>
                    <div class="relative">
                        <input type="number" id="total_price" name="total_price" min="0" step="0.01" required
                               value="{{ old('total_price', optional($package)->total_price) }}" placeholder="เช่น 2000"
                               class="w-full pl-3 pr-12 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                        <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-medium">บาท</span>
                    </div>
                    <p class="text-xs text-green-600 font-medium mt-1.5">
                        💡 เฉลี่ยครั้งละ: <span id="avgPriceLabel">0</span> บาท
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">อายุแพ็กเกจ (Validity)</label>
                    <div class="flex space-x-2">
                        <input type="number" name="validity_value" min="1" required
                               value="{{ old('validity_value', optional($package)->validity_value) }}" placeholder="เช่น 60"
                               class="w-3/5 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                        <select name="validity_unit" class="w-2/5 px-2 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                            <option value="days" {{ old('validity_unit', optional($package)->validity_unit ?? 'days') === 'days' ? 'selected' : '' }}>วัน (Days)</option>
                            <option value="hours" {{ old('validity_unit', optional($package)->validity_unit) === 'hours' ? 'selected' : '' }}>ชั่วโมง (Hours)</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">คำแนะนำการใช้งาน (Recommendation Text)</label>
                    <input type="text" name="recommendation_text" value="{{ old('recommendation_text', optional($package)->recommendation_text) }}"
                           placeholder="เช่น แนะนำสำหรับ : เรียน 1 ครั้ง/สัปดาห์"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-4 pt-4 border-t border-slate-200">
            <a href="{{ route('admin.courses') }}"
               class="px-5 py-2.5 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100 transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 shadow-sm transition">
                บันทึกการแก้ไข
            </button>
        </div>
    </form>
</div>

{{-- ข้อมูลรอบเวลาเดิมของคอร์สนี้ ส่งเข้า JS เพื่อ pre-fill แถวเวลาเรียน (รวม capacity) --}}
@php
    $existingSchedules = $course->schedules->map(function ($s) {
        return [
            'day_type' => $s->day_type,
            'start_time' => \Illuminate\Support\Carbon::parse($s->start_time)->format('H:i'),
            'end_time' => \Illuminate\Support\Carbon::parse($s->end_time)->format('H:i'),
            'is_limited_spots' => $s->is_limited_spots,
            'capacity' => $s->capacity,
        ];
    });
@endphp
<script>
    window.__existingSchedules = @json($existingSchedules);
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ---------- 1) เฉลี่ยราคาต่อครั้ง ----------
    const sessionInput = document.getElementById('total_sessions');
    const priceInput = document.getElementById('total_price');
    const avgLabel = document.getElementById('avgPriceLabel');

    function calcAverage() {
        const sessions = parseInt(sessionInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        avgLabel.textContent = sessions > 0 ? Math.round(price / sessions).toLocaleString() : '0';
    }
    sessionInput.addEventListener('input', calcAverage);
    priceInput.addEventListener('input', calcAverage);
    calcAverage();

    // ---------- 2) ไฮไลต์การ์ด weekday/weekend ที่เลือกอยู่ ----------
    const dayOptions = document.querySelectorAll('#dayTypeOptions .day-type-option');
    function refreshDayOptionStyles() {
        dayOptions.forEach(function (label) {
            const input = label.querySelector('input[type="radio"]');
            label.classList.remove('border-blue-500', 'bg-blue-50', 'border-slate-200');
            label.classList.add(input.checked ? 'border-blue-500' : 'border-slate-200');
            if (input.checked) label.classList.add('bg-blue-50');
        });
        const selected = document.querySelector('#dayTypeOptions input[name="global_day_type"]:checked').value;
        document.querySelectorAll('.schedule-day-type').forEach(el => el.value = selected);
    }
    dayOptions.forEach(label => label.querySelector('input').addEventListener('change', refreshDayOptionStyles));
    refreshDayOptionStyles();

    // ---------- 3) รอบเวลาเรียนแบบเพิ่ม/ลบได้ ----------
    const scheduleRows = document.getElementById('scheduleRows');
    const rowTemplate = document.getElementById('scheduleRowTemplate');
    const globalDayType = () => document.querySelector('#dayTypeOptions input[name="global_day_type"]:checked').value;

    function calcDuration(row) {
        const start = row.querySelector('.schedule-start').value;
        const end = row.querySelector('.schedule-end').value;
        const label = row.querySelector('.schedule-duration');
        if (!start || !end) { label.textContent = '0.00 ชั่วโมง'; return; }
        const [sh, sm] = start.split(':').map(Number);
        const [eh, em] = end.split(':').map(Number);
        let minutes = (eh * 60 + em) - (sh * 60 + sm);
        if (minutes < 0) minutes = 0;
        const h = Math.floor(minutes / 60);
        const m = minutes % 60;
        label.textContent = h + '.' + String(m).padStart(2, '0') + ' ชั่วโมง';
    }

    function renumberRows() {
        scheduleRows.querySelectorAll('.schedule-row').forEach((row, idx) => {
            row.querySelector('.schedule-label').textContent = 'รอบที่ ' + (idx + 1) + ':';
        });
    }

    function toggleCapacityField(row) {
        const limitedCheckbox = row.querySelector('.schedule-limited');
        const capacityWrap = row.querySelector('.schedule-capacity-wrap');
        const capacityInput = row.querySelector('.schedule-capacity');
        if (limitedCheckbox.checked) {
            capacityWrap.classList.remove('hidden');
            capacityWrap.classList.add('flex');
            capacityInput.setAttribute('required', 'required');
        } else {
            capacityWrap.classList.add('hidden');
            capacityWrap.classList.remove('flex');
            capacityInput.removeAttribute('required');
            capacityInput.value = '';
        }
    }

    function addScheduleRow(prefill) {
        const clone = rowTemplate.content.firstElementChild.cloneNode(true);
        clone.querySelector('.schedule-day-type').value = prefill ? prefill.day_type : globalDayType();
        clone.querySelector('.schedule-start').value = prefill ? prefill.start_time : '16:00';
        clone.querySelector('.schedule-end').value = prefill ? prefill.end_time : '17:30';
        clone.querySelector('.schedule-limited').checked = prefill ? !!prefill.is_limited_spots : false;
        clone.querySelector('.schedule-capacity').value = (prefill && prefill.capacity) ? prefill.capacity : '';
        clone.querySelector('.schedule-start').addEventListener('input', () => calcDuration(clone));
        clone.querySelector('.schedule-end').addEventListener('input', () => calcDuration(clone));
        clone.querySelector('.schedule-limited').addEventListener('change', () => toggleCapacityField(clone));
        clone.querySelector('.remove-schedule-row').addEventListener('click', () => {
            if (scheduleRows.querySelectorAll('.schedule-row').length > 1) {
                clone.remove();
                renumberRows();
            }
        });
        scheduleRows.appendChild(clone);
        calcDuration(clone);
        toggleCapacityField(clone);
        renumberRows();
    }

    document.getElementById('addScheduleRow').addEventListener('click', () => addScheduleRow(null));

    // เติมแถวรอบเวลาจากข้อมูลเดิมของคอร์ส ถ้าไม่มีเลยให้เริ่มด้วย 1 แถวว่าง
    const existing = window.__existingSchedules || [];
    if (existing.length > 0) {
        existing.forEach(s => addScheduleRow(s));
    } else {
        addScheduleRow(null);
    }

    // ---------- 4) ก่อน submit: สร้าง hidden input schedules[i][...] จากแถวที่กรอกไว้ ----------
    document.getElementById('courseForm').addEventListener('submit', function () {
        const container = document.getElementById('scheduleHiddenInputs');
        container.innerHTML = '';
        scheduleRows.querySelectorAll('.schedule-row').forEach((row, idx) => {
            const isLimited = row.querySelector('.schedule-limited').checked;
            const fields = {
                day_type: row.querySelector('.schedule-day-type').value,
                start_time: row.querySelector('.schedule-start').value,
                end_time: row.querySelector('.schedule-end').value,
                is_limited_spots: isLimited ? '1' : '0',
                capacity: isLimited ? row.querySelector('.schedule-capacity').value : '',
            };
            Object.entries(fields).forEach(([key, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `schedules[${idx}][${key}]`;
                input.value = value;
                container.appendChild(input);
            });
        });
    });

    
});
</script>
<script>
    const ORIGINAL_COURSE_IMAGE = @json($course->image_url);

    function previewImg(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('img-preview').src = e.target.result;
                document.getElementById('img-preview-caption').textContent = 'ภาพที่เลือกใหม่';
            
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function handleRemoveImageToggle(checkbox) {
        const preview = document.getElementById('img-preview');
        const caption = document.getElementById('img-preview-caption');
        if (checkbox.checked) {
            preview.src = PLACEHOLDER_IMAGE;
            caption.textContent = 'ภาพจะถูกลบเมื่อบันทึก';
            document.getElementById('course_image_input').value = '';
        } else {
            preview.src = ORIGINAL_COURSE_IMAGE || PLACEHOLDER_IMAGE;
            caption.textContent = ORIGINAL_COURSE_IMAGE ? 'รูปปัจจุบัน' : 'ยังไม่มีภาพประกอบ';
        }
    }
</script>
<style>
    input, select, textarea {
    color: #0f172a; 
}
</style>
@endsection