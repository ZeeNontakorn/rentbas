<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewScore;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category');
        $days = (int) $request->query('days', 0);

        $query = Review::with(['scores', 'user:id,name,email', 'coach:id,name', 'booking.court'])
            ->orderByDesc('created_at');

        // กรองตามหมวด — ดูเฉพาะรีวิวที่มีคะแนนของหมวดนั้น
        if ($category && array_key_exists($category, ReviewScore::allCategories())) {
            $query->whereHas('scores', fn ($q) => $q->where('category', $category));
        }

        if ($days > 0) {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $reviews = $query->get();

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'category' => $category,
            'days' => $days,
            'categories' => ReviewScore::allCategories(),
            'averages' => self::averagesByCategory(),
            'overallAverage' => self::overallAverage(),
        ]);
    }

    /**
     * ค่าเฉลี่ยรายหมวดของทั้งระบบ ในรูป ['court' => ['avg' => 4.2, 'count' => 10], ...]
     * ใช้ทั้งหน้านี้และการ์ดสรุปบน dashboard
     */
    public static function averagesByCategory(): array
    {
        $grouped = ReviewScore::get(['category', 'score'])->groupBy('category');

        $result = [];
        foreach (ReviewScore::allCategories() as $key => $label) {
            $rows = $grouped->get($key);
            $result[$key] = [
                'label' => $label,
                'avg' => $rows ? round((float) $rows->avg('score'), 1) : 0.0,
                'count' => $rows ? $rows->count() : 0,
            ];
        }

        return $result;
    }

    /** ค่าเฉลี่ยรวมทุกหมวด */
    public static function overallAverage(): float
    {
        $scores = ReviewScore::get(['score']);

        return $scores->isEmpty() ? 0.0 : round((float) $scores->avg('score'), 1);
    }
}
