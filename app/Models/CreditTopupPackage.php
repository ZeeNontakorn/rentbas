<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditTopupPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'label', 'price_satang', 'credit_satang', 'expiry_days', 'is_active', 'sort_order','promptpay_number'
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function requests(): HasMany
    {
        return $this->hasMany(CreditTopupRequest::class);
    }

    /**
     * เครดิตโบนัสที่ผู้ใช้ได้เพิ่มจากราคาที่จ่ายจริง (หน่วยสตางค์) — ใช้แสดง badge โปรโมชั่น
     */
    public function getBonusSatangAttribute(): int
    {
        return max(0, (int) $this->credit_satang - (int) $this->price_satang);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
