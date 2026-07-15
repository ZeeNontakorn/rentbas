<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class CourtSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'court_id',
        'code',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function closures(): HasMany
    {
        return $this->hasMany(CourtClosure::class);
    }

    /**
     * รายชื่อ id ของ section ทั้งหมด (รวมตัวเอง) ที่ถือว่า "ไม่ว่าง" ไปด้วย
     * เมื่อ section นี้ถูกจอง เช่น จอง full -> a และ b ก็ไม่ว่างไปด้วยโดยอัตโนมัติ
     *
     * อ่านจาก court_section_blocks ทั้งสองทิศทาง (court_section_id และ blocks_section_id)
     * เผื่อข้อมูลถูกบันทึกมาไม่ครบสมมาตรตอนเพิ่ม section ใหม่ๆ
     */
    public function conflictingSectionIds(): array
    {
        $forward = DB::table('court_section_blocks')
            ->where('court_section_id', $this->id)
            ->pluck('blocks_section_id');

        $backward = DB::table('court_section_blocks')
            ->where('blocks_section_id', $this->id)
            ->pluck('court_section_id');

        return $forward->merge($backward)
            ->push($this->id)
            ->unique()
            ->values()
            ->all();
    }
}
