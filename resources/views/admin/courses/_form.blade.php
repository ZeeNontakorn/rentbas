@php($isEdit = isset($course))

<form action="{{ $isEdit ? route('admin.courses.update', $course) : route('admin.courses.store') }}" method="POST"
    enctype="multipart/form-data" id="courseForm" class="space-y-6" novalidate>
    @csrf
    @if($isEdit) @method('PUT') @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="mb-1 font-semibold">กรอกข้อมูลไม่ครบหรือไม่ถูกต้อง:</p>
            <ul class="list-inside list-disc space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-center gap-2 border-b border-slate-100 pb-3"><b
                class="grid h-7 w-7 place-items-center rounded-lg bg-orange-50 text-sm text-orange-600">1</b>
            <h2 class="text-lg font-semibold text-slate-900">ข้อมูลทั่วไปของคลาสเรียน</h2>
        </div>
        <div class="mb-6">
            <label class="mb-2 block text-sm font-medium text-slate-700">ชื่อคลาสเรียน
                <span class="text-xs text-red-500"> *</span>
            </label>
            <input id="course_name_input" name="course_name" value="{{ old('course_name', $isEdit ? $course->course_name : '') }}"
                maxlength="255"
                class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-900 bg-white placeholder:text-slate-400 [color-scheme:light] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="เช่น Standard Class">
            <p id="course_name_error" class="hidden mt-1.5 text-xs text-red-600">กรุณากรอกชื่อคลาสเรียน</p>
        </div>
        <div class="grid gap-6 md:grid-cols-2">
            <div><label class="mb-3 block text-sm font-medium text-slate-700">กลุ่มผู้เรียนเป้าหมาย
                <span class="text-xs text-red-500"> *</span>
            </label>
                <div class="grid grid-cols-2 gap-2">@foreach(['Rookie','Beginner','Junior','Player'] as $group)<label
                        class="flex cursor-pointer items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="target_groups[]"
                            class="target_group h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            value="{{ $group }}" @checked(in_array($group, old('target_groups', $isEdit ?
                            $course->targetGroups->pluck('target_group')->all() : [])))>{{ $group }}</label>@endforeach
                </div>
                <p id="target_groups_error" class="hidden mt-1.5 text-xs text-red-600">กรุณาเลือกกลุ่มเป้าหมายอย่างน้อย 1 กลุ่ม</p>
            </div>
            <div>
                <label class="mb-3 block text-sm font-medium text-slate-700">เกณฑ์อายุผู้เรียน
                    <span class="text-xs text-red-500"> *</span>
                </label>
                <div class="flex items-center gap-2">
                    <input id="min_age_input" type="number" name="min_age" min="0"
                        value="{{ old('min_age', $isEdit ? $course->min_age : '') }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white placeholder:text-slate-400 [color-scheme:light] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="ขั้นต่ำ"><span
                        class="flex-shrink-0 text-slate-400">ถึง</span><input id="max_age_input" type="number" name="max_age" min="0"
                        value="{{ old('max_age', $isEdit ? $course->max_age : '') }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white placeholder:text-slate-400 [color-scheme:light] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="สูงสุด">
                </div>
                <p id="min_age_error" class="hidden mt-1.5 text-xs text-red-600">กรุณากรอกอายุขั้นต่ำ</p>
                <p id="max_age_error" class="hidden mt-1.5 text-xs text-red-600">อายุสูงสุดต้องมากกว่าหรือเท่ากับอายุขั้นต่ำ</p>
            </div>
        </div>
        <div class="mt-6"><label class="mb-2 block text-sm font-medium text-slate-700">รายละเอียดคอร์ส
                (ไม่บังคับ)</label><textarea name="description" rows="3"
                class="w-full rounded-lg border border-slate-300 px-4 py-2 text-slate-900 bg-white placeholder:text-slate-400 [color-scheme:light] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $isEdit ? $course->description : '') }}</textarea>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-center gap-2 border-b border-slate-100 pb-3"><b
                class="grid h-7 w-7 place-items-center rounded-lg bg-orange-50 text-sm text-orange-600">2</b>
            <div>
                <h2 class="text-lg font-semibold text-slate-900">กำหนดวันเรียนและเวลา</h2>
                <p class="text-xs text-slate-500">เลือกวันเอง เช่น Standard Class จันทร์ พุธ ศุกร์</p>
            </div>
        </div>
        <div id="scheduleRows" class="space-y-3"></div>
        <button type="button" id="addSchedule"
            class="mt-4 inline-flex items-center gap-1 rounded-lg border border-blue-200 px-3 py-1.5 text-sm font-medium text-blue-600 transition-colors duration-150 hover:bg-blue-50">＋ เพิ่มรอบเวลาเรียน</button>
        <div id="scheduleInputs"></div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-center gap-2 border-b border-slate-100 pb-3"><b
                class="grid h-7 w-7 place-items-center rounded-lg bg-orange-50 text-sm text-orange-600">3</b>
            <div>
                <h2 class="text-lg font-semibold text-slate-900">ตัวเลือกแพ็กเกจ</h2>
                <p class="text-xs text-slate-500">เพิ่มแพ็กเกจรายครั้ง, 4 ครั้ง, 8 ครั้ง หรือแบบอื่นได้ในคอร์สเดียวกัน
                </p>
            </div>
        </div>
        <div id="packageRows" class="space-y-3"></div>
        <button type="button" id="addPackage"
            class="mt-4 inline-flex items-center gap-1 rounded-lg border border-blue-200 px-3 py-1.5 text-sm font-medium text-blue-600 transition-colors duration-150 hover:bg-blue-50">＋ เพิ่มแพ็กเกจ</button>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center gap-2 border-b border-slate-100 pb-3"><b
                class="grid h-7 w-7 place-items-center rounded-lg bg-orange-50 text-sm text-orange-600">4</b>
            <div>
                <h2 class="text-lg font-semibold text-slate-900">ภาพประกอบคอร์ส</h2>
                <p class="text-xs text-slate-500">ไม่บังคับ, 1 ภาพ</p>
            </div>
        </div>

        <div id="dropzone"
            class="relative cursor-pointer overflow-hidden rounded-lg border-2 border-dashed border-slate-300 text-center transition-colors duration-150 hover:border-blue-400 hover:bg-blue-50/40">
            <div id="dropzone-empty" class="px-6 py-8 {{ $isEdit && $course->image_url ? 'hidden' : '' }}">
                <svg class="mx-auto mb-2 h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M12 12v9m0-9l-3 3m3-3l3 3" />
                </svg>
                <p class="text-sm text-slate-500"><span class="font-medium text-blue-600">คลิกเพื่อเลือกภาพ</span> หรือลากไฟล์มาวางที่นี่</p>
                <p class="mt-1 text-xs text-slate-400">JPG, PNG, WEBP ไม่เกิน 20MB</p>
            </div>
            <div id="dropzone-preview" class="relative {{ $isEdit && $course->image_url ? '' : 'hidden' }}">
                <img id="img-preview" src="{{ $isEdit ? $course->image_url : '' }}" class="h-40 w-full object-cover">
                <div class="group absolute inset-0 flex items-center justify-center bg-black/0 transition hover:bg-black/40">
                    <span class="text-sm font-medium text-white opacity-0 transition group-hover:opacity-100">คลิกเพื่อเปลี่ยนภาพ</span>
                </div>
            </div>
            <input id="image" name="image" type="file" accept="image/png,image/jpeg,image/webp" class="hidden">
        </div>
        <p id="image_error" class="hidden mt-1.5 text-xs text-red-600">รองรับเฉพาะไฟล์ JPG, PNG, WEBP และต้องมีขนาดไม่เกิน 20MB</p>
        <div class="mt-2 flex items-center gap-3">
            <p id="imageName" class="text-xs text-slate-500 {{ $isEdit && $course->image_url ? '' : 'hidden' }}">
                {{ $isEdit && $course->image_url ? 'ใช้ภาพเดิมของคอร์สนี้' : '' }}
            </p>
            @if($isEdit)
                <button type="button" id="remove-image-btn"
                    class="{{ $course->image_url ? '' : 'hidden' }} rounded-lg border border-red-200 bg-white px-3 py-1 text-xs font-medium text-red-600 hover:bg-red-50">
                    ลบภาพ
                </button>
                <input type="hidden" name="remove_image" id="remove_image_input" value="0">
            @endif
        </div>
    </section>
    <div class="flex justify-end gap-4 border-t border-slate-200 pt-4"><a href="{{ route('admin.courses') }}"
            class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm text-slate-700 hover:bg-slate-50">ยกเลิก</a><button
            class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">{{ $isEdit ? 'บันทึกการแก้ไข' : 'บันทึกคอร์ส' }}</button>
    </div>

    <template id="scheduleTemplate">
        <div class="schedule rounded-lg border border-slate-200 bg-slate-50 p-4 transition-colors duration-150">
            <div class="flex justify-between"><b class="text-sm text-slate-700">รอบเวลาเรียน <span class="text-xs text-red-500"> *</span></b><button type="button"
                    class="remove rounded-lg px-2 py-1 text-sm text-red-500 transition-colors duration-150 hover:bg-red-100">ลบรอบ</button></div>

            <label class="mb-2 mt-3 block text-xs font-medium text-slate-500">เลือกวันเรียน <span class="text-xs text-red-500"> *</span></label>
            <div class="days flex flex-wrap gap-2">
                @foreach(['mon'=>'จ','tue'=>'อ','wed'=>'พ','thu'=>'พฤ','fri'=>'ศ','sat'=>'ส','sun'=>'อา'] as $value=>$label)<label class="cursor-pointer"><input class="day peer sr-only" type="checkbox" value="{{ $value }}"><span
                        class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-slate-300 px-2 text-sm text-slate-600 transition peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white">{{ $label }}</span></label>@endforeach
            </div>
            <p class="day-error hidden mt-1.5 text-xs text-red-600">กรุณาเลือกวันเรียนอย่างน้อย 1 วัน</p>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">เวลาเริ่ม <span class="text-xs text-red-500"> *</span></label>
                    <input class="start w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white [color-scheme:light]" type="time">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">เวลาสิ้นสุด <span class="text-xs text-red-500"> *</span></label>
                    <div class="flex items-center gap-2">
                        <input class="end w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white [color-scheme:light]" type="time">
                        <span class="duration inline-block flex-shrink-0 whitespace-nowrap rounded-full bg-blue-100 px-2 py-1.5 text-xs font-medium text-blue-700">0.00 ชั่วโมง</span>
                    </div>
                </div>
            </div>
            <p class="time-error hidden mt-1.5 text-xs text-red-600">กรุณาเลือกเวลาเริ่มและเวลาสิ้นสุด</p>

            <div class="mt-3 flex flex-wrap items-center gap-3">
                <label class="text-slate-500 flex flex-shrink-0 cursor-pointer items-center gap-2 text-sm"><input class=" limited h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        type="checkbox">จำกัดจำนวน</label>
                <div class="capacityWrap hidden items-center gap-2">
                    <input class="capacity w-32 rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white [color-scheme:light] [-webkit-text-fill-color:#0f172a]" min="1" type="number"
                        placeholder="จำนวนคน">
                    <span class="text-xs text-slate-500">คน</span>
                </div>
            </div>
            <p class="capacity-error hidden mt-1.5 text-xs text-red-600">กรุณากรอกจำนวนคนสูงสุด เมื่อเลือก "จำกัดจำนวน"</p>
        </div>
    </template>
    <template id="packageTemplate">
        <div class="package rounded-lg border border-slate-200 bg-slate-50 p-4 transition-colors duration-150">
            <div class="flex justify-between"><b class="text-sm text-slate-700">แพ็กเกจ <span class="text-xs text-red-500"> *</span></b><button type="button"
                    class="removePackage rounded-lg px-2 py-1 text-sm text-red-500 transition-colors duration-150 hover:bg-red-100">ลบแพ็กเกจ</button></div>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">ประเภทคอร์ส <span class="text-xs text-red-500"> *</span></label>
                    <select class="packageType w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white [color-scheme:light]">
                        <option value="group">Standard Group Class</option>
                        <option value="private">Private Class</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">จำนวนครั้ง <span class="text-xs text-red-500"> *</span></label>
                    <input class="sessions w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white placeholder:text-slate-400 [color-scheme:light]" type="number" min="1"
                        placeholder="จำนวนครั้ง เช่น 4">
                    <p class="sessions-error hidden mt-1.5 text-xs text-red-600">กรุณากรอกจำนวนครั้ง</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">ราคา (บาท) <span class="text-xs text-red-500"> *</span></label>
                    <input class="price w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white placeholder:text-slate-400 [color-scheme:light]"
                        type="number" min="0" max="{{ $maxPackagePrice }}" step=".01" placeholder="ราคา (บาท)">
                    <p class="price-error hidden mt-1.5 text-xs text-red-600">กรุณากรอกราคาแพ็กเกจ</p>
                    <p class="avgPrice mt-1.5 text-xs font-medium text-green-600">💡 เฉลี่ยครั้งละ: <span class="avgPriceValue">0</span> บาท</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">อายุแพ็กเกจ <span class="text-xs text-red-500"> *</span></label>
                    <input class="validity w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white placeholder:text-slate-400 [color-scheme:light]" type="number" min="1"
                        placeholder="อายุแพ็กเกจ">
                    <p class="validity-error hidden mt-1.5 text-xs text-red-600">กรุณากรอกอายุแพ็กเกจ</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">หน่วยอายุ <span class="text-xs text-red-500"> *</span></label>
                    <select class="unit w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white [color-scheme:light]">
                        <option value="days">วัน</option>
                        <option value="hours">ชั่วโมง</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">คำแนะนำ (ไม่บังคับ)</label>
                    <input class="recommendation w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white placeholder:text-slate-400 [color-scheme:light]"
                        placeholder="คำแนะนำ (ไม่บังคับ)">
                </div>
            </div>
        </div>
    </template>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const schedules = document.getElementById('scheduleRows'),
        packages = document.getElementById('packageRows'),
        scheduleTemplate = document.getElementById('scheduleTemplate'),
        packageTemplate = document.getElementById('packageTemplate'),
        oldSchedules = @json($existingSchedules),
        oldPackages = @json($existingPackages);

    const MAX_PACKAGE_PRICE = {{ $maxPackagePrice }};
    const INVALID_CLASSES = ['!border-red-600', '!bg-red-50'];

    // ---------- helper: error แบบ field เดี่ยว (ใช้กับ error element ที่มี id ตายตัว) ----------
    function showFieldError(inputEl, errorId) {
        if (inputEl) inputEl.classList.add(...INVALID_CLASSES);
        const errEl = document.getElementById(errorId);
        if (errEl) errEl.classList.remove('hidden');
    }
    function clearFieldError(errorId, inputEl) {
        if (inputEl) inputEl.classList.remove(...INVALID_CLASSES);
        const errEl = document.getElementById(errorId);
        if (errEl) errEl.classList.add('hidden');
    }

    // ---------- helper: error แบบ scoped ภายในแถว (schedule / package row) ----------
    function showRowError(row, selector, message, invalidInputs = []) {
        const errEl = row.querySelector(selector);
        if (errEl) {
            if (message) errEl.textContent = message;
            errEl.classList.remove('hidden');
        }
        invalidInputs.forEach(el => el && el.classList.add(...INVALID_CLASSES));
    }
    function clearRowErrorEl(row, selector, invalidInputs = []) {
        const errEl = row.querySelector(selector);
        if (errEl) errEl.classList.add('hidden');
        invalidInputs.forEach(el => el && el.classList.remove(...INVALID_CLASSES));
    }

    // คำนวณระยะเวลาเรียน (ชม.) จากเวลาเริ่ม-เวลาสิ้นสุดของแต่ละรอบ
    function calcDuration(row) {
        const start = row.querySelector('.start').value;
        const end = row.querySelector('.end').value;
        const label = row.querySelector('.duration');
        if (!start || !end) {
            label.textContent = '0.00 ชั่วโมง';
            return;
        }
        const [sh, sm] = start.split(':').map(Number);
        const [eh, em] = end.split(':').map(Number);
        let minutes = (eh * 60 + em) - (sh * 60 + sm);
        if (minutes < 0) minutes = 0;
        const hours = minutes / 60;
        label.textContent = hours.toFixed(2) + ' ชั่วโมง';
    }

    // คำนวณราคาเฉลี่ยต่อครั้งของแต่ละแพ็กเกจ
    function calcPackageAverage(row) {
        const sessions = parseInt(row.querySelector('.sessions').value) || 0;
        const price = parseFloat(row.querySelector('.price').value) || 0;
        const valueEl = row.querySelector('.avgPriceValue');
        valueEl.textContent = sessions > 0 ? Math.round(price / sessions).toLocaleString() : '0';
    }

    function addSchedule(data = {
        weekdays: [],
        start: '00:00',
        end: '00:00'
    }) {
        const row = scheduleTemplate.content.firstElementChild.cloneNode(true);
        row.querySelector('.start').value = data.start;
        row.querySelector('.end').value = data.end;
        row.querySelector('.limited').checked = !!data.limited;
        row.querySelector('.capacity').value = data.capacity || '';
        const setCapacityVisible = visible => {
            const wrap = row.querySelector('.capacityWrap');
            wrap.classList.toggle('hidden', !visible);
            wrap.classList.toggle('flex', visible);
            if (!visible) row.querySelector('.capacity').value = '';
        };
        setCapacityVisible(!!data.limited);
        row.querySelectorAll('.day').forEach(input => input.checked = data.weekdays.includes(input.value));

        const clearDayError = () => clearRowErrorEl(row, '.day-error');
        const clearTimeError = () => clearRowErrorEl(row, '.time-error', [row.querySelector('.start'), row.querySelector('.end')]);
        const clearCapacityError = () => clearRowErrorEl(row, '.capacity-error', [row.querySelector('.capacity')]);

        row.querySelector('.limited').onchange = e => {
            setCapacityVisible(e.target.checked);
            clearCapacityError();
        };
        row.querySelectorAll('.day').forEach(input => input.onchange = clearDayError);
        row.querySelector('.start').oninput = () => { calcDuration(row); clearTimeError(); };
        row.querySelector('.end').oninput = () => { calcDuration(row); clearTimeError(); };
        row.querySelector('.capacity').oninput = clearCapacityError;
        row.querySelector('.remove').onclick = () => row.remove();
        schedules.append(row);
        calcDuration(row);
    }

    function addPackage(data = {}) {
        const row = packageTemplate.content.firstElementChild.cloneNode(true);
        row.querySelector('.packageType').value = data.package_type || 'group';
        row.querySelector('.sessions').value = data.total_sessions || '';
        row.querySelector('.price').value = data.total_price || '';
        row.querySelector('.validity').value = data.validity_value || '';
        row.querySelector('.unit').value = data.validity_unit || 'days';
        row.querySelector('.recommendation').value = data.recommendation_text || '';

        const clearSessionsError = () => clearRowErrorEl(row, '.sessions-error', [row.querySelector('.sessions')]);
        const clearPriceError = () => clearRowErrorEl(row, '.price-error', [row.querySelector('.price')]);
        const clearValidityError = () => clearRowErrorEl(row, '.validity-error', [row.querySelector('.validity')]);

        row.querySelector('.sessions').oninput = () => { calcPackageAverage(row); clearSessionsError(); };
        row.querySelector('.price').oninput = () => { calcPackageAverage(row); clearPriceError(); };
        row.querySelector('.validity').oninput = clearValidityError;
        row.querySelector('.removePackage').onclick = () => row.remove();
        packages.append(row);
        calcPackageAverage(row);
    }

    (oldSchedules.length ? oldSchedules : [undefined]).forEach(addSchedule);
    (oldPackages.length ? oldPackages : [undefined]).forEach(addPackage);
    document.getElementById('addSchedule').onclick = () => addSchedule();
    document.getElementById('addPackage').onclick = () => addPackage();

    document.getElementById('course_name_input').addEventListener('input', function () {
        clearFieldError('course_name_error', this);
    });
    ['min_age_input', 'max_age_input'].forEach(id => {
        document.getElementById(id).addEventListener('input', function () {
            clearFieldError('min_age_error');
            clearFieldError('max_age_error');
            document.getElementById('min_age_input').classList.remove(...INVALID_CLASSES);
            document.getElementById('max_age_input').classList.remove(...INVALID_CLASSES);
        });
    });
    document.querySelectorAll('.target_group').forEach(input => {
        input.addEventListener('change', () => clearFieldError('target_groups_error'));
    });

    // ---------- Dropzone รูปภาพ (คลิก / ลาก-วาง / preview / validate / ลบ) ----------
    const dropzone = document.getElementById('dropzone');
    const dropzoneEmpty = document.getElementById('dropzone-empty');
    const dropzonePreview = document.getElementById('dropzone-preview');
    const imgPreview = document.getElementById('img-preview');
    const imageInput = document.getElementById('image');
    const imageNameEl = document.getElementById('imageName');
    const removeImageBtn = document.getElementById('remove-image-btn');
    const removeImageInput = document.getElementById('remove_image_input');

    const DRAG_OVER_CLASSES = ['!border-blue-600', '!bg-blue-600/5'];
    const IMAGE_MAX_BYTES = 20 * 1024 * 1024; // 20MB ตามข้อความที่แจ้งผู้ใช้
    const IMAGE_ALLOWED_TYPES = ['image/png', 'image/jpeg', 'image/webp'];

    dropzone.addEventListener('click', () => imageInput.click());

    function showImageError(message) {
        const errEl = document.getElementById('image_error');
        errEl.textContent = message;
        errEl.classList.remove('hidden');
    }
    function clearImageError() {
        document.getElementById('image_error').classList.add('hidden');
    }

    // ภาพใหม่ที่เลือก (ไฟล์หรือลากวาง) จะแทนที่ภาพเดิม/ที่โชว์อยู่ทันที ไม่มีการเก็บภาพเดิมไว้ย้อนกลับ
    function showSelectedFile(file) {
        clearImageError();
        if (!IMAGE_ALLOWED_TYPES.includes(file.type)) {
            showImageError('รองรับเฉพาะไฟล์ JPG, PNG, WEBP เท่านั้น');
            imageInput.value = '';
            return;
        }
        if (file.size > IMAGE_MAX_BYTES) {
            showImageError('ไฟล์ต้องมีขนาดไม่เกิน 20MB');
            imageInput.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = e => {
            imgPreview.src = e.target.result;
            dropzoneEmpty.classList.add('hidden');
            dropzonePreview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
        imageNameEl.textContent = 'เลือกไฟล์: ' + file.name;
        imageNameEl.classList.remove('hidden');
        // มีการอัปโหลดภาพใหม่แล้ว ไม่ถือว่าเป็นการลบภาพอีกต่อไป และภาพเดิม (ถ้ามี) ถูกแทนที่ทันที
        if (removeImageInput) removeImageInput.value = '0';
        if (removeImageBtn) removeImageBtn.classList.remove('hidden');
    }

    // เคลียร์ทุกอย่างจนกลายเป็น dropzone ว่าง (ไม่มีภาพเลย) — ใช้ตอนกดปุ่ม "ลบภาพ" เท่านั้น
    function clearToEmpty() {
        imageInput.value = '';
        imgPreview.src = '';
        dropzoneEmpty.classList.remove('hidden');
        dropzonePreview.classList.add('hidden');
        imageNameEl.classList.add('hidden');
    }

    imageInput.addEventListener('change', () => {
        if (imageInput.files && imageInput.files[0]) showSelectedFile(imageInput.files[0]);
    });

    // ปุ่ม "ลบภาพ" — ลบภาพจริง ไม่ว่าจะเป็นภาพเดิมของคอร์ส หรือไฟล์ใหม่ที่เพิ่งเลือกไว้ก็ตาม
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', e => {
            e.stopPropagation();
            clearImageError();
            clearToEmpty();
            removeImageInput.value = '1';
            removeImageBtn.classList.add('hidden');
        });
    }

    ['dragenter', 'dragover'].forEach(evt => dropzone.addEventListener(evt, e => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.add(...DRAG_OVER_CLASSES);
    }));
    ['dragleave', 'drop'].forEach(evt => dropzone.addEventListener(evt, e => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove(...DRAG_OVER_CLASSES);
    }));
    dropzone.addEventListener('drop', e => {
        const file = e.dataTransfer.files[0];
        if (!file) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        imageInput.files = dt.files;
        showSelectedFile(file);
    });

    // ---------- ตรวจสอบความถูกต้องก่อน submit (แยก validate ทีละช่อง Input) ----------
    function validateForm() {
        let isValid = true;
        let firstInvalidEl = null;
        const markInvalid = el => { firstInvalidEl = firstInvalidEl || el; isValid = false; };

        // 1) ชื่อคลาสเรียน: ห้ามว่าง, ห้ามยาวเกิน 255 ตัวอักษร
        const courseNameInput = document.getElementById('course_name_input');
        const courseNameVal = courseNameInput.value.trim();
        if (!courseNameVal) {
            document.getElementById('course_name_error').textContent = 'กรุณากรอกชื่อคลาสเรียน';
            showFieldError(courseNameInput, 'course_name_error');
            markInvalid(courseNameInput);
        } else if (courseNameVal.length > 255) {
            document.getElementById('course_name_error').textContent = 'ชื่อคลาสเรียนต้องไม่เกิน 255 ตัวอักษร';
            showFieldError(courseNameInput, 'course_name_error');
            markInvalid(courseNameInput);
        }

        // 2) กลุ่มเป้าหมาย: ต้องเลือกอย่างน้อย 1 รายการ
        const targetGroupChecked = document.querySelectorAll('.target_group:checked').length > 0;
        if (!targetGroupChecked) {
            document.getElementById('target_groups_error').classList.remove('hidden');
            markInvalid(document.getElementById('target_groups_wrap'));
        }

        // 3) อายุขั้นต่ำ: ห้ามว่าง, ต้องเป็นตัวเลขจำนวนเต็ม >= 0
        const minAgeInput = document.getElementById('min_age_input');
        const maxAgeInput = document.getElementById('max_age_input');
        const minAgeVal = minAgeInput.value.trim();
        const maxAgeVal = maxAgeInput.value.trim();
        const isInt = v => /^-?\d+$/.test(v);

        if (minAgeVal === '') {
            document.getElementById('min_age_error').textContent = 'กรุณากรอกอายุขั้นต่ำ';
            showFieldError(minAgeInput, 'min_age_error');
            markInvalid(minAgeInput);
        } else if (!isInt(minAgeVal) || Number(minAgeVal) < 0) {
            document.getElementById('min_age_error').textContent = 'กรุณากรอกอายุขั้นต่ำเป็นตัวเลขเท่านั้น';
            showFieldError(minAgeInput, 'min_age_error');
            markInvalid(minAgeInput);
        }

        // 4) อายุสูงสุด (ไม่บังคับ): ถ้ากรอกต้องเป็นตัวเลขจำนวนเต็ม >= 0 และ >= อายุขั้นต่ำ
        if (maxAgeVal !== '') {
            if (!isInt(maxAgeVal) || Number(maxAgeVal) < 0) {
                document.getElementById('max_age_error').textContent = 'กรุณากรอกอายุสูงสุดเป็นตัวเลขเท่านั้น';
                showFieldError(maxAgeInput, 'max_age_error');
                markInvalid(maxAgeInput);
            } else if (isInt(minAgeVal) && Number(maxAgeVal) < Number(minAgeVal)) {
                document.getElementById('max_age_error').textContent = 'อายุสูงสุดต้องมากกว่าหรือเท่ากับอายุขั้นต่ำ';
                showFieldError(maxAgeInput, 'max_age_error');
                markInvalid(maxAgeInput);
            }
        }

        // 5) รอบเวลาเรียน: แยก validate วันเรียน และ เวลา ของแต่ละแถว
        const scheduleRowEls = [...schedules.children];
        scheduleRowEls.forEach(row => {
            const days = [...row.querySelectorAll('.day:checked')];
            if (!days.length) {
                showRowError(row, '.day-error', 'กรุณาเลือกวันเรียนอย่างน้อย 1 วัน');
                markInvalid(row.querySelector('.days'));
            }

            const startInput = row.querySelector('.start');
            const endInput = row.querySelector('.end');
            const start = startInput.value;
            const end = endInput.value;
            if (!start && !end) {
                showRowError(row, '.time-error', 'กรุณาเลือกเวลาเริ่มและเวลาสิ้นสุด', [startInput, endInput]);
                markInvalid(startInput);
            } else if (!start) {
                showRowError(row, '.time-error', 'กรุณาเลือกเวลาเริ่ม', [startInput]);
                markInvalid(startInput);
            } else if (!end) {
                showRowError(row, '.time-error', 'กรุณาเลือกเวลาสิ้นสุด', [endInput]);
                markInvalid(endInput);
            } else if (start >= end) {
                showRowError(row, '.time-error', 'เวลาเริ่มต้องน้อยกว่าเวลาสิ้นสุด', [startInput, endInput]);
                markInvalid(startInput);
            }

            const isLimited = row.querySelector('.limited').checked;
            const capacityInput = row.querySelector('.capacity');
            if (isLimited) {
                const capVal = capacityInput.value.trim();
                if (capVal === '' || !isInt(capVal) || Number(capVal) < 1) {
                    showRowError(row, '.capacity-error', 'กรุณากรอกจำนวนคนสูงสุด เมื่อเลือก "จำกัดจำนวน"', [capacityInput]);
                    markInvalid(capacityInput);
                }
            }
        });
        if (!scheduleRowEls.length) markInvalid(schedules);

        // 6) แพ็กเกจ: แยก validate จำนวนครั้ง / ราคา / อายุแพ็กเกจ ของแต่ละแถว
        const packageRowEls = [...packages.children];
        packageRowEls.forEach(row => {
            const sessionsInput = row.querySelector('.sessions');
            const priceInput = row.querySelector('.price');
            const validityInput = row.querySelector('.validity');
            const sessionsVal = sessionsInput.value.trim();
            const priceVal = priceInput.value.trim();
            const validityVal = validityInput.value.trim();

            if (sessionsVal === '') {
                showRowError(row, '.sessions-error', 'กรุณากรอกจำนวนครั้ง', [sessionsInput]);
                markInvalid(sessionsInput);
            } else if (!isInt(sessionsVal)) {
                showRowError(row, '.sessions-error', 'จำนวนครั้งต้องเป็นตัวเลข', [sessionsInput]);
                markInvalid(sessionsInput);
            } else if (Number(sessionsVal) < 1) {
                showRowError(row, '.sessions-error', Number(sessionsVal) === 0 ? 'กรุณากรอกจำนวนครั้ง' : 'จำนวนครั้งต้องเป็นจำนวนเต็มบวก', [sessionsInput]);
                markInvalid(sessionsInput);
            }

            if (priceVal === '') {
                showRowError(row, '.price-error', 'กรุณากรอกราคาแพ็กเกจ', [priceInput]);
                markInvalid(priceInput);
            } else if (isNaN(priceVal)) {
                showRowError(row, '.price-error', 'ราคาต้องเป็นตัวเลข', [priceInput]);
                markInvalid(priceInput);
            } else if (Number(priceVal) <= 0) {
                showRowError(row, '.price-error', 'ราคาต้องมากกว่า 0', [priceInput]);
                markInvalid(priceInput);
            } else if (Number(priceVal) > MAX_PACKAGE_PRICE) {
                showRowError(row, '.price-error', 'ราคาต้องไม่เกิน ' + MAX_PACKAGE_PRICE.toLocaleString() + ' บาท', [priceInput]);
                markInvalid(priceInput);
            }

            if (validityVal === '') {
                showRowError(row, '.validity-error', 'กรุณากรอกอายุแพ็กเกจ', [validityInput]);
                markInvalid(validityInput);
            } else if (!isInt(validityVal)) {
                showRowError(row, '.validity-error', 'อายุแพ็กเกจต้องเป็นตัวเลข', [validityInput]);
                markInvalid(validityInput);
            } else if (Number(validityVal) < 0) {
                showRowError(row, '.validity-error', 'อายุแพ็กเกจต้องมากกว่าหรือเท่ากับ 0', [validityInput]);
                markInvalid(validityInput);
            }
        });
        if (!packageRowEls.length) markInvalid(packages);

        if (!isValid && firstInvalidEl) {
            firstInvalidEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (typeof firstInvalidEl.focus === 'function') firstInvalidEl.focus();
        }

        return isValid;
    }

    document.getElementById('courseForm').onsubmit = e => {
        if (!validateForm()) {
            e.preventDefault();
            return;
        }

        let hidden = document.getElementById('scheduleInputs');
        hidden.innerHTML = '';
        [...schedules.children].forEach((row, index) => {
            const days = [...row.querySelectorAll('.day:checked')].map(input => input.value);
            const fields = {
                day_type: 'weekday',
                start_time: row.querySelector('.start').value,
                end_time: row.querySelector('.end').value,
                is_limited_spots: row.querySelector('.limited').checked ? '1' : '0',
                capacity: row.querySelector('.capacity').value
            };
            Object.entries(fields).forEach(([key, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `schedules[${index}][${key}]`;
                input.value = value;
                hidden.append(input)
            });
            days.forEach((value, dayIndex) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `schedules[${index}][weekdays][${dayIndex}]`;
                input.value = value;
                hidden.append(input)
            })
        });
        [...packages.children].forEach((row, index) => [
            ['package_type', '.packageType'],
            ['total_sessions', '.sessions'],
            ['total_price', '.price'],
            ['validity_value', '.validity'],
            ['validity_unit', '.unit'],
            ['recommendation_text', '.recommendation']
        ].forEach(([key, selector]) => row.querySelector(selector).name =
            `packages[${index}][${key}]`));
    }
});
</script>
