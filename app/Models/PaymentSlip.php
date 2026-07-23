<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSlip extends Model
{
    use HasFactory;

    protected $fillable = ['booking_id', 'file_path', 'status', 'verified_by', 'verified_at'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
