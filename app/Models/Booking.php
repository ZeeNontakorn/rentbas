<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'court_id',
        'court_section_id',
        'booking_date',
        'start_time',
        'end_time',
        'status',
        'reject_reason',
        // === เพิ่มสำหรับ Credit / Payment / Concurrency feature ===
        'booking_source',       // manual | credit | promptpay
        'payment_method',       // credit | promptpay
        'payment_status',       // unpaid | pending_slip | paid | failed | refunded
        'price',                // สตางค์
        'pricing_rule_id',
        'promotion_package_id',
        'price_breakdown',      // JSON: รายละเอียดราคา ณ ตอนจอง (ใช้แสดงในใบเสร็จ)
        'locked_until',
        'review_invited_at',    // เวลาที่ยิงแจ้งเตือนชวนรีวิวไปแล้ว (null = ยังไม่เคยชวน)
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'locked_until' => 'datetime',
            'review_invited_at' => 'datetime',
            'price_breakdown' => 'array',
        ];
    }

    public function startDateTime(): Carbon
    {
        return Carbon::parse($this->booking_date->toDateString() . ' ' . $this->start_time);
    }

    public function endDateTime(): Carbon
    {
        return Carbon::parse($this->booking_date->toDateString() . ' ' . $this->end_time);
    }

    /**
     * ใช้บริการจบแล้วหรือยัง — เงื่อนไขหลักของสิทธิ์รีวิว
     */
    public function hasFinished(): bool
    {
        return $this->endDateTime()->isPast();
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function courtSection(): BelongsTo
    {
        return $this->belongsTo(CourtSection::class);
    }

    public function pricingRule(): BelongsTo
    {
        return $this->belongsTo(PricingRule::class);
    }

    public function promotionPackage(): BelongsTo
    {
        return $this->belongsTo(PromotionPackage::class);
    }

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function paymentSlips()
    {
        return $this->hasMany(PaymentSlip::class);
    }

    /**
     * ราคาแบบบาท (float) สำหรับแสดงผล — price เก็บเป็นสตางค์ในฐานข้อมูล
     */
    public function getPriceBahtAttribute(): ?float
    {
        return $this->price === null ? null : $this->price / 100;
    }

    /**
     * @deprecated ใช้ scopeOverlappingSection() แทน
     */
    public function scopeOverlapping(Builder $query, int $courtId, string $date, string $start, string $end): Builder
    {
        return $query->where('court_id', $courtId)
            ->whereDate('booking_date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function (Builder $q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            });
    }

    /**
     * Scope: หา booking ที่ทับซ้อนกับช่วงเวลาที่กำหนด โดยพิจารณาความสัมพันธ์ระหว่าง section ด้วย
     *
     * นับเป็น "ชนกัน/ไม่ว่าง" เมื่อ:
     *  - status เป็น pending (manual, รอแอดมินอนุมัติ) หรือ approved, หรือ
     *  - status เป็น pending_payment และยังไม่หมดอายุการล็อก (locked_until > now())
     *    (ถ้าหมดอายุแล้วแต่ scheduler ยังไม่ทันรัน ให้ถือว่า "ว่าง" ไปเลย กันสล็อตค้าง)
     */
    public function scopeOverlappingSection(Builder $query, CourtSection $section, string $date, string $start, string $end): Builder
    {
        return $query->whereIn('court_section_id', $section->conflictingSectionIds())
            ->whereDate('booking_date', $date)
            ->where(function (Builder $q) {
                $q->whereIn('status', ['pending', 'approved'])
                    ->orWhere(function (Builder $qq) {
                        $qq->where('status', 'pending_payment')->where('locked_until', '>', now());
                    });
            })
            ->where(function (Builder $q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            });
    }

    public function isStarted(): bool
    {
        $date = $this->booking_date instanceof Carbon
            ? $this->booking_date->toDateString()
            : (string) $this->booking_date;

        return Carbon::parse($date.' '.$this->start_time)->lte(now());
    }
}
