<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_name',
        'course_type',
        'min_age',
        'max_age',
        'description',
        'image_path',
    ];

    protected $casts = [
        'min_age' => 'integer',
        'max_age' => 'integer',
    ];

    // ------- Relations -------

    public function targetGroups(): HasMany
    {
        return $this->hasMany(CourseTargetGroup::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(CourseSchedule::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(CoursePackage::class);
    }

    // ------- Accessors -------

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    // "6 ปีขึ้นไป" ถ้าไม่มี max_age, หรือ "6-9 ปี" ถ้ามีทั้งคู่
    public function getAgeRangeLabelAttribute(): string
    {
        if (is_null($this->max_age)) {
            return "{$this->min_age} ปีขึ้นไป";
        }

        return "{$this->min_age}-{$this->max_age} ปี";
    }

    public function getTargetGroupsLabelAttribute(): string
    {
        return $this->targetGroups->pluck('target_group')->implode(', ');
    }
}
