<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Notification;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function create(Request $request)
    {
        $facilities = Facility::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('reviews.create', [
            'facilities' => $facilities,
            'reviewer' => $request->user(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'overall_rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:1000'],
            'ratings' => ['required', 'array', 'min:1'],
            'ratings.*' => ['required', 'integer', 'between:1,5'],
            'images' => ['nullable', 'array', 'max:3'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'overall_rating.required' => 'กรุณาให้คะแนนประสบการณ์โดยรวม',
            'comment.required' => 'กรุณาเขียนความคิดเห็น',
            'comment.min' => 'ความคิดเห็นต้องมีอย่างน้อย 10 ตัวอักษร',
            'ratings.required' => 'กรุณาให้คะแนนสิ่งอำนวยความสะดวกอย่างน้อย 1 รายการ',
            'ratings.min' => 'กรุณาให้คะแนนสิ่งอำนวยความสะดวกอย่างน้อย 1 รายการ',
            'images.max' => 'แนบรูปภาพได้สูงสุด 3 รูป',
            'images.*.max' => 'รูปภาพแต่ละรูปต้องมีขนาดไม่เกิน 5 MB',
        ]);

        $facilityIds = Facility::where('is_active', true)
            ->whereIn('id', array_keys($data['ratings']))
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if (count($facilityIds) !== count($data['ratings'])) {
            throw ValidationException::withMessages([
                'ratings' => 'มีหัวข้อรีวิวที่ไม่พร้อมใช้งาน กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง',
            ]);
        }

        $review = DB::transaction(function () use ($request, $data) {
            $review = Review::create([
                'user_id' => $request->user()->id,
                'overall_rating' => $data['overall_rating'],
                'comment' => $data['comment'],
                'status' => 'published', // ให้รีวิวแสดงผลทันที แต่ยังไม่แจ้งเตือนแอดมิน
                'published_at' => now(),
            ]);

            foreach ($data['ratings'] as $facilityId => $rating) {
                $review->ratings()->create([
                    'facility_id' => $facilityId,
                    'rating' => $rating,
                ]);
            }

            foreach ($request->file('images', []) as $index => $image) {
                $review->images()->create([
                    'image_path' => $image->store('reviews', 'public'),
                    'sort_order' => $index,
                ]);
            }

            return $review;
        });

        User::whereIn('role', ['admin', 'superadmin'])
            ->pluck('id')
            ->each(function ($adminId) use ($request, $review) {
                Notification::create([
                    'user_id' => $adminId,
                    'title' => 'มีรีวิวใหม่เข้ามา',
                    'message' => "รีวิว #{$review->id} จาก {$request->user()->name} | คะแนนรวม {$review->overall_rating}/5",
                ]);
            });

        return redirect()->to(route('home').'#member-reviews')
            ->with('success', 'ส่งรีวิวสำเร็จ');
    }
}
