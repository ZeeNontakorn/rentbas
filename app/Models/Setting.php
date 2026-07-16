<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    /**
     * รายชื่อ key ที่เป็น "รูปภาพ" เท่านั้น — ใช้กรองว่า key ไหนควรถูกแปลงเป็น media URL
     * key อื่นๆ ที่ไม่อยู่ในลิสต์นี้ (เช่น about_title, about_desc) จะไม่ถูกแตะเลย
     */
    public const IMAGE_KEYS = [
        'promo_image',
        'hero_img_1',
        'hero_img_2',
        'hero_img_3',
        'about_img_1',
        'about_img_2',
        'about_img_3',
        'courts_bg',
        'community_img',
    ];

    protected $primaryKey = 'key';
    protected $keyType    = 'string';
    public $incrementing  = false;
    public $timestamps    = false;

    protected $fillable = ['key', 'value'];

    /**
     * แปลง path รูปที่เก็บใน DB (ไม่ว่าจะเป็น path เก่า storage/... , storage/app/...
     * หรือ path ใหม่ media/...) ให้กลายเป็น URL เต็มผ่าน route /media เสมอ
     * ถ้าเป็น URL เต็มอยู่แล้ว (http/https เช่นรูป default จาก unsplash) จะคืนค่าเดิมไว้เฉยๆ
     */
    protected static function resolveImageUrl(?string $val): ?string
    {
        if (empty($val)) {
            return $val;
        }

        // เป็น absolute URL อยู่แล้ว (เช่นรูป default จาก unsplash/pexels) ไม่ต้องแตะ
        if (preg_match('#^https?://#i', $val)) {
            return $val;
        }

        // ตัด prefix เก่าทุกแบบทิ้งให้เหลือแค่ relative path จริงๆ เช่น "site/xxx.jpg"
        $clean = preg_replace('#^/?(storage/app/public/|storage/app/|storage/|media/)#i', '', $val);

        return asset('media/' . ltrim($clean, '/'));
    }

    /**
     * เช็คว่า key นี้เป็นรูปภาพหรือไม่ (รวมทั้ง court_img_xx ที่สร้าง dynamic ต่อสนาม)
     */
    protected static function isImageKey(string $key): bool
    {
        return in_array($key, self::IMAGE_KEYS, true)
            || str_starts_with($key, 'court_img_');
    }

    /**
     * Helper to get a setting value quickly by key.
     */
    public static function getVal($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        $val = $setting ? $setting->value : $default;

        if (is_string($val) && self::isImageKey($key)) {
            return self::resolveImageUrl($val);
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

            if (is_string($val) && self::isImageKey($key)) {
                $val = self::resolveImageUrl($val);
            }

            $resolved[$key] = $val;
        }

        return $resolved;
    }
}
