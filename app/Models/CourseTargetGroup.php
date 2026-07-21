<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseTargetGroup extends Model
{
    protected $fillable = [
        'course_id',
        'target_group',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}