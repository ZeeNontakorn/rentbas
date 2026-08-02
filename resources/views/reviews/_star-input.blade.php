{{--
    ตัวเลือกดาว 1-5 ของหนึ่งหมวด
    ใช้: @include('reviews._star-input', ['category' => 'court', 'label' => 'สนามบาส'])

    ค่าจริงส่งผ่าน hidden input ชื่อ scores[<category>] ปุ่มดาวเป็นแค่ตัวควบคุม
    เพื่อให้ยังส่งฟอร์มได้ตามปกติโดยไม่ต้องพึ่ง JS ฝั่ง submit
--}}
<div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between"
     x-data="{ score: {{ (int) old("scores.$category", 0) }}, hover: 0 }">

    <span class="text-sm font-medium text-gray-700">{{ $label }}</span>

    <div class="flex items-center gap-2">
        <input type="hidden" name="scores[{{ $category }}]" :value="score">

        <div class="flex items-center gap-1" @mouseleave="hover = 0">
            <template x-for="i in 5" :key="i">
                <button type="button"
                        @click="score = i"
                        @mouseenter="hover = i"
                        :aria-label="`ให้ ${i} ดาว`"
                        class="transition-transform hover:scale-110 focus:outline-none">
                    <svg class="h-7 w-7"
                         :class="(hover || score) >= i ? 'text-yellow-400' : 'text-gray-300'"
                         fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.362-1.118l-3.977-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                </button>
            </template>
        </div>

        <span class="w-10 text-right text-sm font-semibold text-gray-500"
              x-text="score ? score + '.0' : '-'"></span>
    </div>
</div>

@error("scores.$category")
    <div class="form-error">{{ $message }}</div>
@enderror
