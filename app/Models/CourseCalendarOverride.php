<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseCalendarOverride extends Model
{
    protected $fillable = ['course_schedule_id', 'occurrence_date', 'title_override', 'starts_at', 'ends_at', 'package_type', 'court_section_id'];

    protected $casts = ['occurrence_date' => 'date', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function schedule(): BelongsTo { return $this->belongsTo(CourseSchedule::class, 'course_schedule_id'); }
    public function courtSection(): BelongsTo { return $this->belongsTo(CourtSection::class); }
}
