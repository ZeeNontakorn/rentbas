<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateTrainingBooking extends Model
{
    use HasFactory;

    protected $table = 'private_training_bookings';

    protected $fillable = [
        'user_id',
        'coach_id',
        'court_id',
        'court_section_id',
        'court_assigned_by',
        'court_assigned_at',
        'date',
        'start_time',
        'end_time',
        'status',
        'note',
        'reject_reason',
    ];

    protected function casts(): array
    {
        return [
            // แคสต์ฟิลด์ date ให้เป็น Object ของ Carbon อัตโนมัติ 
            // ทำให้ตอนเรียก $this->date สามารถต่อด้วยฟังก์ชันของ Carbon ได้เลย
            'date' => 'date',
            'court_assigned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function courtSection(): BelongsTo
    {
        return $this->belongsTo(CourtSection::class);
    }

    public function courtAssignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'court_assigned_by');
    }

    /**
     * Local Scope: หาการจองที่เวลาทับซ้อนกับช่วงเวลาที่กำลังจะทำรายการ
     * (เฉพาะรายการที่ pending รออนุมัติ หรือ approved อนุมัติแล้ว)
     */
    public function scopeOverlapping(Builder $query, int $coachId, string $date, string $start, string $end): Builder
    {
        return $query->where('coach_id', $coachId)
            ->whereDate('date', $date)
            ->whereIn('status', ['pending', 'awaiting_court', 'confirmed'])
            // Logic เช็คเวลาทับซ้อน (Overlapping Logic):
            // รายการเก่าต้องเริ่ม "ก่อน" รายการใหม่จะจบ (start_time < $end) 
            // และ รายการเก่าต้องจบ "หลัง" รายการใหม่เริ่ม (end_time > $start)
            ->where(function (Builder $q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            });
    }

    /**
     * ตรวจสอบว่าถึงเวลา/เลยเวลาเริ่มต้นเทรนแล้วหรือยัง
     */
    public function isStarted(): bool
    {
        // ใช้ ->copy() เพื่อโคลน Object สร้าง Instance ใหม่ ป้องกันไม่ให้การแก้เวลาไปกระทบกับค่าเดิมของ Model
        // ->setTimeFromTimeString() นำ String เวลามาประกอบเข้ากับวันที่
        // ->isPast() รีเทิร์นค่า true หากเวลาที่ได้ เป็นอดีตหรือเท่ากับปัจจุบัน (now)
        return $this->date->copy()->setTimeFromTimeString($this->start_time)->isPast();
    }
}
