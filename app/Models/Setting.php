<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    use HasFactory;

    public const DEFAULTS = [
        'about_title' => 'สนามที่ได้มาตรฐาน ระบบการจองที่ทันสมัย',
        'about_desc' => 'THATA HOMECOURT คือสนามบาสเก็ตบอลมาตรฐานสากล ทีมงานพร้อมดูแลตลอด 24 ชั่วโมง',
        'promo_subtitle' => 'อัปเดตโปรโมชั่นสุดพิเศษ',
        'promo_title' => 'Preview Promotion',
        'promo_card_title' => 'BASKETBALL',
        'promo_card_sub' => 'โปรโมชั่นพิเศษ',
        'promo_image' => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?q=80&w=800&auto=format&fit=crop',
        'hero_img_1' => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?q=80&w=2000&auto=format&fit=crop',
        'hero_img_2' => 'https://images.unsplash.com/photo-1504450758481-7338eba7524a?q=80&w=2000&auto=format&fit=crop',
        'hero_img_3' => 'https://images.unsplash.com/photo-1515523110800-9415d13b84a8?q=80&w=2000&auto=format&fit=crop',
        'about_img_1' => 'https://images.unsplash.com/photo-1504450758481-7338eba7524a?q=80&w=900&auto=format&fit=crop',
        'about_img_2' => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?q=80&w=600&auto=format&fit=crop',
        'about_img_3' => 'https://images.unsplash.com/photo-1574623452334-1e0ac2b3ccb4?q=80&w=600&auto=format&fit=crop',
        'courts_bg' => 'https://images.pexels.com/photos/18460191/pexels-photo-18460191.jpeg',
        'community_img' => 'https://images.unsplash.com/photo-1515523110800-9415d13b84a8?q=80&w=900&auto=format&fit=crop',
    ];

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
            $storagePath = preg_replace('/^\/?storage\//', '', $val);
            return Storage::disk('public')->url($storagePath);
        }

        return $val;
    }

    /**
     * Get multiple settings as key => value (with defaults and storage URL transform).
     */
    public static function values(array $keys = null): array
    {
        $defaults = self::DEFAULTS;
        $keys = $keys ?? array_keys($defaults);

        $stored = self::whereIn('key', $keys)
            ->pluck('value', 'key')
            ->toArray();

        $resolved = [];

        foreach ($keys as $key) {
            $val = $stored[$key] ?? ($defaults[$key] ?? null);

            if (is_string($val) && (str_starts_with($val, 'storage/') || str_starts_with($val, '/storage/'))) {
                $storagePath = preg_replace('/^\/?storage\//', '', $val);
                $val = Storage::disk('public')->url($storagePath);
            }

            $resolved[$key] = $val;
        }

        return $resolved;
    }
}
