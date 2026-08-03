@extends('layouts.app')

@section('title', 'จัดการเว็บไซต์')

@section('content')
<div class="bg-slate-50 text-gray-900 min-h-screen min-w-screen py-8">
    <div class="container mx-auto px-6 max-w-4xl">

        {{-- Header --}}
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">จัดการเว็บไซต์</h1>
                <p class="text-sm text-gray-500 mt-1">จัดการเนื้อหา รูปภาพ สิ่งอำนวยความสะดวก และรีวิวที่แสดงบนหน้า Home</p>
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
                        <input type="text" id="" name="about_title"
                               class="w-full border-2 border-gray-200 rounded-lg px-4 py-2.5 focus:border-orange-500 outline-none transition text-sm"
                               value="{{ old('about_title', $settings['about_title'] ?? '') }}"
                               placeholder="เช่น สนามที่ได้มาตรฐาน ระบบการจองที่ทันสมัย">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            รายละเอียด (About Description)
                        </label>
                        <textarea name="about_desc" id="" rows="3"
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
                                <img data-original-src="{{ $settings['about_img_'.$i] ?? '' }}" id="preview-about-{{ $i }}" src="{{ $settings['about_img_'.$i] ?? '' }}" class="h-40 w-full object-cover rounded-lg border border-gray-200">
                                <input type="file" id="" name="about_img_{{ $i }}_file" accept="image/*"
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
                            <input type="text" id="" name="promo_subtitle"
                                   class="w-full border-2 border-gray-200 rounded-lg px-4 py-2.5 focus:border-orange-500 outline-none transition text-sm"
                                   value="{{ old('promo_subtitle', $settings['promo_subtitle'] ?? '') }}"
                                   placeholder="เช่น อัปเดตโปรโมชั่นสุดพิเศษ">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                หัวข้อโปรโมชั่น (Title)
                            </label>
                            <input type="text" id="" name="promo_title"
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
                            <input type="text" id="" name="promo_card_title"
                                   class="w-full border-2 border-gray-200 rounded-lg px-4 py-2.5 focus:border-orange-500 outline-none transition text-sm"
                                   value="{{ old('promo_card_title', $settings['promo_card_title'] ?? '') }}"
                                   placeholder="เช่น BASKETBALL">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                คำอธิบายบนการ์ดโปรโมชั่น (Card Subtitle)
                            </label>
                            <input type="text" id="" name="promo_card_sub"
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
                                data-original-src="{{ $promoImg ?? '' }}"
                                class="h-40 w-full rounded-lg object-cover border-2 border-gray-200 shadow-sm">
                            <p class="text-xs text-center text-gray-400 mt-1">รูปปัจจุบัน</p>
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <input type="file" id="" name="promo_image_file" accept="image/*"
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-600 hover:file:bg-orange-100 cursor-pointer"
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
                                <img data-original-src="{{ $settings['hero_img_'.$i] ?? '' }}" id="preview-hero-{{ $i }}" src="{{ $settings['hero_img_'.$i] ?? '' }}" class="h-40 w-full object-cover rounded-lg border border-gray-200">
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
                                <img data-original-src="{{ $settings['courts_bg'] ?? '' }}" id="preview-courts" src="{{ $settings['courts_bg'] ?? '' }}" class="h-60 w-full object-cover rounded-lg border border-gray-200">
                                <input type="file" id="" name="courts_bg_file" accept="image/*"
                                       class="block w-full text-xs text-gray-500 mb-2 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-600 hover:file:bg-orange-100 cursor-pointer"
                                       onchange="previewImg(this, 'preview-courts')">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-2">ภาพ Community</label>
                                <img data-original-src="{{ $settings['community_img'] ?? '' }}" id="preview-community" src="{{ $settings['community_img'] ?? '' }}" class="h-60 w-full object-cover rounded-lg border border-gray-200">
                                <input type="file" id="" name="community_img_file" accept="image/*"
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

                        <img data-original-src="{{ $courtImgSrc }}" id="preview-court-{{ $court->id }}"
                             src="{{ $courtImgSrc }}"
                             class="h-32 w-full object-cover rounded-lg border border-gray-200 mb-3">

                        <input type="file" id="" name="court_images[{{ $court->id }}]" accept="image/*"
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

            {{-- ─── Section 5: Facilities ─── --}}
            <section id="facility-management" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 scroll-mt-24">
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-1">5. สิ่งอำนวยความสะดวก</h3>
                    <p class="text-xs text-gray-400">เพิ่มการ์ดใหม่พร้อมรูปภาพ หรือเปิด–ปิดการแสดงผลบนหน้า Home</p>
                </div>

                <form action="{{ route('admin.website.facilities.store') }}" method="POST" enctype="multipart/form-data"
                      class="rounded-xl border-2 border-dashed border-orange-200 bg-orange-50/50 p-5">
                    @csrf
                    <p class="mb-4 font-semibold text-gray-800">เพิ่มการ์ดใหม่</p>
                    @if($errors->facilityCreate->any())
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">กรุณาตรวจสอบข้อมูลที่กรอกให้ถูกต้อง</div>
                    @endif
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">ชื่อหัวข้อ</label>
                            <input type="text" name="name" value="{{ old('name') }}" required maxlength="100" placeholder="เช่น ที่จอดรถ"
                                   class="w-full rounded-lg border-2 border-gray-200 px-4 py-2.5 text-sm outline-none transition focus:border-orange-500">
                            @if($errors->facilityCreate->has('name'))<p class="mt-1 text-xs text-red-600">{{ $errors->facilityCreate->first('name') }}</p>@endif
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">ลำดับ</label>
                            <input type="number" name="sort_order" min="0" max="999" value="{{ old('sort_order', ($facilities->max('sort_order') ?? 0) + 1) }}"
                                   class="w-full rounded-lg border-2 border-gray-200 px-4 py-2.5 text-sm outline-none transition focus:border-orange-500">
                            @if($errors->facilityCreate->has('sort_order'))<p class="mt-1 text-xs text-red-600">{{ $errors->facilityCreate->first('sort_order') }}</p>@endif
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">รายละเอียด</label>
                            <textarea name="description" rows="2" maxlength="500" placeholder="รายละเอียดสั้นๆ ที่แสดงบนการ์ด"
                                      class="w-full rounded-lg border-2 border-gray-200 px-4 py-2.5 text-sm outline-none transition focus:border-orange-500">{{ old('description') }}</textarea>
                            @if($errors->facilityCreate->has('description'))<p class="mt-1 text-xs text-red-600">{{ $errors->facilityCreate->first('description') }}</p>@endif
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">รูปภาพ</label>
                            <input type="file" name="image" required accept="image/jpeg,image/png,image/webp"
                                   class="block w-full text-xs text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-orange-100 file:px-4 file:py-2 file:font-semibold file:text-orange-700 hover:file:bg-orange-200">
                            <p class="mt-1 text-[11px] text-gray-400">JPG, PNG หรือ WebP ขนาดไม่เกิน 5 MB</p>
                            @if($errors->facilityCreate->has('image'))<p class="mt-1 text-xs text-red-600">{{ $errors->facilityCreate->first('image') }}</p>@endif
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="rounded-lg bg-orange-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-600">เพิ่มการ์ด</button>
                    </div>
                </form>

                <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
                    @forelse($facilities as $facility)
                        <form action="{{ route('admin.website.facilities.update', $facility) }}" method="POST" enctype="multipart/form-data"
                              id="facility-{{ $facility->id }}" class="scroll-mt-24 overflow-hidden rounded-xl border border-gray-200">
                            @csrf
                            @method('PUT')
                            <img src="{{ $facility->image_url }}" alt="{{ $facility->name }}" class="h-40 w-full bg-gray-100 object-cover">
                            <div class="space-y-3 p-4">
                                @if($errors->getBag('facilityUpdate'.$facility->id)->any())
                                    <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">กรุณาตรวจสอบข้อมูลในการ์ดนี้</div>
                                @endif
                                <div class="flex items-center justify-between gap-3">
                                    <input type="text" name="name" value="{{ $errors->getBag('facilityUpdate'.$facility->id)->any() ? old('name') : $facility->name }}" required maxlength="100"
                                           class="min-w-0 flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold outline-none focus:border-orange-500">
                                    <label class="flex shrink-0 items-center gap-2 text-xs font-medium text-gray-600">
                                        <input type="checkbox" name="is_active" value="1" @checked($errors->getBag('facilityUpdate'.$facility->id)->any() ? old('is_active') : $facility->is_active) class="rounded border-gray-300 text-orange-500">
                                        แสดง
                                    </label>
                                </div>
                                @if($errors->getBag('facilityUpdate'.$facility->id)->has('name'))<p class="text-xs text-red-600">{{ $errors->getBag('facilityUpdate'.$facility->id)->first('name') }}</p>@endif
                                <textarea name="description" rows="2" maxlength="500"
                                          class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs outline-none focus:border-orange-500">{{ $errors->getBag('facilityUpdate'.$facility->id)->any() ? old('description') : $facility->description }}</textarea>
                                @if($errors->getBag('facilityUpdate'.$facility->id)->has('description'))<p class="text-xs text-red-600">{{ $errors->getBag('facilityUpdate'.$facility->id)->first('description') }}</p>@endif
                                <div class="grid grid-cols-[90px_1fr] gap-3">
                                    <div>
                                        <label class="mb-1 block text-xs text-gray-500">ลำดับ</label>
                                        <input type="number" name="sort_order" value="{{ $errors->getBag('facilityUpdate'.$facility->id)->any() ? old('sort_order') : $facility->sort_order }}" min="0" max="999" required
                                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs outline-none focus:border-orange-500">
                                        @if($errors->getBag('facilityUpdate'.$facility->id)->has('sort_order'))<p class="mt-1 text-xs text-red-600">{{ $errors->getBag('facilityUpdate'.$facility->id)->first('sort_order') }}</p>@endif
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs text-gray-500">เปลี่ยนรูป</label>
                                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                                               class="block w-full text-xs text-gray-500 file:mr-2 file:rounded file:border-0 file:bg-gray-100 file:px-2 file:py-2">
                                        @if($errors->getBag('facilityUpdate'.$facility->id)->has('image'))<p class="mt-1 text-xs text-red-600">{{ $errors->getBag('facilityUpdate'.$facility->id)->first('image') }}</p>@endif
                                    </div>
                                </div>
                                <button type="submit" class="w-full rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">บันทึกการ์ด</button>
                            </div>
                        </form>
                    @empty
                        <p class="text-sm text-gray-400">ยังไม่มีสิ่งอำนวยความสะดวก</p>
                    @endforelse
                </div>
            </section>

            {{-- ─── Section 6: Review moderation ─── --}}
            <section id="review-moderation" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 scroll-mt-24">
                <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-1">6. รีวิวจากสมาชิก</h3>
                        <p class="text-xs text-gray-400">รีวิวใหม่จะรอตรวจสอบ และจะแสดงบนหน้า Home หลัง Admin กดเผยแพร่เท่านั้น</p>
                    </div>
                    <div class="flex gap-2 text-xs">
                        <span class="rounded-full bg-amber-100 px-3 py-1 font-semibold text-amber-700">รอตรวจ {{ $reviews->where('status', 'pending')->count() }}</span>
                    </div>
                </div>

                <div class="space-y-4">
                    @forelse($reviews as $review)
                        <article class="rounded-xl border border-gray-200 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-gray-900">{{ $review->user->name }}</p>
                                        <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold
                                            {{ $review->status === 'published' ? 'bg-emerald-100 text-emerald-700' : ($review->status === 'hidden' ? 'bg-gray-100 text-gray-600' : 'bg-amber-100 text-amber-700') }}">
                                            {{ $review->status === 'published' ? 'เผยแพร่แล้ว' : ($review->status === 'hidden' ? 'ซ่อนอยู่' : 'รอตรวจสอบ') }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-400">{{ $review->user->email }} · {{ $review->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <div class="text-lg text-amber-400">{{ str_repeat('★', $review->overall_rating) }}<span class="text-gray-200">{{ str_repeat('★', 5 - $review->overall_rating) }}</span></div>
                            </div>

                            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $review->comment }}</p>

                            @if($review->ratings->isNotEmpty())
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($review->ratings as $rating)
                                        <span class="rounded-full bg-orange-50 px-3 py-1 text-xs text-orange-700">{{ $rating->facility->name }} {{ $rating->rating }}/5</span>
                                    @endforeach
                                </div>
                            @endif

                            @if($review->images->isNotEmpty())
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($review->images as $image)
                                        <a href="{{ $image->image_url }}" target="_blank" rel="noopener">
                                            <img src="{{ $image->image_url }}" alt="รูปประกอบรีวิว" class="h-20 w-24 rounded-lg border border-gray-200 object-cover">
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            <div class="mt-4 flex flex-wrap justify-end gap-2">
                                @if($review->status !== 'published')
                                    <form action="{{ route('admin.website.reviews.status', $review) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="published">
                                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700">เผยแพร่</button>
                                    </form>
                                @endif
                                @if($review->status !== 'hidden')
                                    <form action="{{ route('admin.website.reviews.status', $review) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="hidden">
                                        <button type="submit" class="rounded-lg bg-gray-700 px-4 py-2 text-xs font-semibold text-white hover:bg-gray-800">ซ่อนรีวิว</button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="rounded-xl bg-gray-50 px-5 py-10 text-center text-sm text-gray-400">ยังไม่มีรีวิวจากสมาชิก</div>
                    @endforelse
                </div>

                @if($reviews->hasPages())
                    <div class="mt-5">{{ $reviews->links() }}</div>
                @endif
            </section>

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
        submitButton.style.backgroundColor = '#16a34a';
        submitButton.onmouseenter = () => submitButton.style.backgroundColor = '#15803d';

        submitSuccessTimeoutId = setTimeout(() => {
            submitButton.innerHTML = defaultSubmitButtonHtml;
            submitButton.style.backgroundColor = '';
            submitButton.onmouseenter = null;
        }, 1800);
    };

    form.addEventListener('input', refreshStatus);
    form.addEventListener('change', refreshStatus);
    form.addEventListener('reset', () => {
        requestAnimationFrame(() => {
            // Revert every preview image in this form back to its original src
            form.querySelectorAll('img[data-original-src]').forEach(img => {
                img.src = img.dataset.originalSrc || '';
            });

            // Re-hide the promo banner wrapper if it originally had no image
            const previewWrap = form.querySelector('#img-preview-wrap');
            const promoImg = form.querySelector('#img-preview');
            if (previewWrap && promoImg) {
                previewWrap.classList.toggle('hidden', !promoImg.dataset.originalSrc);
            }

            refreshStatus();
        });
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

            // NEW: lock in the currently-shown previews as the new "original"
            // so a later "ยกเลิก" reverts to the last SAVED image, not the
            // image that was on the page at initial load.
            form.querySelectorAll('img[data-original-src]').forEach(img => {
                img.dataset.originalSrc = img.src;
            });

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
