<section id="facility-reviews" class="facilities-section" data-aos="fade-up">
    <div class="facilities-head">
        <div>
            <p class="facilities-label">More Than Basketball</p>
            <h2 class="facilities-title">ครบทุกช่วงเวลา<br>ทั้งในและนอกสนาม</h2>
            <p class="facilities-sub">แวะพัก เติมพลัง ช้อปอุปกรณ์ พร้อมสิ่งอำนวยความสะดวกครบในที่เดียว</p>
        </div>
        @if(($facilities ?? collect())->count() > 1)
            <div class="slider-actions">
                <button type="button" class="slider-btn" data-slider="facility-track" data-direction="-1" aria-label="เลื่อนสิ่งอำนวยความสะดวกไปทางซ้าย">←</button>
                <button type="button" class="slider-btn" data-slider="facility-track" data-direction="1" aria-label="เลื่อนสิ่งอำนวยความสะดวกไปทางขวา">→</button>
            </div>
        @endif
    </div>

    <div id="facility-track" class="facility-track">
        @forelse(($facilities ?? collect()) as $facility)
            <article class="facility-card">
                @if($facility->image_url)
                    <img src="{{ $facility->image_url }}" alt="{{ $facility->name }}" loading="lazy" {!! $imageFallback !!}>
                @endif
                <span class="facility-index">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                <div class="facility-card-body">
                    <h3 class="facility-card-title">{{ $facility->name }}</h3>
                    @if($facility->description)
                        <p class="facility-card-desc">{{ $facility->description }}</p>
                    @endif
                    <div class="facility-score">
                        @php
                            $facilityStars = (int) round((float) $facility->average_rating);
                        @endphp
                        <span class="facility-stars" aria-label="{{ $facilityStars }} จาก 5 ดาว">
                            {{ str_repeat('★', $facilityStars) }}<span style="color:#cbd5e1">{{ str_repeat('★', 5 - $facilityStars) }}</span>
                        </span>
                        @if($facility->ratings_count > 0)
                            <strong>{{ number_format((float) $facility->average_rating, 1) }}</strong>
                            <small>{{ $facility->ratings_count }} รีวิว</small>
                        @else
                            <small>ยังไม่มีคะแนน</small>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-xl bg-white p-8 text-sm text-gray-400">ยังไม่มีข้อมูลสิ่งอำนวยความสะดวก</div>
        @endforelse
    </div>

    @include('partials.home-reviews')
</section>
