<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Court extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'court_status',
        'closed_from',
        'closed_until',
    ];

    protected function casts(): array
    {
        return [
            'closed_from' => 'datetime',
            'closed_until' => 'datetime',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Return true if this court is unavailable for the given [from, to] window.
     * A court is unavailable if its status is closed, or if the window overlaps
     * the closed_from..closed_until maintenance range.
     */
    public function isClosedAt(CarbonInterface $from, CarbonInterface $to): bool
    {
        if ($this->court_status === 'closed') {
            return true;
        }

        if ($this->closed_from && $this->closed_until) {
            if ($from->lt($this->closed_until) && $to->gt($this->closed_from)) {
                return true;
            }
        }

        // Check court_closures
        $dateStr = $from->toDateString();
        $startTime = $from->toTimeString();
        $endTime = $to->toTimeString();

        $closure = CourtClosure::where('court_id', $this->id)
            ->whereDate('date', $dateStr)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();

        if ($closure) {
            return true;
        }

        return false;
    }
    public function getClosureType(string $from, string $to, string $date): ?string
    {

        if ($this->court_status === 'closed') {
            return 'closed';
        }


        // Check court_closures
        $dateStr = $date;
        $startTime = $from;
        $endTime = $to;

        $closure = CourtClosure::where('court_id', $this->id)
            ->whereDate('date', $dateStr)
            ->where('start_time', '<=', $endTime)
            ->where('end_time', '>=', $startTime)
            ->first();


        return $closure?->type;
    }
}
