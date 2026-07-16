<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'court_id',
        'court_section_id',
        'booking_date',
        'start_time',
        'end_time',
        'status',
        'reject_reason',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
        ];
    }

    public function startDateTime(): Carbon
    {
        return Carbon::parse($this->booking_date->toDateString() . ' ' . $this->start_time);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function courtSection(): BelongsTo
    {
        return $this->belongsTo(CourtSection::class);
    }

    /**
     * Scope: find bookings that overlap a given time range on a given day.
     * Two ranges overlap when start < other.end AND end > other.start.
     *
     * @deprecated ใช้ scopeOverlappingSection() แทน เพราะเช็คแค่ court_id เดิม
     * ไม่รู้จักเรื่องครึ่งสนาม (จอง full ที่ court นี้จะไม่ไปชนกับ booking ที่ผูกกับ
     * court_id เดียวกันแต่คนละ section) เก็บไว้เผื่อโค้ดเก่าที่ยังไม่ได้ย้ายมาใช้ตัวใหม่
     */
    public function scopeOverlapping(Builder $query, int $courtId, string $date, string $start, string $end): Builder
    {
        return $query->where('court_id', $courtId)
            ->whereDate('booking_date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function (Builder $q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            });
    }

    /**
     * Scope: หา booking ที่ทับซ้อนกับช่วงเวลาที่กำหนด โดยพิจารณาความสัมพันธ์ระหว่าง
     * section ด้วย (จองครึ่ง A ต้องชนกับที่มีคนจอง "เต็มสนาม" ไว้ก่อนด้วย ไม่ใช่แค่ชนกับ A ตรงๆ
     * และในทางกลับกัน จอง "เต็มสนาม" ต้องชนกับทั้ง A และ B ที่ถูกจองไปแล้ว)
     */
    public function scopeOverlappingSection(Builder $query, CourtSection $section, string $date, string $start, string $end): Builder
    {
        return $query->whereIn('court_section_id', $section->conflictingSectionIds())
            ->whereDate('booking_date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function (Builder $q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            });
    }

    /**
     * True if the booking's start time has already passed.
     */
    public function isStarted(): bool
    {
        $date = $this->booking_date instanceof Carbon
            ? $this->booking_date->toDateString()
            : (string) $this->booking_date;

        return Carbon::parse($date.' '.$this->start_time)->lte(now());
    }
}
