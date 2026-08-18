<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditTransactionLot extends Model
{
    use HasFactory;

    protected $fillable = ['credit_transaction_id', 'credit_id', 'amount_satang'];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(CreditTransaction::class, 'credit_transaction_id');
    }

    public function credit(): BelongsTo
    {
        return $this->belongsTo(Credit::class);
    }
}
