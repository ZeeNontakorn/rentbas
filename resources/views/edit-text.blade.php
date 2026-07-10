@extends('layouts.app')

@section('title', 'แก้ไขเนื้อหาและโปรโมชั่น')

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen min-w-screen py-8">
    <div class="container mx-auto px-6 max-w-4xl">

        {{-- Header --}}
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">แก้ไขเนื้อหาเว็บไซต์</h1>
                <p class="text-sm text-gray-500 mt-1">จัดการข้อความโปรโมชั่น รูปภาพ และส่วน About Court</p>
            </div>
        </div>

        <div id="save-errors" class="hidden mb-6 bg-red-50 border border-red-200 rounded-xl px-5 py-4">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <p class="font-semibold text-red-800">เกิดข้อผิดพลาด</p>
            </div>
            <ul id="save-errors-list" class="list-disc list-inside text-sm text-red-600 space-y-1"></ul>
        </div>

        @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-xl px-5 py-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <p class="font-semibold text-red-800">เกิดข้อผิดพลาด</p>
                </div>
                <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="space-y-6">

            {{-- ─── Section 1: About Court ─── --}}
            <form action="{{ route('admin.edit.text.update') }}" method="POST" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 js-setting-form" data-section="about">
                @csrf
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-1">1. แก้ไขส่วน About Court</h3>
                        <p class="text-xs text-gray-400">ข้อความที่แสดงในส่วน "เกี่ยวกับสนาม" ของหน้าหลัก</p>

                    </div>
                    <x-save-status />
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            หัวข้อหลัก (About Title)
                        </label>
                        <input type="text" name="about_title"
                               class="w-full border-2 border-gray-200 rounded-lg px-4 py-2.5 focus:border-orange-500 outline-none transition text-sm"
                               value="{{ old('about_title', $settings['about_title'] ?? '') }}"
                               placeholder="เช่น สนามที่ได้มาตรฐาน ระบบการจองที่ทันสมัย">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            รายละเอียด (About Description)
                        </label>
                        <textarea name="about_desc" rows="3"
                                  class="w-full border-2 border-gray-200 rounded-lg px-4 py-2.5 focus:border-orange-500 outline-none transition text-sm"
                                  placeholder="คำอธิบายส่วน About Court">{{ old('about_desc', $settings['about_desc'] ?? '') }}</textarea>
                    </div>
                    {{-- About Images --}}
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 mb-3 border-b pb-2">About Court (ภาพเกี่ยวกับสนาม)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            @foreach([1, 2, 3] as $i)
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-2">ภาพ About {{ $i }}</label>
                                <img id="preview-about-{{ $i }}" src="{{ $settings['about_img_'.$i] ?? '' }}" class="h-40 w-full object-cover rounded-lg border border-gray-200">
                                <input type="file" name="about_img_{{ $i }}_file" accept="image/*"
                                       class="block w-full text-xs text-gray-500 mb-2 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-600 hover:file:bg-orange-100 cursor-pointer"
                                       onchange="previewImg(this, 'preview-about-{{ $i }}')">
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <x-form-action-button type="reset" variant="reset">ยกเลิก</x-form-action-button>
                    <x-form-action-button type="submit" icon="check">บันทึก</x-form-action-button>
                </div>
            </form>

            {{-- ─── Section 2: Promotion Header ─── --}}
            <form action="{{ route('admin.edit.text.update') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 js-setting-form" data-section="promo">
                @csrf
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-1">2. แก้ไขส่วน Preview Promotion</h3>
                        <p class="text-xs text-gray-400">ข้อความหัวเรื่องและการ์ดโปรโมชั่นแรกในหน้าหลัก</p>
                    </div>
                    <x-save-status />
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                ข้อความด้านบนโปรโมชั่น (Subtitle)
                            </label>
                            <input type="text" name="promo_subtitle"
                                   class="w-full border-2 border-gray-200 rounded-lg px-4 py-2.5 focus:border-orange-500 outline-none transition text-sm"
                                   value="{{ old('promo_subtitle', $settings['promo_subtitle'] ?? '') }}"
                                   placeholder="เช่น อัปเดตโปรโมชั่นสุดพิเศษ">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                หัวข้อโปรโมชั่น (Title)
                            </label>
                            <input type="text" name="promo_title"
                                   class="w-full border-2 border-gray-200 rounded-lg px-4 py-2.5 focus:border-orange-500 outline-none transition text-sm"
                                   value="{{ old('promo_title', $settings['promo_title'] ?? '') }}"
                                   placeholder="เช่น Preview Promotion">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                ชื่อบนการ์ดโปรโมชั่น (Card Title)
                            </label>
                            <input type="text" name="promo_card_title"
                                   class="w-full border-2 border-gray-200 rounded-lg px-4 py-2.5 focus:border-orange-500 outline-none transition text-sm"
                                   value="{{ old('promo_card_title', $settings['promo_card_title'] ?? '') }}"
                                   placeholder="เช่น BASKETBALL">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                คำอธิบายบนการ์ดโปรโมชั่น (Card Subtitle)
                            </label>
                            <input type="text" name="promo_card_sub"
                                   class="w-full border-2 border-gray-200 rounded-lg px-4 py-2.5 focus:border-orange-500 outline-none transition text-sm"
                                   value="{{ old('promo_card_sub', $settings['promo_card_sub'] ?? '') }}"
                                   placeholder="เช่น โปรโมชั่นพิเศษ">
                        </div>
                    </div>

                    {{-- Image Upload --}}
                    @php($promoImg = $settings['promo_image'] ?? null)
                    <div class="pt-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            แบนเนอร์โปรโมชั่น
                        </label>
                            {{-- Current image preview --}}
                            <div id="img-preview-wrap" class="{{ empty($promoImg) ? 'hidden' : '' }}">
                                <img id="img-preview"
                                     src="{{ $promoImg ?? '' }}"
                                     class="h-40 w-full rounded-lg object-cover border-2 border-gray-200 shadow-sm">
                                <p class="text-xs text-center text-gray-400 mt-1">รูปปัจจุบัน</p>
                                <div class="flex items-start gap-4">
                            <div class="flex-1">
                                <input type="file" name="promo_image_file" accept="image/*"
                                       class="block w-full text-sm text-gray-500
                                              file:mr-4 file:py-2 file:px-4
                                              file:rounded-lg file:border-0
                                              file:text-sm file:font-semibold
                                              file:bg-orange-50 file:text-orange-600
                                              hover:file:bg-orange-100 cursor-pointer"
                                       onchange="previewImg(this)">
                            </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <x-form-action-button type="reset" variant="reset">ยกเลิก</x-form-action-button>
                    <x-form-action-button type="submit" icon="check">บันทึก</x-form-action-button>
                </div>
            </form>

            {{-- ─── Section 3: Other Images ─── --}}
            <form action="{{ route('admin.edit.text.update') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 js-setting-form" data-section="images">
                @csrf
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-1">3. แก้ไขรูปภาพหน้าหลักอื่นๆ</h3>
                        <p class="text-xs text-gray-400">อัปโหลดรูปภาพส่วนต่างๆ ของหน้า Home Page (Hero, พื้นหลัง, Community)</p>
                    </div>
                    <x-save-status />
                </div>

                <div class="space-y-6">

                    {{-- Hero Images --}}
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 mb-3 border-b pb-2">Hero Banners (ภาพสไลด์บนสุด)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            @foreach([1, 2, 3] as $i)
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-2">ภาพ Hero Banner {{ $i }}</label>
                                <img id="preview-hero-{{ $i }}" src="{{ $settings['hero_img_'.$i] ?? '' }}" class="h-40 w-full object-cover rounded-lg border border-gray-200">
                                <input type="file" name="hero_img_{{ $i }}_file" accept="image/*"
                                       class="block w-full text-xs text-gray-500 mb-2 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-600 hover:file:bg-orange-100 cursor-pointer"
                                       onchange="previewImg(this, 'preview-hero-{{ $i }}')">
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Courts Background & Community --}}
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 mb-3 border-b pb-2">Background & Community</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-2">พื้นหลังสนาม</label>
                                <img id="preview-courts" src="{{ $settings['courts_bg'] ?? '' }}" class="h-60 w-full object-cover rounded-lg border border-gray-200">
                                <input type="file" name="courts_bg_file" accept="image/*"
                                       class="block w-full text-xs text-gray-500 mb-2 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-600 hover:file:bg-orange-100 cursor-pointer"
                                       onchange="previewImg(this, 'preview-courts')">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-2">ภาพ Community</label>
                                <img id="preview-community" src="{{ $settings['community_img'] ?? '' }}" class="h-60 w-full object-cover rounded-lg border border-gray-200">
                                <input type="file" name="community_img_file" accept="image/*"
                                       class="block w-full text-xs text-gray-500 mb-2 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-600 hover:file:bg-orange-100 cursor-pointer"
                                       onchange="previewImg(this, 'preview-community')">
                            </div>
                        </div>
                    </div>

                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <x-form-action-button type="reset" variant="reset">ยกเลิก</x-form-action-button>
                    <x-form-action-button type="submit" icon="check">บันทึก</x-form-action-button>
                </div>
            </form>

            {{-- ─── Section 4: รูปภาพประจำสนาม (Court Booking) ─── --}}
            <form action="{{ route('admin.courts.images.update') }}" method="POST"
                  enctype="multipart/form-data" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 js-setting-form"
                  data-section="court-images">
                @csrf
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-1">4. แก้ไขรูปภาพประจำสนาม</h3>
                        <p class="text-xs text-gray-400">รูปแบนเนอร์ที่จะแสดงในหน้า Court Booking ของแต่ละสนาม</p>
                    </div>
                    <x-save-status />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($courts as $court)
                    @php($courtImg = $settings['court_img_' . $court->id] ?? null)
                    @php($courtImgSrc = $courtImg ?: 'https://images.unsplash.com/photo-1577416412292-747c6607f055?q=80&w=2340&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D')
                    <div class="border border-gray-200 rounded-lg p-4">
                        <p class="font-semibold text-sm text-gray-800 mb-3">{{ $court->name }}</p>

                        <img id="preview-court-{{ $court->id }}"
                             src="{{ $courtImgSrc }}"
                             class="h-32 w-full object-cover rounded-lg border border-gray-200 mb-3">

                        <input type="file" name="court_images[{{ $court->id }}]" accept="image/*"
                               class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg
                                      file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-600
                                      hover:file:bg-orange-100 cursor-pointer"
                               onchange="previewImg(this, 'preview-court-{{ $court->id }}')">
                    </div>
                    @endforeach
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <x-form-action-button type="reset" variant="reset">ยกเลิก</x-form-action-button>
                    <x-form-action-button type="submit" icon="check">บันทึก</x-form-action-button>
                </div>
            </form>

        </div>

    </div>
