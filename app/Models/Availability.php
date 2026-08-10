<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Availability extends Model
{
    protected $table = 'availabilities';

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'court_id',
        'status',
        'detail'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}