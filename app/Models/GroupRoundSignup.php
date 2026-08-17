<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupRoundSignup extends Model
{
    protected $fillable = [
        'group_round_id',
        'user_id',
        'guest_name',
        'order_number',
        'credit_used',
        'status',
        'is_reserve',
        'signed_up_at',
        'added_by',
        'booked_by',
    ];

    protected $casts = [
        'order_number' => 'integer',
        'credit_used' => 'integer',
        'is_reserve' => 'boolean',
        'signed_up_at' => 'datetime',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(GroupRound::class, 'group_round_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /** สมาชิกที่เป็นคนจอง/จ่ายเงินให้ที่นั่งนี้ (ตัวเองหรือจองแทนเพื่อน) */
    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

    public function displayName(): string
    {
        return $this->user->name ?? $this->guest_name ?? '-';
    }

    public function isGuest(): bool
    {
        return is_null($this->user_id);
    }
}