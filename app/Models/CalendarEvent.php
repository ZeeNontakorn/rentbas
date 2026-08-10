<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    protected $fillable = ['title', 'description', 'starts_at', 'ends_at', 'all_day', 'color', 'recurrence', 'recurrence_until', 'coach_name', 'coach_id', 'event_type', 'package_type', 'court_section_id', 'student_names'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'all_day' => 'boolean', 'recurrence_until' => 'date', 'student_names' => 'array'];

    public function courtSection(): BelongsTo
    {
        return $this->belongsTo(CourtSection::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }
}
