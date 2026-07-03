<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourtClosure extends Model
{
    use HasFactory;

    protected $fillable = [
        'court_id',
        'date',
        'start_time',
        'end_time',
        'type', // maintenance | unavailable
    ];

    public function court()
    {
        return $this->belongsTo(Court::class);
    }
}