</div>

@push('scripts')
<script>
function previewImg(input, targetId = 'img-preview') {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const el = document.getElementById(targetId);
            if(el) el.src = e.target.result;
            if(targetId === 'img-preview') {
                document.getElementById('img-preview-wrap').classList.remove('hidden');
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function serializeSettingForm(form) {
    return Array.from(form.elements)
        .filter(element => element.name && !['submit', 'reset', 'button'].includes(element.type))
        .map(element => {
            if (element.type === 'file') {
                return `${element.name}:${element.files && element.files.length ? '1' : '0'}`;
            }

            if (element.type === 'checkbox' || element.type === 'radio') {
                return `${element.name}:${element.checked ? '1' : '0'}`;
            }

            return `${element.name}:${element.value}`;
        })
        .join('|');
}

function clearFormFileInputs(form) {
    form.querySelectorAll('input[type="file"]').forEach(input => {
        input.value = '';
    });
}

function showSaveErrors(errors) {
    const errorBox = document.getElementById('save-errors');
    const errorList = document.getElementById('save-errors-list');

    if (!errorBox || !errorList) {
        return;
    }

    errorList.innerHTML = '';
    Object.values(errors || {}).flat().forEach(message => {
        const item = document.createElement('li');
        item.textContent = message;
        errorList.appendChild(item);
    });

    errorBox.classList.remove('hidden');
}

function hideSaveErrors() {
    const errorBox = document.getElementById('save-errors');
    const errorList = document.getElementById('save-errors-list');

    if (errorBox) {
        errorBox.classList.add('hidden');
    }

    if (errorList) {
        errorList.innerHTML = '';
    }
}

function setSaveStatus(statusEl, isDirty) {
    const savedLabel = statusEl.dataset.savedLabel;
    const dirtyLabel = statusEl.dataset.dirtyLabel;
    const savedClass = statusEl.dataset.savedClass;
    const dirtyClass = statusEl.dataset.dirtyClass;

    statusEl.textContent = isDirty ? dirtyLabel : savedLabel;
    statusEl.className = `js-save-status inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold ${isDirty ? dirtyClass : savedClass}`;
}

document.querySelectorAll('.js-setting-form').forEach(form => {
    const statusEl = form.querySelector('.js-save-status');
    let initialState = serializeSettingForm(form);
    const submitButton = form.querySelector('button[type="submit"]');
    const defaultSubmitButtonHtml = submitButton ? submitButton.innerHTML : '';
    let submitSuccessTimeoutId;

    const refreshStatus = () => setSaveStatus(statusEl, serializeSettingForm(form) !== initialState);
    const setSaving = isSaving => {
        if (submitButton) {
            submitButton.disabled = isSaving;
            submitButton.classList.toggle('opacity-70', isSaving);
            submitButton.classList.toggle('cursor-not-allowed', isSaving);
        }
    };

    const showSubmitSuccess = () => {
        if (!submitButton) {
            return;
        }

        if (submitSuccessTimeoutId) {
            clearTimeout(submitSuccessTimeoutId);
        }

        submitButton.innerHTML = 'บันทึกสำเร็จ';
        submitButton.classList.remove('bg-orange-500', 'hover:bg-orange-600');
        submitButton.classList.add('bg-green-600', 'hover:bg-green-700');

        submitSuccessTimeoutId = setTimeout(() => {
            submitButton.innerHTML = defaultSubmitButtonHtml;
            submitButton.classList.remove('bg-green-600', 'hover:bg-green-700');
            submitButton.classList.add('bg-orange-500', 'hover:bg-orange-600');
        }, 1800);
    };

    form.addEventListener('input', refreshStatus);
    form.addEventListener('change', refreshStatus);
    form.addEventListener('reset', () => {
        requestAnimationFrame(refreshStatus);
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();

        hideSaveErrors();
        setSaving(true);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                showSaveErrors(payload.errors || { general: [payload.message || 'บันทึกไม่สำเร็จ'] });
                return;
            }

            clearFormFileInputs(form);
            initialState = serializeSettingForm(form);
            refreshStatus();
            showSubmitSuccess();
        } catch (error) {
            showSaveErrors({ general: ['ไม่สามารถบันทึกข้อมูลได้ กรุณาลองอีกครั้ง'] });
        } finally {
            setSaving(false);
        }
    });

    refreshStatus();
});
</script>
@endpush
@endsection
