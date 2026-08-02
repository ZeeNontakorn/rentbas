<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'category',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
        ];
    }

    // 5 หมวดของการรีวิวสถานที่ ตาม requirement ข้อ 1.4
    const FACILITY_CATEGORIES = [
        'court' => 'สนามบาส',
        'cafe' => 'คาเฟ่',
        'restroom' => 'ห้องน้ำ',
        'service' => 'การบริการ',
        'cleanliness' => 'ความสะอาด',
    ];

    // หมวดที่ 6 — ให้คะแนนโค้ชผู้สอน Private แยกเส้นทางกับ 5 หมวดข้างบน
    const COACH_CATEGORY = 'coach';

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /** ป้ายไทยของทุกหมวดรวมโค้ช ใช้ตอนแสดงผลฝั่ง admin */
    public static function allCategories(): array
    {
        return self::FACILITY_CATEGORIES + [self::COACH_CATEGORY => 'โค้ชผู้สอน Private'];
    }

    public static function label(string $category): string
    {
        return self::allCategories()[$category] ?? $category;
    }
}
