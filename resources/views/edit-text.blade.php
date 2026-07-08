@extends('layouts.app')

@section('title', 'แก้ไขเนื้อหาและโปรโมชั่น')

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen py-8">
    <div class="container mx-auto px-6 max-w-4xl">

        {{-- Header --}}
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">แก้ไขเนื้อหาเว็บไซต์</h1>
                <p class="text-sm text-gray-500 mt-1">จัดการข้อความโปรโมชั่น รูปภาพ และส่วน About Court</p>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div id="flash-success"
                 class="mb-6 bg-green-50 border border-green-200 rounded-xl px-5 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-green-800">บันทึกสำเร็จ!</p>
                        <p class="text-sm text-green-600">{{ session('success') }}</p>
                    </div>
                </div>
                <button onclick="document.getElementById('flash-success').remove()"
                        class="text-green-400 hover:text-green-600 text-xl font-bold">✕</button>
            </div>
        @endif

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
            <form action="{{ route('admin.edit.text.update') }}" method="POST" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                @csrf
                <h3 class="text-lg font-bold text-gray-800 mb-1">1. แก้ไขส่วน About Court</h3>
                <p class="text-xs text-gray-400 mb-5">ข้อความที่แสดงในส่วน "เกี่ยวกับสนาม" ของหน้าหลัก</p>

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
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="reset"
                            class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="px-8 py-2.5 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition shadow-sm font-semibold text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        บันทึก
                    </button>
                </div>
            </form>

            {{-- ─── Section 2: Promotion Header ─── --}}
            <form action="{{ route('admin.edit.text.update') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                @csrf
                <h3 class="text-lg font-bold text-gray-800 mb-1">2. แก้ไขส่วน Preview Promotion</h3>
                <p class="text-xs text-gray-400 mb-5">ข้อความหัวเรื่องและการ์ดโปรโมชั่นแรกในหน้าหลัก</p>

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
                    <div class="pt-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            อัปโหลดแบนเนอร์โปรโมชั่น (รูปแรกในแถว)
                        </label>
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
                                <p class="text-xs text-gray-400 mt-1">PNG, JPG, WEBP ขนาดไม่เกิน 2MB</p>
                            </div>
                            {{-- Current image preview --}}
                            <div id="img-preview-wrap" class="{{ empty($settings['promo_image']) ? 'hidden' : '' }}">
                                <img id="img-preview"
                                     src="{{ $settings['promo_image'] ?? '' }}"
                                     class="h-20 w-32 rounded-lg object-cover border-2 border-gray-200 shadow-sm">
                                <p class="text-xs text-center text-gray-400 mt-1">รูปปัจจุบัน</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="reset"
                            class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="px-8 py-2.5 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition shadow-sm font-semibold text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        บันทึก
                    </button>
                </div>
            </form>

            {{-- ─── Section 3: Other Images ─── --}}
            <form action="{{ route('admin.edit.text.update') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                @csrf
                <h3 class="text-lg font-bold text-gray-800 mb-1">3. แก้ไขรูปภาพหน้าหลักอื่นๆ</h3>
                <p class="text-xs text-gray-400 mb-5">อัปโหลดรูปภาพส่วนต่างๆ ของหน้า Home Page (Hero, About, พื้นหลัง, Community)</p>

                <div class="space-y-6">

                    {{-- Hero Images --}}
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 mb-3 border-b pb-2">Hero Banners (ภาพสไลด์บนสุด)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            @foreach([1, 2, 3] as $i)
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-2">ภาพที่ {{ $i }}</label>
                                <input type="file" name="hero_img_{{ $i }}_file" accept="image/*"
                                       class="block w-full text-xs text-gray-500 mb-2 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-600 hover:file:bg-orange-100 cursor-pointer"
                                       onchange="previewImg(this, 'preview-hero-{{ $i }}')">
                                <img id="preview-hero-{{ $i }}" src="{{ $settings['hero_img_'.$i] ?? '' }}" class="h-20 w-full object-cover rounded-lg border border-gray-200">
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- About Images --}}
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 mb-3 border-b pb-2">About Court (ภาพเกี่ยวกับสนาม)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            @foreach([1, 2, 3] as $i)
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-2">ภาพส่วน About ที่ {{ $i }}</label>
                                <input type="file" name="about_img_{{ $i }}_file" accept="image/*"
                                       class="block w-full text-xs text-gray-500 mb-2 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-600 hover:file:bg-orange-100 cursor-pointer"
                                       onchange="previewImg(this, 'preview-about-{{ $i }}')">
                                <img id="preview-about-{{ $i }}" src="{{ $settings['about_img_'.$i] ?? '' }}" class="h-20 w-full object-cover rounded-lg border border-gray-200">
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Courts Background & Community --}}
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 mb-3 border-b pb-2">Background & Community</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-2">พื้นหลังส่วนสนาม (Courts Background)</label>
                                <input type="file" name="courts_bg_file" accept="image/*"
                                       class="block w-full text-xs text-gray-500 mb-2 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-600 hover:file:bg-orange-100 cursor-pointer"
                                       onchange="previewImg(this, 'preview-courts')">
                                <img id="preview-courts" src="{{ $settings['courts_bg'] ?? '' }}" class="h-20 w-full object-cover rounded-lg border border-gray-200">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-2">ภาพส่วน Community</label>
                                <input type="file" name="community_img_file" accept="image/*"
                                       class="block w-full text-xs text-gray-500 mb-2 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-600 hover:file:bg-orange-100 cursor-pointer"
                                       onchange="previewImg(this, 'preview-community')">
                                <img id="preview-community" src="{{ $settings['community_img'] ?? '' }}" class="h-20 w-full object-cover rounded-lg border border-gray-200">
                            </div>
                        </div>
                    </div>

                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="reset"
                            class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="px-8 py-2.5 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition shadow-sm font-semibold text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        บันทึก
                    </button>
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
// Auto-dismiss flash after 5s
setTimeout(() => {
    const el = document.getElementById('flash-success');
    if (el) el.style.transition = 'opacity 0.5s';
    if (el) el.style.opacity = '0';
    setTimeout(() => el && el.remove(), 500);
}, 5000);
</script>
@endpush
@endsection
