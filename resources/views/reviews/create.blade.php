@extends('layouts.app')

@section('title', 'เขียนรีวิว - THATA Homecourt')

@section('content')
<div class="min-h-screen bg-[#f6f5f0] px-4 py-10 text-[#0d0f1e] sm:px-6">
    <div class="mx-auto max-w-4xl">
        <a href="{{ route('home') }}#facility-reviews" class="mb-5 inline-flex text-sm font-medium text-orange-600 hover:text-orange-700">← กลับหน้าแรก</a>

        <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="bg-[#13162a] px-6 py-8 text-white sm:px-10">
                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-orange-400">Share your experience</p>
                <h1 class="text-3xl font-bold sm:text-4xl">เขียนรีวิว THATA Homecourt</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-white/60">ให้คะแนนสถานที่และสิ่งอำนวยความสะดวกที่คุณได้ใช้ ความคิดเห็นของคุณช่วยให้เราพัฒนาบริการให้ดีขึ้น</p>
            </div>

            <form method="POST" action="{{ route('reviews.store') }}" enctype="multipart/form-data" class="space-y-8 p-6 sm:p-10" id="review-form">
                @csrf

                <div class="flex items-center gap-4 rounded-2xl bg-orange-50 px-5 py-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-orange-500 font-bold text-white">
                        {{ mb_substr($reviewer->name, 0, 1) }}
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">รีวิวโดย</p>
                        <p class="font-semibold text-gray-900">{{ $reviewer->name }}</p>
                        <p class="text-xs text-emerald-600">✓ สมาชิกที่เข้าสู่ระบบแล้ว</p>
                    </div>
                </div>

                @if($errors->any())
                    <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                        <p class="font-semibold">กรุณาตรวจสอบข้อมูล</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section>
                    <div class="mb-4">
                        <h2 class="text-lg font-bold">ประสบการณ์โดยรวม <span class="text-orange-500">*</span></h2>
                        <p class="text-sm text-gray-500">ให้คะแนนภาพรวมของสถานที่และการบริการ</p>
                    </div>
                    <input type="hidden" name="overall_rating" id="overall-rating" value="{{ old('overall_rating') }}">
                    <div class="flex flex-wrap items-center gap-2" data-star-group data-input="overall-rating">
                        @for($score = 1; $score <= 5; $score++)
                            <button type="button" data-score="{{ $score }}" class="star-button text-4xl leading-none text-gray-300 transition hover:scale-110" aria-label="ให้ {{ $score }} ดาว">★</button>
                        @endfor
                        <span class="ml-2 text-sm text-gray-500" data-rating-label>ยังไม่ได้ให้คะแนน</span>
                    </div>
                </section>

                <section>
                    <div class="mb-4">
                        <h2 class="text-lg font-bold">สิ่งที่คุณได้ใช้บริการ <span class="text-orange-500">*</span></h2>
                        <p class="text-sm text-gray-500">ให้คะแนนอย่างน้อย 1 รายการ รายการที่ไม่ได้ใช้ปล่อยว่างได้</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach($facilities as $facility)
                            @php($oldRating = old('ratings.'.$facility->id))
                            <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                                @if($facility->image_url)
                                    <img src="{{ $facility->image_url }}" alt="{{ $facility->name }}" class="h-36 w-full object-cover">
                                @endif
                                <div class="p-4">
                                    <h3 class="font-semibold">{{ $facility->name }}</h3>
                                    @if($facility->description)
                                        <p class="mt-1 line-clamp-2 text-xs leading-5 text-gray-500">{{ $facility->description }}</p>
                                    @endif
                                    <input type="hidden" name="ratings[{{ $facility->id }}]" id="facility-rating-{{ $facility->id }}" value="{{ $oldRating }}" disabled>
                                    <div class="mt-3 flex items-center gap-1" data-star-group data-input="facility-rating-{{ $facility->id }}" data-optional="true">
                                        @for($score = 1; $score <= 5; $score++)
                                            <button type="button" data-score="{{ $score }}" class="star-button text-2xl leading-none text-gray-300 transition hover:scale-110" aria-label="ให้ {{ $facility->name }} {{ $score }} ดาว">★</button>
                                        @endfor
                                        <button type="button" data-clear-rating class="ml-auto text-xs text-gray-400 hover:text-gray-600">ไม่ได้ใช้</button>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section>
                    <label for="comment" class="mb-2 block text-lg font-bold">ความคิดเห็น <span class="text-orange-500">*</span></label>
                    <textarea id="comment" name="comment" rows="5" maxlength="1000" required
                              class="w-full rounded-2xl border-2 border-gray-200 px-4 py-3 text-sm outline-none transition focus:border-orange-500"
                              placeholder="เล่าประสบการณ์ของคุณ เช่น คาเฟ่บริการดี ห้องน้ำสะอาด หรือพื้นที่นั่งพักสะดวก...">{{ old('comment') }}</textarea>
                    <p class="mt-1 text-right text-xs text-gray-400"><span id="comment-count">0</span>/1000</p>
                </section>

                <section>
                    <label class="mb-2 block text-lg font-bold">รูปภาพประกอบ <span class="text-sm font-normal text-gray-400">(ไม่บังคับ)</span></label>
                    <label for="review-images" class="flex cursor-pointer items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 px-5 py-7 text-center transition hover:border-orange-400 hover:bg-orange-50">
                        <span><strong class="block text-sm text-gray-700">＋ เลือกรูปภาพ</strong><span class="mt-1 block text-xs text-gray-400">JPG, PNG หรือ WebP · สูงสุด 3 รูป · รูปละไม่เกิน 5 MB</span></span>
                    </label>
                    <input id="review-images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple class="sr-only">
                    <div id="image-previews" class="mt-3 grid grid-cols-3 gap-3"></div>
                    <p id="image-error" class="mt-2 hidden text-sm text-red-600"></p>
                </section>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-6 sm:flex-row sm:justify-end">
                    <a href="{{ route('home') }}#facility-reviews" class="rounded-xl border border-gray-300 px-6 py-3 text-center text-sm font-semibold text-gray-600 hover:bg-gray-50">ยกเลิก</a>
                    <button type="submit" class="rounded-xl bg-orange-500 px-7 py-3 text-sm font-semibold text-white transition hover:bg-orange-600">ส่งรีวิว</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const labels = ['', 'แย่มาก', 'ควรปรับปรุง', 'พอใช้', 'ดี', 'ดีมาก'];

    document.querySelectorAll('[data-star-group]').forEach(group => {
        const input = document.getElementById(group.dataset.input);
        const stars = Array.from(group.querySelectorAll('[data-score]'));
        const label = group.querySelector('[data-rating-label]');

        const render = value => {
            stars.forEach(star => {
                const active = Number(star.dataset.score) <= value;
                star.classList.toggle('text-amber-400', active);
                star.classList.toggle('text-gray-300', !active);
            });
            if (label) label.textContent = value ? `${value} ดาว · ${labels[value]}` : 'ยังไม่ได้ให้คะแนน';
        };

        stars.forEach(star => star.addEventListener('click', () => {
            input.disabled = false;
            input.value = star.dataset.score;
            render(Number(star.dataset.score));
        }));

        const clear = group.querySelector('[data-clear-rating]');
        if (clear) clear.addEventListener('click', () => {
            input.value = '';
            input.disabled = true;
            render(0);
        });

        if (input.value) {
            input.disabled = false;
            render(Number(input.value));
        }
    });

    const comment = document.getElementById('comment');
    const count = document.getElementById('comment-count');
    const updateCount = () => count.textContent = comment.value.length;
    comment.addEventListener('input', updateCount);
    updateCount();

    const imageInput = document.getElementById('review-images');
    const previews = document.getElementById('image-previews');
    const imageError = document.getElementById('image-error');

    imageInput.addEventListener('change', () => {
        previews.innerHTML = '';
        imageError.classList.add('hidden');
        const files = Array.from(imageInput.files || []);

        if (files.length > 3) {
            imageError.textContent = 'เลือกได้สูงสุด 3 รูป';
            imageError.classList.remove('hidden');
            imageInput.value = '';
            return;
        }

        for (const file of files) {
            if (file.size > 5 * 1024 * 1024) {
                imageError.textContent = `รูป ${file.name} มีขนาดเกิน 5 MB`;
                imageError.classList.remove('hidden');
                imageInput.value = '';
                previews.innerHTML = '';
                return;
            }
            const image = document.createElement('img');
            image.src = URL.createObjectURL(file);
            image.alt = 'ตัวอย่างรูปรีวิว';
            image.className = 'aspect-[4/3] w-full rounded-xl object-cover';
            previews.appendChild(image);
        }
    });
});
</script>
@endpush
