<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoursePackage extends Model
{
    protected $fillable = [
        'course_id',
        'course_type_id',
        'total_sessions',
        'total_price',
        'price_per_session',
        'validity_value',
        'validity_unit',
        'recommendation_text',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'price_per_session' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function courseType(): BelongsTo
    {
        return $this->belongsTo(CourseType::class);
    }

    public function getPackageTypeLabelAttribute(): string
    {
        return $this->courseType?->name ?? 'ยังไม่กำหนดประเภทแพ็กเกจ';
    }

    public function getValidityLabelAttribute(): string
    {
        $unit = $this->validity_unit === 'hours' ? 'ชั่วโมง' : 'วัน';

        return "{$this->validity_value} {$unit}";
    }
}
