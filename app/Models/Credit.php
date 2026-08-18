<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Credit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'amount_satang', 'remaining_satang', 'expires_at',
        'source', 'credit_topup_request_id', 'admin_id', 'note',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topupRequest(): BelongsTo
    {
        return $this->belongsTo(CreditTopupRequest::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * ก้อนที่ยังใช้ได้: เหลือยอด > 0 และยังไม่หมดอายุ (หรือไม่มีวันหมดอายุ)
     */
    public function scopeValid($query)
    {
        return $query->where('remaining_satang', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    /**
     * เรียงก้อนที่ใกล้หมดอายุที่สุดขึ้นก่อน สำหรับหักเครดิตแบบ FIFO by expiry
     * (ก้อนที่ไม่มีวันหมดอายุ = expires_at null จะถูกเรียงไว้ท้ายสุด)
     */
    public function scopeExpiryOrder($query)
    {
        return $query->orderByRaw('expires_at IS NULL, expires_at ASC');
    }
}
