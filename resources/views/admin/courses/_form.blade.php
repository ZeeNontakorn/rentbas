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
            <label class="mb-2 block text-sm font-medium text-slate-700">ชื่อคลาสเรียน</label>
            <input id="course_name_input" name="course_name" value="{{ old('course_name', $isEdit ? $course->course_name : '') }}"
                class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-900 bg-white placeholder:text-slate-400 [color-scheme:light] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="เช่น Standard Class">
            <p id="course_name_error" class="hidden mt-1.5 text-xs text-red-600">กรุณากรอกชื่อคลาสเรียน</p>
        </div>
        <div class="grid gap-6 md:grid-cols-2">
            <div><label class="mb-3 block text-sm font-medium text-slate-700">กลุ่มผู้เรียนเป้าหมาย</label>
                <div class="grid grid-cols-2 gap-2">@foreach(['Rookie','Beginner','Junior','Player'] as $group)<label
                        class="flex cursor-pointer items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="target_groups[]"
                            value="{{ $group }}" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" @checked(in_array($group, old('target_groups', $isEdit ?
                            $course->targetGroups->pluck('target_group')->all() : [])))>{{ $group }}</label>@endforeach
                </div>
            </div>
            <div>
                <label class="mb-3 block text-sm font-medium text-slate-700">เกณฑ์อายุผู้เรียน</label>
                <div class="flex items-center gap-2">
                    <input id="min_age_input" type="number" name="min_age" min="0"
                        value="{{ old('min_age', $isEdit ? $course->min_age : '') }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white placeholder:text-slate-400 [color-scheme:light] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="ขั้นต่ำ"><span
                        class="flex-shrink-0 text-slate-400">ถึง</span><input id="max_age_input" type="number" name="max_age" min="0"
                        value="{{ old('max_age', $isEdit ? $course->max_age : '') }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white placeholder:text-slate-400 [color-scheme:light] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="สูงสุด">
                </div>
                <p id="age_range_error" class="hidden mt-1.5 text-xs text-red-600">กรุณากรอกอายุขั้นต่ำ และอายุสูงสุดต้องมากกว่าหรือเท่ากับอายุขั้นต่ำ</p>
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
        <p id="schedule_error" class="hidden mt-1.5 text-xs text-red-600">กรุณาเลือกวันเรียนอย่างน้อย 1 วัน กรอกเวลาให้ครบทุกรอบ (เวลาสิ้นสุดต้องมากกว่าเวลาเริ่มต้น) และถ้าติ๊ก "จำกัดจำนวน" ต้องกรอกจำนวนคนด้วย</p>
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
            <p id="package_error" class="hidden mt-1.5 text-xs text-red-600">กรุณากรอกจำนวนครั้ง ราคา (ไม่เกิน {{ number_format($maxPackagePrice) }} บาท) และอายุแพ็กเกจให้ครบทุกแพ็กเกจ (เป็นตัวเลขที่ถูกต้อง)</p>        <button type="button" id="addPackage"
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
                <p class="mt-1 text-xs text-slate-400">JPG, PNG, WEBP ไม่เกิน 2MB</p>
            </div>
            <div id="dropzone-preview" class="relative {{ $isEdit && $course->image_url ? '' : 'hidden' }}">
                <img id="img-preview" src="{{ $isEdit ? $course->image_url : '' }}" class="h-40 w-full object-cover">
                <div class="group absolute inset-0 flex items-center justify-center bg-black/0 transition hover:bg-black/40">
                    <span class="text-sm font-medium text-white opacity-0 transition group-hover:opacity-100">คลิกเพื่อเปลี่ยนภาพ</span>
                </div>
            </div>
            <input id="image" name="image" type="file" accept="image/png,image/jpeg,image/webp" class="hidden">
        </div>
        <p id="image_error" class="hidden mt-1.5 text-xs text-red-600">รองรับเฉพาะไฟล์ JPG, PNG, WEBP และต้องมีขนาดไม่เกิน 2MB</p>
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
            <div class="flex justify-between"><b class="text-sm text-slate-700">รอบเวลาเรียน</b><button type="button"
                    class="remove rounded-lg px-2 py-1 text-sm text-red-500 transition-colors duration-150 hover:bg-red-100">ลบรอบ</button></div>

            <label class="mb-2 mt-3 block text-xs font-medium text-slate-500">เลือกวันเรียน</label>
            <div class="flex flex-wrap gap-2">
                @foreach(['mon'=>'จ','tue'=>'อ','wed'=>'พ','thu'=>'พฤ','fri'=>'ศ','sat'=>'ส','sun'=>'อา'] as $value=>$label)<label class="cursor-pointer"><input class="day peer sr-only" type="checkbox" value="{{ $value }}"><span
                        class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-slate-300 px-2 text-sm text-slate-600 transition peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white">{{ $label }}</span></label>@endforeach
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">เวลาเริ่ม</label>
                    <input class="start w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white [color-scheme:light]" type="time">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">เวลาสิ้นสุด</label>
                    <div class="flex items-center gap-2">
                        <input class="end w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white [color-scheme:light]" type="time">
                        <span class="duration inline-block flex-shrink-0 whitespace-nowrap rounded-full bg-blue-100 px-2 py-1.5 text-xs font-medium text-blue-700">0.00 ชั่วโมง</span>
                    </div>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-3">
                <label class="text-slate-500 flex flex-shrink-0 cursor-pointer items-center gap-2 text-sm"><input class=" limited h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        type="checkbox">จำกัดจำนวน</label>
                <div class="capacityWrap hidden items-center gap-2">
                    <input class="capacity w-32 rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white [color-scheme:light] [-webkit-text-fill-color:#0f172a]" min="1" type="number"
                        placeholder="จำนวนคน">
                    <span class="text-xs text-slate-500">คน</span>
                </div>
            </div>
        </div>
    </template>
    <template id="packageTemplate">
        <div class="package rounded-lg border border-slate-200 bg-slate-50 p-4 transition-colors duration-150">
            <div class="flex justify-between"><b class="text-sm text-slate-700">แพ็กเกจ</b><button type="button"
                    class="removePackage rounded-lg px-2 py-1 text-sm text-red-500 transition-colors duration-150 hover:bg-red-100">ลบแพ็กเกจ</button></div>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">ประเภทคอร์ส</label>
                    <select class="packageType w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white [color-scheme:light]">
                        <option value="group">Standard Group Class</option>
                        <option value="private">Private Class</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">จำนวนครั้ง</label>
                    <input class="sessions w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white placeholder:text-slate-400 [color-scheme:light]" type="number" min="1"
                        placeholder="จำนวนครั้ง เช่น 4">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">ราคา (บาท)</label>
                    <input class="price w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white placeholder:text-slate-400 [color-scheme:light]"
                        type="number" min="0" max="{{ $maxPackagePrice }}" step=".01" placeholder="ราคา (บาท)">

                    
                    <p class="avgPrice mt-1.5 text-xs font-medium text-green-600">💡 เฉลี่ยครั้งละ: <span class="avgPriceValue">0</span> บาท</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">อายุแพ็กเกจ</label>
                    <input class="validity w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 bg-white placeholder:text-slate-400 [color-scheme:light]" type="number" min="1"
                        placeholder="อายุแพ็กเกจ">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">หน่วยอายุ</label>
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

    // ---------- helper: error แบบ field เดี่ยว ----------
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
        weekdays: ['mon', 'wed', 'fri'],
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
        const clearRowError = () => {
            row.classList.remove(...INVALID_CLASSES);
            row.querySelector('.capacity').classList.remove(...INVALID_CLASSES);
            clearFieldError('schedule_error');
        };
        row.querySelector('.limited').onchange = e => {
            setCapacityVisible(e.target.checked);
            clearRowError();
        };
        row.querySelectorAll('.day').forEach(input => input.onchange = clearRowError);
        row.querySelector('.start').oninput = () => { calcDuration(row); clearRowError(); };
        row.querySelector('.end').oninput = () => { calcDuration(row); clearRowError(); };
        row.querySelector('.capacity').oninput = clearRowError;
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
        const clearRowError = () => { row.classList.remove(...INVALID_CLASSES); clearFieldError('package_error'); };
        row.querySelector('.sessions').oninput = () => { calcPackageAverage(row); clearRowError(); };
        row.querySelector('.price').oninput = () => { calcPackageAverage(row); clearRowError(); };
        row.querySelector('.validity').oninput = clearRowError;
        row.querySelector('.removePackage').onclick = () => row.remove();
        packages.append(row);
        calcPackageAverage(row);
    }(oldSchedules.length ? oldSchedules : [undefined]).forEach(addSchedule);
    (oldPackages.length ? oldPackages : [undefined]).forEach(addPackage);
    document.getElementById('addSchedule').onclick = () => addSchedule();
    document.getElementById('addPackage').onclick = () => addPackage();

    document.getElementById('course_name_input').addEventListener('input', function () {
        clearFieldError('course_name_error', this);
    });
    ['min_age_input', 'max_age_input'].forEach(id => {
        document.getElementById(id).addEventListener('input', function () {
            clearFieldError('age_range_error');
            document.getElementById('min_age_input').classList.remove(...INVALID_CLASSES);
            document.getElementById('max_age_input').classList.remove(...INVALID_CLASSES);
        });
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
    const IMAGE_MAX_BYTES = 2 * 1024 * 1024; // 2MB ตามข้อความที่แจ้งผู้ใช้
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
            showImageError('ไฟล์ต้องมีขนาดไม่เกิน 2MB');
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

    // ---------- ตรวจสอบความถูกต้องก่อน submit ----------
    function validateForm() {
        let isValid = true;
        let firstInvalidEl = null;

        const courseNameInput = document.getElementById('course_name_input');
        if (!courseNameInput.value.trim()) {
            showFieldError(courseNameInput, 'course_name_error');
            firstInvalidEl = firstInvalidEl || courseNameInput;
            isValid = false;
        }

        const minAgeInput = document.getElementById('min_age_input');
        const maxAgeInput = document.getElementById('max_age_input');
        const minAgeVal = minAgeInput.value.trim();
        const maxAgeVal = maxAgeInput.value.trim();
        let ageInvalid = false;
        if (minAgeVal === '' || isNaN(minAgeVal) || Number(minAgeVal) < 0) ageInvalid = true;
        if (maxAgeVal !== '' && (isNaN(maxAgeVal) || Number(maxAgeVal) < Number(minAgeVal || 0))) ageInvalid = true;
        if (ageInvalid) {
            showFieldError(minAgeInput, 'age_range_error');
            maxAgeInput.classList.add(...INVALID_CLASSES);
            firstInvalidEl = firstInvalidEl || minAgeInput;
            isValid = false;
        }

        let scheduleInvalid = false;
        const scheduleRowEls = [...schedules.children];
        if (scheduleRowEls.length === 0) scheduleInvalid = true;
        scheduleRowEls.forEach(row => {
            const days = [...row.querySelectorAll('.day:checked')];
            const start = row.querySelector('.start').value;
            const end = row.querySelector('.end').value;
            let rowBad = false;
            if (!days.length) rowBad = true;
            if (!start || !end || start >= end) rowBad = true;
            const isLimited = row.querySelector('.limited').checked;
            const capacityInput = row.querySelector('.capacity');
            if (isLimited) {
                const capVal = capacityInput.value.trim();
                if (capVal === '' || isNaN(capVal) || !Number.isInteger(Number(capVal)) || Number(capVal) < 1) {
                    capacityInput.classList.add(...INVALID_CLASSES);
                    rowBad = true;
                }
            }
            if (rowBad) {
                row.classList.add(...INVALID_CLASSES);
                scheduleInvalid = true;
                firstInvalidEl = firstInvalidEl || row;
            }
        });
        if (scheduleInvalid) {
            document.getElementById('schedule_error').classList.remove('hidden');
            isValid = false;
        }

        let packageInvalid = false;
        const packageRowEls = [...packages.children];
        if (packageRowEls.length === 0) packageInvalid = true;
        packageRowEls.forEach(row => {
            const sessionsVal = row.querySelector('.sessions').value.trim();
            const priceVal = row.querySelector('.price').value.trim();
            const validityVal = row.querySelector('.validity').value.trim();
            let rowBad = false;
            if (sessionsVal === '' || isNaN(sessionsVal) || !Number.isInteger(Number(sessionsVal)) || Number(sessionsVal) < 1) rowBad = true;
            if (priceVal === '' || isNaN(priceVal) || Number(priceVal) < 0 || Number(priceVal) > MAX_PACKAGE_PRICE) rowBad = true;
            if (validityVal === '' || isNaN(validityVal) || !Number.isInteger(Number(validityVal)) || Number(validityVal) < 1) rowBad = true;
            if (rowBad) {
                row.classList.add(...INVALID_CLASSES);
                packageInvalid = true;
                firstInvalidEl = firstInvalidEl || row;
            }
        });
        if (packageInvalid) {
            document.getElementById('package_error').classList.remove('hidden');
            isValid = false;
        }

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