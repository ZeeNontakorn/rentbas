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

    /**
     * Scope: find bookings that overlap a given time range on a given day.
     * Two ranges overlap when start < other.end AND end > other.start.
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
