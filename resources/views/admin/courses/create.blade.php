@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    {{-- กัน text input เป็นสีขาวมองไม่เห็น เวลาเบราว์เซอร์/OS ของผู้ใช้ตั้งเป็น Dark Mode --}}
    <style>
        #courseForm input,
        #courseForm textarea,
        #courseForm select {
            color-scheme: light;
            color: #0f172a; /* slate-900 */
            background-color: #ffffff;
        }
        #courseForm input:-webkit-autofill,
        #courseForm input:-webkit-autofill:hover,
        #courseForm input:-webkit-autofill:focus {
            -webkit-text-fill-color: #0f172a !important;
            box-shadow: 0 0 0px 1000px #ffffff inset !important;
            transition: background-color 5000s ease-in-out 0s;
        }
        #courseForm input::placeholder,
        #courseForm textarea::placeholder {
            color: #94a3b8; /* slate-400 */
        }

        /* ---- Validation styles ---- */
        #courseForm .field-error-msg {
            display: none;
            color: #dc2626; /* red-600 */
            font-size: 0.75rem;
            margin-top: 0.375rem;
        }
        #courseForm .field-error-msg.show {
            display: block;
        }
        #courseForm .input-invalid {
            border-color: #dc2626 !important;
            background-color: #fef2f2 !important;
        }
        #courseForm .schedule-row.row-invalid {
            border-color: #dc2626 !important;
            background-color: #fef2f2 !important;
        }
    </style>

    <div class="flex items-center justify-between mb-8 pb-5 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">เพิ่มคอร์ส / แพ็กเกจใหม่</h1>
            <p class="text-sm text-slate-500 mt-1">สร้างคอร์สและตั้งค่าราคาสำหรับ THATA Basketball Clinic</p>
        </div>
       
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

    <form action="{{ route('admin.courses.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="courseForm" novalidate>
        @csrf

        {{-- ============ 1. ข้อมูลทั่วไปของคลาสเรียน ============ --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center space-x-2 mb-4 pb-3 border-b border-slate-100">
                <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-orange-50 text-orange-600 text-sm font-bold">1</span>
                <h2 class="text-lg font-semibold text-slate-900">ข้อมูลทั่วไปของคลาสเรียน (General Info)</h2>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">ชื่อคลาสเรียน (Course/Class Name)</label>
                <input type="text" id="course_name_input" name="course_name" value="{{ old('course_name') }}"
                       placeholder="เช่น Standard Class, Special Class, Kinder Class"
                       class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm text-slate-900 bg-white">
                <p id="course_name_error" class="field-error-msg">กรุณากรอกชื่อคลาสเรียน</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-3">กลุ่มผู้เรียนเป้าหมาย (Target Groups)</label>
                    <div class="grid grid-cols-2 gap-y-2">
                        @foreach (['Rookie', 'Beginner', 'Junior', 'Player'] as $group)
                            <label class="flex items-center space-x-2 text-sm text-slate-700 cursor-pointer">
                                <input type="checkbox" name="target_groups[]" value="{{ $group }}"
                                       {{ in_array($group, old('target_groups', [])) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                <span>{{ $group }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-3">เกณฑ์อายุผู้เรียน (Age Range)</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" id="min_age_input" name="min_age" min="0" value="{{ old('min_age') }}"
                               placeholder="ขั้นต่ำ"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm text-slate-900 bg-white">
                        <span class="text-sm text-slate-400 flex-shrink-0">ถึง</span>
                        <input type="number" id="max_age_input" name="max_age" min="0" value="{{ old('max_age') }}"
                               placeholder="สูงสุด"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm text-slate-900 bg-white">
                    </div>
                    <p id="age_range_error" class="field-error-msg">กรุณากรอกอายุขั้นต่ำ และอายุสูงสุดต้องมากกว่าหรือเท่ากับอายุขั้นต่ำ</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">รายละเอียดคอร์ส (ไม่บังคับ)</label>
                    <textarea name="description" rows="3"
                              placeholder="รายละเอียดเพิ่มเติมเกี่ยวกับคลาสนี้"
                              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm text-slate-900 bg-white">{{ old('description') }}</textarea>
                </div>

            <div class="md:col-span-2 pt-2">
    <label class="block text-sm font-medium text-slate-700 mb-2">
        ภาพประกอบคอร์ส (ไม่บังคับ, 1 ภาพ)
    </label>

    <div id="dropzone"
         class="relative border-2 border-dashed border-slate-300 rounded-lg text-center cursor-pointer transition hover:border-blue-400 hover:bg-blue-50/40 overflow-hidden"
         onclick="document.getElementById('course_image_input').click()">

        {{-- สถานะยังไม่เลือกไฟล์ --}}
        <div id="dropzone-empty" class="px-6 py-8">
            <svg class="w-8 h-8 mx-auto text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M12 12v9m0-9l-3 3m3-3l3 3"/>
            </svg>
            <p class="text-sm text-slate-500">
                <span class="text-blue-600 font-medium">คลิกเพื่อเลือกภาพ</span> หรือลากไฟล์มาวางที่นี่
            </p>
            <p class="text-xs text-slate-400 mt-1">รองรับ JPG, PNG ขนาดไม่เกิน 2MB</p>
        </div>

        {{-- สถานะเลือกไฟล์แล้ว: โชว์รูป preview --}}
        <div id="dropzone-preview" class="hidden relative">
            <img id="img-preview" class="h-40 w-full object-cover">
            <div class="absolute inset-0 bg-black/0 hover:bg-black/40 transition flex items-center justify-center group">
                <span class="opacity-0 group-hover:opacity-100 text-white text-sm font-medium transition">คลิกเพื่อเปลี่ยนภาพ</span>
            </div>
            <button type="button" id="remove-preview-btn"
                    class="absolute top-2 right-2 bg-white/90 hover:bg-white text-red-600 rounded-full w-7 h-7 flex items-center justify-center shadow"
                    title="ลบภาพที่เลือก">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <input type="file" id="course_image_input" name="image" accept="image/*"
               class="hidden" onchange="handleFileSelect(this)">
    </div>

    <p id="dropzone-filename" class="text-xs text-slate-600 mt-2 hidden">
        เลือกไฟล์: <span class="font-medium"></span>
    </p>
</div>
            </div>
        </div>

        {{-- ============ 2. กำหนดวันเรียนและเวลา ============ --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center space-x-2 mb-4 pb-3 border-b border-slate-100">
                <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-orange-50 text-orange-600 text-sm font-bold">2</span>
                <h2 class="text-lg font-semibold text-slate-900">กำหนดวันเรียนและเวลา (Schedule &amp; Time)</h2>
            </div>

            <label class="block text-sm font-medium text-slate-700 mb-3">ประเภทวันเรียน (Day Type)</label>
            <div class="grid grid-cols-2 gap-4 mb-6" id="dayTypeOptions">
                <label class="day-type-option flex items-center p-3 border-2 border-blue-500 bg-blue-50 rounded-lg cursor-pointer transition">
                    <input type="radio" name="global_day_type" value="weekday" checked
                           class="w-4 h-4 text-blue-600 border-slate-300 focus:ring-blue-500">
                    <div class="ml-3">
                        <span class="block text-sm font-medium text-slate-900">Weekday (วันธรรมดา)</span>
                        <span class="block text-xs text-slate-400">จันทร์, พุธ, ศุกร์</span>
                    </div>
                </label>
                <label class="day-type-option flex items-center p-3 border-2 border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50 transition">
                    <input type="radio" name="global_day_type" value="weekend"
                           class="w-4 h-4 text-blue-600 border-slate-300 focus:ring-blue-500">
                    <div class="ml-3">
                        <span class="block text-sm font-medium text-slate-900">Weekend (วันหยุด)</span>
                        <span class="block text-xs text-slate-400">เสาร์, อาทิตย์</span>
                    </div>
                </label>
            </div>
            <p id="day_type_error" class="field-error-msg">กรุณาเลือกประเภทวันเรียน</p>

            <label class="block text-sm font-medium text-slate-700 mb-3 mt-4">รอบเวลาเรียน (Time Slots)</label>
            <div id="scheduleRows" class="space-y-3 mb-3"></div>
            <p id="schedule_error" class="field-error-msg">กรุณากรอกเวลาให้ครบทุกรอบ เวลาสิ้นสุดต้องมากกว่าเวลาเริ่มต้น และถ้าติ๊ก "จำกัดจำนวน" ต้องกรอกจำนวนคนด้วย</p>

            {{-- แม่แบบ (template) แถวรอบเวลา ใช้ clone ผ่าน JS ไม่ได้ถูกส่งฟอร์มตรงๆ --}}
            <template id="scheduleRowTemplate">
                <div class="schedule-row flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-lg px-4 py-3">
                    <span class="text-sm text-slate-500 flex-shrink-0 w-16 schedule-label">รอบที่ 1:</span>
                    <input type="hidden" class="schedule-day-type" value="weekday"><select class="schedule-court-section w-44 px-2 py-1.5 border border-slate-300 rounded-lg text-sm text-slate-900 bg-white focus:ring-2 focus:ring-blue-500 outline-none"><option value="">เลือกสนาม (ถ้ามี)</option>@foreach($courts as $court)@foreach($court->allSectionsOrdered() as $section)<option value="{{ $section->id }}">{{ $court->name }} — {{ $section->name }}</option>@endforeach
@endforeach</select>
                    <input type="time" class="schedule-start w-28 px-2 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none text-slate-900 bg-white" value="16:00" required>
                    <span class="text-slate-400">-</span>
                    <input type="time" class="schedule-end w-28 px-2 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none text-slate-900 bg-white" value="17:30" required>
                    <span class="schedule-duration text-xs bg-blue-100 text-blue-700 font-medium px-2 py-1 rounded-full whitespace-nowrap">0.00 ชั่วโมง</span>
                    <label class="flex items-center space-x-1.5 text-xs text-slate-600 cursor-pointer ml-auto flex-shrink-0">
                        <input type="checkbox" class="schedule-limited w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                        <span>จำกัดจำนวน (Limited Spots)</span>
                    </label>
                    <div class="schedule-capacity-wrap hidden items-center gap-1 flex-shrink-0">
                        <input type="number" min="1" placeholder="จำนวนคน"
                               class="schedule-capacity w-24 px-2 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none text-slate-900 bg-white">
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

            {{-- ค่าจริงที่ถูกส่งไปกับฟอร์ม ถูกซิงค์จาก JS ก่อน submit --}}
            <div id="scheduleHiddenInputs"></div>
        </div>

        {{-- ============ 3. การตั้งค่าแพ็กเกจและราคา ============ --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center space-x-2 mb-4 pb-3 border-b border-slate-100">
                <span class="flex items-center justify-center w-7 h-7 rounded-lg  bg-orange-50 text-orange-600 text-sm font-bold">3</span>
                <h2 class="text-lg font-semibold text-slate-900">การตั้งค่าแพ็กเกจและราคา (Packages &amp; Pricing)</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">ประเภทคอร์ส (Package Type)</label>
                    <select id="package_type_input" name="package_type" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm text-slate-900 bg-white">
                        <option value="group" {{ old('package_type', 'group') === 'group' ? 'selected' : '' }}>Standard Group Class (กลุ่มเรียนรวม)</option>
                        <option value="private" {{ old('package_type') === 'private' ? 'selected' : '' }}>Private Class (ส่วนตัว)</option>
                    </select>
                    <p id="package_type_error" class="field-error-msg">กรุณาเลือกประเภทคอร์ส</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">จำนวนครั้งในแพ็กเกจ</label>
                    <div class="relative">
                        <input type="number" id="total_sessions" name="total_sessions" min="1"
                               value="{{ old('total_sessions', 1) }}" placeholder="เช่น 4, 8, 15"
                               class="w-full pl-3 pr-12 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm text-slate-900 bg-white">
                        <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-medium">ครั้ง</span>
                    </div>
                    <p id="total_sessions_error" class="field-error-msg">กรุณากรอกจำนวนครั้ง (ตัวเลขจำนวนเต็ม ตั้งแต่ 1 ขึ้นไป)</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">ราคาแพ็กเกจเต็ม (THB)</label>
                    <div class="relative">
                        <input type="number" id="total_price" name="total_price" min="0" step="0.01"
                               value="{{ old('total_price') }}" placeholder="เช่น 2000"
                               class="w-full pl-3 pr-12 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm text-slate-900 bg-white">
                        <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-medium">บาท</span>
                    </div>
                    <p class="text-xs text-green-600 font-medium mt-1.5">
                        💡 เฉลี่ยครั้งละ: <span id="avgPriceLabel">0</span> บาท
                    </p>
                    <p id="total_price_error" class="field-error-msg">กรุณากรอกราคาแพ็กเกจ (ตัวเลขตั้งแต่ 0 ขึ้นไป)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">อายุแพ็กเกจ (Validity)</label>
                    <div class="flex space-x-2">
                        <input type="number" id="validity_value_input" name="validity_value" min="1"
                               value="{{ old('validity_value') }}" placeholder="เช่น 60"
                               class="w-3/5 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm text-slate-900 bg-white">
                        <select id="validity_unit_input" name="validity_unit" class="w-2/5 px-2 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm text-slate-900 bg-white">
                            <option value="days" {{ old('validity_unit', 'days') === 'days' ? 'selected' : '' }}>วัน (Days)</option>
                            <option value="hours" {{ old('validity_unit') === 'hours' ? 'selected' : '' }}>ชั่วโมง (Hours)</option>
                        </select>
                    </div>
                    <p id="validity_error" class="field-error-msg">กรุณากรอกอายุแพ็กเกจ (ตัวเลขจำนวนเต็ม ตั้งแต่ 1 ขึ้นไป)</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">คำแนะนำการใช้งาน (Recommendation Text)</label>
                    <input type="text" name="recommendation_text" value="{{ old('recommendation_text') }}"
                           placeholder="เช่น แนะนำสำหรับ : เรียน 1 ครั้ง/สัปดาห์"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm text-slate-900 bg-white">
                    {{-- ไม่บังคับ / ไม่ต้อง Validate --}}
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
                บันทึกคอร์ส
            </button>
        </div>
    </form>
</div>

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
        // อัปเดต day_type ของทุกแถวเวลาเรียนให้ตรงกับตัวเลือกด้านบน
        const selected = document.querySelector('#dayTypeOptions input[name="global_day_type"]:checked').value;
        document.querySelectorAll('.schedule-day-type').forEach(el => el.value = selected);
        clearFieldError('day_type_error');
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
        } else {
            capacityWrap.classList.add('hidden');
            capacityWrap.classList.remove('flex');
            capacityInput.value = '';
            capacityInput.classList.remove('input-invalid');
        }
    }

    function addScheduleRow() {
        const clone = rowTemplate.content.firstElementChild.cloneNode(true);
        clone.querySelector('.schedule-day-type').value = globalDayType();
        clone.querySelector('.schedule-start').addEventListener('input', () => { calcDuration(clone); clearFieldError('schedule_error'); clone.classList.remove('row-invalid'); });
        clone.querySelector('.schedule-end').addEventListener('input', () => { calcDuration(clone); clearFieldError('schedule_error'); clone.classList.remove('row-invalid'); });
        clone.querySelector('.schedule-limited').addEventListener('change', () => { toggleCapacityField(clone); clearFieldError('schedule_error'); clone.classList.remove('row-invalid'); });
        clone.querySelector('.schedule-capacity').addEventListener('input', () => { clearFieldError('schedule_error'); clone.classList.remove('row-invalid'); clone.querySelector('.schedule-capacity').classList.remove('input-invalid'); });
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

    document.getElementById('addScheduleRow').addEventListener('click', addScheduleRow);
    addScheduleRow(); // เริ่มต้นด้วย 1 รอบเวลา

    // ---------- 4) Validation helpers ----------
    function showFieldError(inputEl, errorId) {
        if (inputEl) inputEl.classList.add('input-invalid');
        const errEl = document.getElementById(errorId);
        if (errEl) errEl.classList.add('show');
    }
    function clearFieldError(errorId, inputEl) {
        if (inputEl) inputEl.classList.remove('input-invalid');
        const errEl = document.getElementById(errorId);
        if (errEl) errEl.classList.remove('show');
    }
    function clearAllErrors() {
        document.querySelectorAll('#courseForm .field-error-msg').forEach(el => el.classList.remove('show'));
        document.querySelectorAll('#courseForm .input-invalid').forEach(el => el.classList.remove('input-invalid'));
        document.querySelectorAll('#courseForm .schedule-row').forEach(el => el.classList.remove('row-invalid'));
    }

    function validateForm() {
        clearAllErrors();
        let isValid = true;
        let firstInvalidEl = null;

        // ชื่อคลาสเรียน
        const courseNameInput = document.getElementById('course_name_input');
        if (!courseNameInput.value.trim()) {
            showFieldError(courseNameInput, 'course_name_error');
            firstInvalidEl = firstInvalidEl || courseNameInput;
            isValid = false;
        }

        // เกณฑ์อายุผู้เรียน
        const minAgeInput = document.getElementById('min_age_input');
        const maxAgeInput = document.getElementById('max_age_input');
        const minAgeVal = minAgeInput.value.trim();
        const maxAgeVal = maxAgeInput.value.trim();
        let ageInvalid = false;
        if (minAgeVal === '' || isNaN(minAgeVal) || Number(minAgeVal) < 0) {
            ageInvalid = true;
        }
        if (maxAgeVal !== '' && (isNaN(maxAgeVal) || Number(maxAgeVal) < Number(minAgeVal || 0))) {
            ageInvalid = true;
        }
        if (ageInvalid) {
            showFieldError(minAgeInput, 'age_range_error');
            maxAgeInput.classList.add('input-invalid');
            firstInvalidEl = firstInvalidEl || minAgeInput;
            isValid = false;
        }

        // ประเภทวันเรียน (Day Type) - ต้องมีตัวเลือกถูกเลือกอยู่เสมอ (radio มีค่า default) แต่ตรวจซ้ำเผื่อกรณีถูกแก้ไข DOM
        const dayTypeChecked = document.querySelector('#dayTypeOptions input[name="global_day_type"]:checked');
        if (!dayTypeChecked) {
            document.getElementById('day_type_error').classList.add('show');
            firstInvalidEl = firstInvalidEl || document.getElementById('dayTypeOptions');
            isValid = false;
        }

        // รอบเวลาเรียน: ต้องกรอกเวลาเริ่ม-สิ้นสุดครบ และเวลาสิ้นสุด > เวลาเริ่มต้น ทุกแถว
        let scheduleInvalid = false;
        const rows = scheduleRows.querySelectorAll('.schedule-row');
        if (rows.length === 0) {
            scheduleInvalid = true;
        }
        rows.forEach(row => {
            const start = row.querySelector('.schedule-start').value;
            const end = row.querySelector('.schedule-end').value;
            if (!start || !end || start >= end) {
                row.classList.add('row-invalid');
                scheduleInvalid = true;
            }

            // ถ้าติ๊ก "จำกัดจำนวน" ต้องกรอกจำนวนคน (ตัวเลขจำนวนเต็ม >= 1)
            const isLimited = row.querySelector('.schedule-limited').checked;
            const capacityInput = row.querySelector('.schedule-capacity');
            if (isLimited) {
                const capVal = capacityInput.value.trim();
                if (capVal === '' || isNaN(capVal) || !Number.isInteger(Number(capVal)) || Number(capVal) < 1) {
                    capacityInput.classList.add('input-invalid');
                    row.classList.add('row-invalid');
                    scheduleInvalid = true;
                }
            }
        });
        if (scheduleInvalid) {
            document.getElementById('schedule_error').classList.add('show');
            firstInvalidEl = firstInvalidEl || scheduleRows;
            isValid = false;
        }

        // ประเภทคอร์ส (Package Type)
        const packageTypeInput = document.getElementById('package_type_input');
        if (!packageTypeInput.value) {
            showFieldError(packageTypeInput, 'package_type_error');
            firstInvalidEl = firstInvalidEl || packageTypeInput;
            isValid = false;
        }

        // จำนวนครั้งในแพ็กเกจ
        const sessionsVal = sessionInput.value.trim();
        if (sessionsVal === '' || isNaN(sessionsVal) || !Number.isInteger(Number(sessionsVal)) || Number(sessionsVal) < 1) {
            showFieldError(sessionInput, 'total_sessions_error');
            firstInvalidEl = firstInvalidEl || sessionInput;
            isValid = false;
        }

        // ราคาแพ็กเกจเต็ม
        const priceVal = priceInput.value.trim();
        if (priceVal === '' || isNaN(priceVal) || Number(priceVal) < 0) {
            showFieldError(priceInput, 'total_price_error');
            firstInvalidEl = firstInvalidEl || priceInput;
            isValid = false;
        }

        // อายุแพ็กเกจ (Validity)
        const validityValueInput = document.getElementById('validity_value_input');
        const validityVal = validityValueInput.value.trim();
        if (validityVal === '' || isNaN(validityVal) || !Number.isInteger(Number(validityVal)) || Number(validityVal) < 1) {
            showFieldError(validityValueInput, 'validity_error');
            firstInvalidEl = firstInvalidEl || validityValueInput;
            isValid = false;
        }

        // คำแนะนำการใช้งาน (Recommendation Text) -> ไม่ต้อง Validate (ข้ามช่องนี้โดยตั้งใจ)

        if (!isValid && firstInvalidEl) {
            firstInvalidEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (typeof firstInvalidEl.focus === 'function') firstInvalidEl.focus();
        }

        return isValid;
    }

    // เคลียร์สถานะ error ทันทีที่ผู้ใช้แก้ไขข้อมูล
    document.getElementById('course_name_input').addEventListener('input', function () {
        clearFieldError('course_name_error', this);
    });
    ['min_age_input', 'max_age_input'].forEach(id => {
        document.getElementById(id).addEventListener('input', function () {
            clearFieldError('age_range_error');
            document.getElementById('min_age_input').classList.remove('input-invalid');
            document.getElementById('max_age_input').classList.remove('input-invalid');
        });
    });
    document.getElementById('package_type_input').addEventListener('change', function () {
        clearFieldError('package_type_error', this);
    });
    sessionInput.addEventListener('input', function () {
        clearFieldError('total_sessions_error', this);
    });
    priceInput.addEventListener('input', function () {
        clearFieldError('total_price_error', this);
    });
    document.getElementById('validity_value_input').addEventListener('input', function () {
        clearFieldError('validity_error', this);
    });

    // ---------- 5) ก่อน submit: ตรวจสอบความถูกต้อง + สร้าง hidden input schedules[i][...] จากแถวที่กรอกไว้ ----------
    document.getElementById('courseForm').addEventListener('submit', function (e) {
        if (!validateForm()) {
            e.preventDefault();
            return;
        }

        const container = document.getElementById('scheduleHiddenInputs');
        container.innerHTML = '';
        scheduleRows.querySelectorAll('.schedule-row').forEach((row, idx) => {
            const isLimited = row.querySelector('.schedule-limited').checked;
            const fields = {
                day_type: row.querySelector('.schedule-day-type').value,
                court_section_id: row.querySelector('.schedule-court-section').value,
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
    const dropzone = document.getElementById('dropzone');
    const dropzoneEmpty = document.getElementById('dropzone-empty');
    const dropzonePreview = document.getElementById('dropzone-preview');
    const dropzoneFilename = document.getElementById('dropzone-filename');
    const imgPreview = document.getElementById('img-preview');
    const fileInput = document.getElementById('course_image_input');

    function showSelectedFile(file) {
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            imgPreview.src = e.target.result;
            dropzoneEmpty.classList.add('hidden');
            dropzonePreview.classList.remove('hidden');
            dropzone.classList.remove('border-dashed', 'border-slate-300');
            dropzone.classList.add('border-solid', 'border-green-400');
        };
        reader.readAsDataURL(file);

        dropzoneFilename.querySelector('span').textContent = file.name;
        dropzoneFilename.classList.remove('hidden');
    }

    function clearSelectedFile() {
        fileInput.value = '';
        imgPreview.src = '';
        dropzoneEmpty.classList.remove('hidden');
        dropzonePreview.classList.add('hidden');
        dropzoneFilename.classList.add('hidden');
        dropzone.classList.remove('border-solid', 'border-green-400');
        dropzone.classList.add('border-dashed', 'border-slate-300');
    }

    function handleFileSelect(input) {
        if (input.files && input.files[0]) {
            showSelectedFile(input.files[0]);
        }
    }

    document.getElementById('remove-preview-btn').addEventListener('click', function (e) {
        e.stopPropagation(); // กันไม่ให้ trigger click เปิด file picker ซ้อน
        clearSelectedFile();
    });

    // ---------- Drag & drop ----------
    ['dragenter', 'dragover'].forEach(evt => {
        dropzone.addEventListener(evt, function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('border-blue-400', 'bg-blue-50/40');
        });
    });

    ['dragleave', 'drop'].forEach(evt => {
        dropzone.addEventListener(evt, function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('border-blue-400', 'bg-blue-50/40');
        });
    });

    dropzone.addEventListener('drop', function (e) {
        const file = e.dataTransfer.files[0];
        if (!file) return;

        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;

        showSelectedFile(file);
    });
</script>
<style>
    /* รอบเรียนมีตัวเลือกสนามเพิ่มขึ้น: ให้ตัดบรรทัดแทนการล้นออกนอกการ์ด */
    .schedule-row { flex-wrap: wrap; align-items: center; }
    .schedule-row .schedule-limited { margin-left: 0 !important; }
    .schedule-row .schedule-capacity-wrap { margin-left: 0; }
    @media (max-width: 640px) {
        .schedule-row > .schedule-label { width: 100%; }
        .schedule-row .schedule-court-section { width: 100%; }
    }
</style>
@endsection
