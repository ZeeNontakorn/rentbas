<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewImage extends Model
{
    protected $fillable = ['review_id', 'image_path', 'sort_order'];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function getImageUrlAttribute(): string
    {
        return asset('media/'.ltrim($this->image_path, '/'));
    }
}
