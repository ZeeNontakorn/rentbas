<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PromotionPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'label', 'category', 'court_type', 'available_days',
        'available_start_time', 'available_end_time', 'duration_hours', 'max_people',
        'base_price', 'holiday_price', 'weekend_special_price',
        'weekend_special_start', 'weekend_special_end',
        'requires_verification', 'session_count', 'validity_days', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'available_days' => 'array',
            'requires_verification' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public static function generateCodeFromLabel(string $label, ?string $existingCode = null): string
    {
        $base = trim((string) Str::of($label ?? '')
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9]+/', '-')
            ->trim('-')
            ->lower());

        $base = $base !== '' ? $base : 'package';
        $candidate = $base;
        $count = 1;

        while (self::query()->where('code', $candidate)
            ->when($existingCode !== null && $existingCode !== '', fn ($query) => $query->whereKeyNot($existingCode))
            ->exists()) {
            $candidate = $base . '-' . $count;
            $count++;
        }

        return $candidate;
    }
}
