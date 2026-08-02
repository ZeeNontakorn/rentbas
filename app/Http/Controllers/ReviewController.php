<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\PrivateTrainingBooking;
use App\Models\Review;
use App\Models\ReviewScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * รีวิวได้เฉพาะการจองที่ "อนุมัติแล้ว" เท่านั้น — สถานะอื่น (ถูกปฏิเสธ/ยกเลิก/หมดเวลา)
     * แปลว่าไม่เคยได้ใช้บริการจริง จึงไม่ควรมีสิทธิ์ให้คะแนน
     */
    private const REVIEWABLE_BOOKING_STATUS = 'approved';

    /** สถานะของคาบ Private ที่ถือว่าได้เรียนจริง */
    private const REVIEWABLE_PRIVATE_STATUSES = ['confirmed', 'approved'];

    // ============================================================
    // รีวิวสถานที่ (5 หมวด) — ผูกกับการจองสนาม
    // ============================================================

    public function create(Request $request, Booking $booking)
    {
        $this->assertOwnsBooking($request, $booking);

        if ($reason = $this->bookingBlockReason($booking)) {
            return redirect()->route('history')->with('error', $reason);
        }

        $booking->load('court');

        return view('reviews.create', [
            'booking' => $booking,
            'categories' => ReviewScore::FACILITY_CATEGORIES,
        ]);
    }

    public function store(Request $request, Booking $booking)
    {
        $this->assertOwnsBooking($request, $booking);

        if ($reason = $this->bookingBlockReason($booking)) {
            return redirect()->route('history')->with('error', $reason);
        }

        $data = $request->validate(
            $this->scoreRules(array_keys(ReviewScore::FACILITY_CATEGORIES)),
            $this->scoreMessages(ReviewScore::FACILITY_CATEGORIES)
        );

        DB::transaction(function () use ($request, $booking, $data) {
            $review = Review::create([
                'user_id' => $request->user()->id,
                'booking_id' => $booking->id,
                'comment' => $data['comment'] ?? null,
            ]);

            foreach ($data['scores'] as $category => $score) {
                $review->scores()->create([
                    'category' => $category,
                    'score' => $score,
                ]);
            }
        });

        return redirect()->route('history')->with('success', 'ขอบคุณสำหรับรีวิว 🙏');
    }

    // ============================================================
    // รีวิวโค้ช (หมวดที่ 6) — ผูกกับคาบ Private Training
    // ============================================================

    public function createCoach(Request $request, PrivateTrainingBooking $privateTrainingBooking)
    {
        $this->assertOwnsPrivateBooking($request, $privateTrainingBooking);

        if ($reason = $this->privateBlockReason($privateTrainingBooking)) {
            return redirect()->route('private-training.index')->with('error', $reason);
        }

        $privateTrainingBooking->load('coach.staffProfile');

        return view('reviews.create-coach', [
            'booking' => $privateTrainingBooking,
            'coach' => $privateTrainingBooking->coach,
        ]);
    }

    public function storeCoach(Request $request, PrivateTrainingBooking $privateTrainingBooking)
    {
        $this->assertOwnsPrivateBooking($request, $privateTrainingBooking);

        if ($reason = $this->privateBlockReason($privateTrainingBooking)) {
            return redirect()->route('private-training.index')->with('error', $reason);
        }

        $data = $request->validate(
            $this->scoreRules([ReviewScore::COACH_CATEGORY]),
            $this->scoreMessages([ReviewScore::COACH_CATEGORY => 'โค้ชผู้สอน Private'])
        );

        DB::transaction(function () use ($request, $privateTrainingBooking, $data) {
            $review = Review::create([
                'user_id' => $request->user()->id,
                'private_training_booking_id' => $privateTrainingBooking->id,
                'coach_id' => $privateTrainingBooking->coach_id,
                'comment' => $data['comment'] ?? null,
            ]);

            $review->scores()->create([
                'category' => ReviewScore::COACH_CATEGORY,
                'score' => $data['scores'][ReviewScore::COACH_CATEGORY],
            ]);
        });

        return redirect()->route('private-training.index')->with('success', 'ขอบคุณสำหรับรีวิวโค้ช 🙏');
    }

    // ============================================================
    // หน้ารวมรีวิว
    // ============================================================

    /** รีวิวทั้งหมดที่ user คนนี้เคยเขียน */
    public function index(Request $request)
    {
        $reviews = Review::with(['scores', 'booking.court', 'coach', 'privateTrainingBooking'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return view('reviews.index', compact('reviews'));
    }

    /**
     * โค้ชดูคะแนนที่ตัวเองได้รับ
     * ไม่โหลดความสัมพันธ์ user มาด้วยโดยตั้งใจ — โค้ชต้องไม่เห็นว่าใครเป็นคนรีวิว (anonymous)
     */
    public function myCoachReviews(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user->role === 'staff' && $user->membership_type === 'coach',
            404,
            'ไม่พบข้อมูลโค้ช'
        );

        $reviews = Review::with('scores')
            ->where('coach_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return view('reviews.coach-reviews', [
            'reviews' => $reviews,
            'average' => $this->averageOf($reviews),
        ]);
    }

    // ============================================================
    // แจ้งเตือนชวนรีวิว
    // ============================================================

    /**
     * ชวนให้รีวิวการจองสนามที่เพิ่งใช้บริการจบ
     *
     * ทำแบบ opportunistic ตอนผู้ใช้เปิดหน้าประวัติ (แนวเดียวกับ CheckoutController::releaseStaleLocks())
     * เพื่อไม่ต้องพึ่ง scheduler/cron ซึ่งโปรเจกต์นี้ยังไม่ได้ตั้งไว้
     * คอลัมน์ review_invited_at เป็นตัวกันไม่ให้ยิงซ้ำทุกครั้งที่เปิดหน้า
     */
    public static function inviteForFinishedBookings(int $userId): void
    {
        $bookings = Booking::with('court')
            ->where('user_id', $userId)
            ->where('status', self::REVIEWABLE_BOOKING_STATUS)
            ->whereNull('review_invited_at')
            ->whereDate('booking_date', '<=', now()->toDateString())
            ->whereDoesntHave('review')
            ->get()
            ->filter->hasFinished();

        foreach ($bookings as $booking) {
            Notification::create([
                'user_id' => $userId,
                'title' => 'ให้คะแนนการใช้บริการ',
                'message' => 'คุณใช้บริการ '.($booking->court->name ?? 'สนาม').' วันที่ '
                    .$booking->booking_date->format('d/m/Y').' เสร็จแล้ว — ให้คะแนนหน่อยได้ไหม?',
                'is_read' => false,
            ]);

            self::stampInvited($booking);
        }
    }

    /** ชวนให้รีวิวโค้ชหลังเรียนคาบ Private จบ */
    public static function inviteForFinishedPrivateBookings(int $userId): void
    {
        $bookings = PrivateTrainingBooking::with('coach')
            ->where('user_id', $userId)
            ->whereIn('status', self::REVIEWABLE_PRIVATE_STATUSES)
            ->whereNull('review_invited_at')
            ->whereDate('date', '<=', now()->toDateString())
            ->whereDoesntHave('review')
            ->get()
            ->filter->hasFinished();

        foreach ($bookings as $booking) {
            Notification::create([
                'user_id' => $userId,
                'title' => 'ให้คะแนนโค้ช',
                'message' => 'คุณเรียนกับโค้ช '.($booking->coach->name ?? '-').' วันที่ '
                    .$booking->date->format('d/m/Y').' จบแล้ว — ให้คะแนนโค้ชหน่อยได้ไหม?',
                'is_read' => false,
            ]);

            self::stampInvited($booking);
        }
    }

    /**
     * ปิด timestamps ชั่วคราวตอนบันทึก review_invited_at
     * เพราะถ้าปล่อยให้ updated_at เปลี่ยน ลำดับในหน้าประวัติ (เรียงตาม updated_at) จะสลับทุกครั้งที่เปิดหน้า
     */
    private static function stampInvited($booking): void
    {
        $booking->timestamps = false;
        $booking->review_invited_at = now();
        $booking->save();
    }

    // ============================================================
    // Helpers
    // ============================================================

    private function assertOwnsBooking(Request $request, Booking $booking): void
    {
        // ใช้ 404 ไม่ใช่ 403 เพื่อไม่ให้คนอื่นเดาได้ว่า booking id ไหนมีอยู่จริง
        abort_unless($booking->user_id === $request->user()->id, 404);
    }

    private function assertOwnsPrivateBooking(Request $request, PrivateTrainingBooking $booking): void
    {
        abort_unless($booking->user_id === $request->user()->id, 404);
    }

    /** คืนข้อความเหตุผลถ้ารีวิวไม่ได้ / คืน null ถ้ารีวิวได้ */
    private function bookingBlockReason(Booking $booking): ?string
    {
        if ($booking->status !== self::REVIEWABLE_BOOKING_STATUS) {
            return 'ให้คะแนนได้เฉพาะการจองที่ได้รับอนุมัติแล้วเท่านั้น';
        }

        if (! $booking->hasFinished()) {
            return 'ให้คะแนนได้หลังใช้บริการเสร็จแล้วเท่านั้น';
        }

        if ($booking->review()->exists()) {
            return 'คุณให้คะแนนการจองนี้ไปแล้ว';
        }

        return null;
    }

    private function privateBlockReason(PrivateTrainingBooking $booking): ?string
    {
        if (! in_array($booking->status, self::REVIEWABLE_PRIVATE_STATUSES, true)) {
            return 'ให้คะแนนได้เฉพาะคาบเรียนที่ยืนยันแล้วเท่านั้น';
        }

        if (! $booking->hasFinished()) {
            return 'ให้คะแนนโค้ชได้หลังเรียนจบคาบแล้วเท่านั้น';
        }

        if ($booking->review()->exists()) {
            return 'คุณให้คะแนนคาบเรียนนี้ไปแล้ว';
        }

        return null;
    }

    /** สร้าง validation rules ให้ครบทุกหมวดที่ส่งเข้ามา */
    private function scoreRules(array $categories): array
    {
        $rules = [
            'scores' => ['required', 'array'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];

        foreach ($categories as $category) {
            $rules["scores.{$category}"] = ['required', 'integer', 'between:1,5'];
        }

        return $rules;
    }

    /** ข้อความ error ภาษาไทยรายหมวด */
    private function scoreMessages(array $categories): array
    {
        $messages = [
            'scores.required' => 'กรุณาให้คะแนนอย่างน้อย 1 หมวด',
            'comment.max' => 'ความคิดเห็นต้องไม่เกิน 1,000 ตัวอักษร',
        ];

        foreach ($categories as $category => $label) {
            $messages["scores.{$category}.required"] = "กรุณาให้คะแนน{$label}";
            $messages["scores.{$category}.integer"] = "คะแนน{$label}ไม่ถูกต้อง";
            $messages["scores.{$category}.between"] = "คะแนน{$label}ต้องอยู่ระหว่าง 1-5 ดาว";
        }

        return $messages;
    }

    /** ค่าเฉลี่ยรวมของคอลเลกชันรีวิว (ต้อง eager load scores มาก่อน) */
    private function averageOf($reviews): float
    {
        $scores = $reviews->flatMap->scores;

        return $scores->isEmpty() ? 0.0 : round((float) $scores->avg('score'), 1);
    }
}
