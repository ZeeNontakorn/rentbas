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
            'date' => 'date',
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

    /**
     * Scope: หา private training booking ของโค้ชคนนี้ ที่เวลาทับซ้อนกับช่วงที่กำหนด (เฉพาะ pending/approved)
     */
    public function scopeOverlapping(Builder $query, int $coachId, string $date, string $start, string $end): Builder
    {
        return $query->where('coach_id', $coachId)
            ->whereDate('date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function (Builder $q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            });
    }

    public function isStarted(): bool
    {
        $date = $this->date instanceof Carbon
            ? $this->date->toDateString()
            : (string) $this->date;

        return Carbon::parse($date . ' ' . $this->start_time)->lte(now());
    }
}