<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    protected $fillable = ['title', 'description', 'starts_at', 'ends_at', 'all_day', 'color', 'recurrence', 'recurrence_until', 'coach_name', 'student_names'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'all_day' => 'boolean', 'recurrence_until' => 'date', 'student_names' => 'array'];
}
