<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = ['date', 'name'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public static function isHoliday(string $date): bool
    {
        return static::whereDate('date', $date)->exists();
    }
}
