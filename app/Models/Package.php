<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'name', 'type', 'description', 'price', 'num_of_use', 'day', 'is_active', 'image', 'usable_days'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'day' => 'integer',
        'is_active' => 'boolean',
        'usable_days' => 'array',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'private' ? 'Private Training (ส่วนตัว)' : $this->type;
    }
}
