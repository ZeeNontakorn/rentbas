<div id="member-reviews" class="reviews-wrap" data-aos="fade-up">
    <div class="reviews-head">
        <div class="reviews-heading-row">
            <div>
                <p class="reviews-label">Member Experiences</p>
                <h2 class="reviews-title">เสียงจากสมาชิก THATA</h2>
                <p class="reviews-summary">ประสบการณ์จริงจากสมาชิกที่มาใช้บริการ</p>
            </div>
            @if(($reviewSummary['count'] ?? 0) > 0)
                @php
                    $summaryStars = (int) round((float) $reviewSummary['average']);
                @endphp
                <div class="reviews-score">
                    <strong>{{ number_format($reviewSummary['average'], 1) }}</strong>
                    <div>
                        <div class="reviews-score-stars">{{ str_repeat('★', $summaryStars) }}<span style="color:#e4ddd7">{{ str_repeat('★', 5 - $summaryStars) }}</span></div>
                        <small>จาก {{ $reviewSummary['count'] }} รีวิว</small>
                    </div>
                </div>
            @endif
        </div>
        <div class="slider-actions">
            @auth
                <a href="{{ route('reviews.create') }}" class="write-review-btn"><span aria-hidden="true">✦</span> เขียนรีวิว</a>
            @endauth
            @if(($reviews ?? collect())->count() > 1)
                <button type="button" class="slider-btn" data-slider="review-track" data-direction="-1" aria-label="เลื่อนรีวิวไปทางซ้าย">←</button>
                <button type="button" class="slider-btn" data-slider="review-track" data-direction="1" aria-label="เลื่อนรีวิวไปทางขวา">→</button>
            @endif
        </div>
    </div>

    <div id="review-track" class="review-track">
        @forelse(($reviews ?? collect()) as $review)
            @php
                // เช็คว่ามีรูปใน User หรือรูปจากโปรไฟล์พนักงาน/โค้ชหรือไม่ (แพทเทิร์นเดียวกับ admin/users/show.blade.php)
                $reviewerAvatarUrl = null;
                if (!empty($review->user->avatar) && \Storage::disk('public')->exists($review->user->avatar)) {
                    $reviewerAvatarUrl = asset('storage/' . $review->user->avatar);
                } elseif (!empty($review->user->staffProfile?->profile_image)) {
                    $reviewerAvatarUrl = $review->user->staffProfile->profile_image_url;
                }
            @endphp
            <article class="review-card">
                <div class="review-user-row">
                    <div class="review-user">
                        <div class="review-avatar">
                            @if(!empty($reviewerAvatarUrl))
                                <img src="{{ $reviewerAvatarUrl }}" alt="{{ $review->user->us_name }}">
                            @else
                                {{ mb_substr($review->user->us_name, 0, 1) }}
                            @endif
                        </div>
                        <div class="min-w-0">
                            <div class="review-name">{{ $review->user->us_name }}</div>
                            <div class="review-member">✓ สมาชิก THATA</div>
                        </div>
                    </div>
                    <div class="review-stars">{{ str_repeat('★', $review->overall_rating) }}<span style="color:#e4ddd7">{{ str_repeat('★', 5 - $review->overall_rating) }}</span></div>
                </div>

                <p class="review-comment">{{ $review->comment }}</p>

                @if($review->ratings->isNotEmpty())
                    <div class="review-ratings">
                        @foreach($review->ratings->take(4) as $rating)
                            <span class="review-rating-chip">{{ $rating->facility->name }} {{ $rating->rating }}/5</span>
                        @endforeach
                    </div>
                @endif

                @if($review->images->isNotEmpty())
                    <div class="review-images">
                        @foreach($review->images->take(3) as $image)
                            <a href="{{ $image->image_url }}" target="_blank" rel="noopener">
                                <img src="{{ $image->image_url }}" alt="รูปประกอบรีวิวของ {{ $review->user->us_name }}" loading="lazy">
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="review-footer">
                    <span>รีวิวโดยสมาชิก</span>
                    <span>{{ ($review->published_at ?? $review->created_at)->diffForHumans() }}</span>
                </div>
            </article>
        @empty
            <div class="reviews-empty">ยังไม่มีรีวิวที่เผยแพร่ @auth คุณสามารถเป็นคนแรกที่แบ่งปันประสบการณ์ได้ @endauth</div>
        @endforelse
    </div>
</div>
