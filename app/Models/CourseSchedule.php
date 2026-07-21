<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseSchedule extends Model
{
    protected $fillable = [
        'course_id',
        'court_section_id',
        'day_type',
        'weekdays',
        'start_time',
        'end_time',
        'is_limited_spots',
        'capacity',
    ];

    protected $casts = [
        'is_limited_spots' => 'boolean',
        'capacity' => 'integer',
        'weekdays' => 'array',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function courtSection(): BelongsTo
    {
        return $this->belongsTo(CourtSection::class);
    }

    public function getDayTypeLabelAttribute(): string
    {
        if (! empty($this->weekdays)) {
            $labels = [
                'mon' => 'จันทร์', 'tue' => 'อังคาร', 'wed' => 'พุธ', 'thu' => 'พฤหัสบดี',
                'fri' => 'ศุกร์', 'sat' => 'เสาร์', 'sun' => 'อาทิตย์',
            ];

            $weekdayDays = collect($this->weekdays)->filter(fn ($day) => in_array($day, ['mon', 'tue', 'wed', 'thu', 'fri'], true));
            $weekendDays = collect($this->weekdays)->filter(fn ($day) => in_array($day, ['sat', 'sun'], true));
            $parts = [];

            if ($weekdayDays->isNotEmpty()) {
                $parts[] = 'วันธรรมดา: '.$weekdayDays->map(fn ($day) => $labels[$day])->implode(', ');
            }
            if ($weekendDays->isNotEmpty()) {
                $parts[] = 'เสาร์-อาทิตย์: '.$weekendDays->map(fn ($day) => $labels[$day])->implode(', ');
            }

            return implode(' | ', $parts);
        }

        return $this->day_type === 'weekday' ? 'วันธรรมดา (จ, พ, ศ)' : 'เสาร์-อาทิตย์ (ส, อา)';
    }

    // เช่น "1.30 ชั่วโมง" (ชั่วโมง.นาที) ตามที่โชว์ในฟอร์มของ Gemini
    public function getDurationLabelAttribute(): string
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        $minutes = max(0, $start->diffInMinutes($end, false));

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return $hours.'.'.str_pad((string) $mins, 2, '0', STR_PAD_LEFT).' ชั่วโมง';
    }

    // เช่น "จำกัด 10 คน" ถ้า is_limited_spots = true, หรือ "ไม่จำกัดจำนวน" ถ้าไม่จำกัด
    // TODO (อนาคต): เมื่อมีตาราง bookings/purchases แล้ว ให้เปลี่ยนมาโชว์ "จำกัด 10 คน (จองแล้ว 4)"
    // โดยนับจาก relation ใหม่ เช่น $this->bookings()->count()
    public function getSpotsLabelAttribute(): string
    {
        if (! $this->is_limited_spots || is_null($this->capacity)) {
            return 'ไม่จำกัดจำนวน';
        }

        return "จำกัด {$this->capacity} คน";
    }
}
