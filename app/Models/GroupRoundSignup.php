<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupRoundSignup extends Model
{
    protected $fillable = [
        'group_round_id',
        'user_id',
        'guest_name',
        'order_number',
        'credit_used',
        'status',
        'is_reserve',
        'signed_up_at',
        'added_by',
        'booked_by',
    ];

    protected $casts = [
        'order_number' => 'integer',
        'credit_used' => 'integer',
        'is_reserve' => 'boolean',
        'signed_up_at' => 'datetime',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(GroupRound::class, 'group_round_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /** สมาชิกที่เป็นคนจอง/จ่ายเงินให้ที่นั่งนี้ (ตัวเองหรือจองแทนเพื่อน) */
    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

    public function displayName(): string
    {
        return $this->user->us_name ?? $this->guest_name ?? '-';
    }

    public function isGuest(): bool
    {
        return is_null($this->user_id);
    }

    /**
     * user_id ของคนที่ต้องคืนเครดิตให้เมื่อที่นั่งนี้ถูกยกเลิก — booked_by ก่อน (จองแทนเพื่อนแบบ self-service)
     * ไม่งั้นค่อย fallback ไป user_id (แอดมินผูกบัญชีตรงๆ) ใช้ is_null() เช็คแทน ?? หรือ truthy เฉยๆ
     * เพราะระบบนี้มี user id=0 จริง (บัญชี superadmin ตั้งต้น) — ถ้าเช็คแบบ if ($x) หรือ $a ?? $b เดิม
     * ค่า 0 จะถูกมองว่า "ไม่มีค่า" ทำให้ข้ามการคืนเครดิต/แจ้งเตือนไปเงียบๆ ทั้งที่จริงมีเจ้าของต้องคืนให้
     */
    public function payerId(): ?int
    {
        if (! is_null($this->booked_by)) {
            return $this->booked_by;
        }

        if (! is_null($this->user_id)) {
            return $this->user_id;
        }

        return null;
    }
}