<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_id',
        'private_training_booking_id',
        'coach_id',
        'comment',
    ];

    public function scores(): HasMany
    {
        return $this->hasMany(ReviewScore::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** โค้ชที่ถูกรีวิว (null ถ้าเป็นรีวิวสถานที่) */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function privateTrainingBooking(): BelongsTo
    {
        return $this->belongsTo(PrivateTrainingBooking::class);
    }

    /** true = รีวิวโค้ช, false = รีวิวสถานที่ */
    public function isCoachReview(): bool
    {
        return $this->coach_id !== null;
    }

    /**
     * คะแนนของรีวิวนี้ในรูป ['court' => 5, 'cafe' => 4, ...]
     * ต้อง eager load scores มาก่อนถึงจะไม่ยิง query เพิ่ม
     */
    public function scoreMap(): array
    {
        return $this->scores->pluck('score', 'category')->all();
    }

    /** ค่าเฉลี่ยของทุกหมวดในรีวิวครั้งนี้ */
    public function averageScore(): float
    {
        return round((float) $this->scores->avg('score'), 1);
    }
}
