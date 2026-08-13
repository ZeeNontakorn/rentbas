<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class GroupSession extends Model
{
    protected $fillable = [
        'name',
        'day_of_week',
        'start_time',
        'end_time',
        'court_id',
        'max_players',
        'credit_cost',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'day_of_week' => 'integer',
        'max_players' => 'integer',
        'credit_cost' => 'integer',
    ];

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(GroupRound::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * ชื่อวันภาษาไทย ใช้แสดงผลในหน้า admin
     */
    public function dayLabel(): string
    {
        $days = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
        return $days[$this->day_of_week] ?? '-';
    }

    /**
     * หาวันที่ของ "รอบถัดไป" ตาม day_of_week เทียบกับวันนี้
     * ใช้ตอนกดปุ่ม "เปิดรอบสัปดาห์นี้ / สัปดาห์หน้า" จากเทมเพลต
     */
    public function nextOccurrence(bool $skipToday = false): Carbon
    {
        $today = Carbon::today();
        $date = $today->copy()->next($this->day_of_week);

        if (!$skipToday && $today->dayOfWeek === (int) $this->day_of_week) {
            return $today;
        }

        return $date;
    }
}