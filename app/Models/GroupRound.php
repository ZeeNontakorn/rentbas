<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupRound extends Model
{
    protected $fillable = [
        'group_session_id',
        'title',
        'play_date',
        'start_time',
        'end_time',
        'court_id',
        'max_players',
        'credit_cost',
        'status',
        'created_by',
    ];

    protected $casts = [
        'play_date' => 'date',
        'max_players' => 'integer',
        'credit_cost' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GroupSession::class, 'group_session_id');
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signups(): HasMany
    {
        return $this->hasMany(GroupRoundSignup::class)->orderBy('order_number');
    }

    public function confirmedSignups(): HasMany
    {
        return $this->signups()->where('status', 'confirmed');
    }

    public function confirmedCount(): int
    {
        return $this->confirmedSignups()->count();
    }

    public function isFull(): bool
    {
        return $this->confirmedCount() >= $this->max_players;
    }

    public function remainingSlots(): int
    {
        return max(0, $this->max_players - $this->confirmedCount());
    }
}