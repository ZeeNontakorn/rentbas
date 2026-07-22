<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'label', 'category', 'duration_hours', 'max_people',
        'base_price', 'holiday_price', 'weekend_special_price',
        'weekend_special_start', 'weekend_special_end',
        'requires_verification', 'session_count', 'validity_days', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_verification' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
