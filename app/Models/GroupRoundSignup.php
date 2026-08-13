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
        'signed_up_at',
        'added_by',
    ];

    protected $casts = [
        'order_number' => 'integer',
        'credit_used' => 'integer',
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
}
