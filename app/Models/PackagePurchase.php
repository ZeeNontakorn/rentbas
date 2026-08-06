<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackagePurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'price',
        'status',
        'booking_source',
        'payment_method',
        'payment_status',
        'remaining_use',
        'locked_until',
        'paid_at',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'locked_until' => 'datetime',
            'paid_at'      => 'datetime',
            'expired_at'   => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
