<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    use HasFactory;

    protected $primaryKey = 'key';
    protected $keyType    = 'string';
    public $incrementing  = false;
    public $timestamps    = false;

    protected $fillable = ['key', 'value'];

    /**
     * Helper to get a setting value quickly by key.
     */
    public static function getVal($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        $val = $setting ? $setting->value : $default;

        if (is_string($val) && (str_starts_with($val, 'storage/') || str_starts_with($val, '/storage/'))) {
            return asset(ltrim($val, '/'));
        }

        return $val;
    }
}
