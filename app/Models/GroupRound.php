<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class GroupRound extends Model
{
    /** จำนวนที่นั่งสูงสุดที่ 1 สมาชิกจองได้ต่อรอบ (รวมตัวเอง + จองแทนเพื่อน) */
    const MAX_SEATS_PER_USER = 5;

    protected $fillable = [
        'group_session_id',
        'title',
        'play_date',
        'start_time',
        'end_time',
        'court_id',
        'max_players',
        'credit_cost',
        'cancel_deadline',
        'status',
        'created_by',
    ];

    protected $casts = [
        'play_date' => 'date',
        'max_players' => 'integer',
        'credit_cost' => 'integer',
        'cancel_deadline' => 'datetime',
        'reserves_processed_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GroupSession::class, 'group_session_id');
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signups(): HasMany
    {
        return $this->hasMany(GroupRoundSignup::class)->orderBy('order_number');
    }

    public function confirmedSignups(): HasMany
    {
        return $this->signups()->where('status', 'confirmed');
    }

    /** จำนวนคน "ตัวจริง" ที่ยืนยันแล้ว (ไม่นับคิวสำรอง) */
    public function mainConfirmedCount(): int
    {
        return $this->confirmedSignups()->where('is_reserve', false)->count();
    }

    /** จำนวนคนทั้งหมดที่ยืนยันแล้ว รวมทั้งตัวจริงและสำรอง */
    public function confirmedCount(): int
    {
        return $this->confirmedSignups()->count();
    }

    public function isFull(): bool
    {
        return $this->mainConfirmedCount() >= $this->max_players;
    }

    public function remainingSlots(): int
    {
        return max(0, $this->max_players - $this->mainConfirmedCount());
    }

    /** จำนวนที่นั่งที่สมาชิกคนนี้จองไปแล้วในรอบนี้ (ตัวเอง + จองแทนเพื่อน) */
    public function bookedSeatsFor(?int $userId): int
    {
        if (! $userId) {
            return 0;
        }

        return $this->confirmedSignups()
            ->where(function ($q) use ($userId) {
                $q->where('booked_by', $userId)->orWhere('user_id', $userId);
            })
            ->count();
    }

    /** ที่นั่งที่สมาชิกคนนี้ยังจองเพิ่มได้อีกในรอบนี้ (สูงสุด MAX_SEATS_PER_USER) */
    public function remainingSeatsFor(?int $userId): int
    {
        return max(0, self::MAX_SEATS_PER_USER - $this->bookedSeatsFor($userId));
    }

    /** ยังอยู่ในช่วงเวลาที่สมาชิกยกเลิกจองเองได้ไหม (ไม่ได้ตั้งเดดไลน์ = ยกเลิกได้เสมอ) */
    public function canSelfCancel(): bool
    {
        if (! $this->cancel_deadline) {
            return true;
        }

        return now()->lessThan($this->cancel_deadline);
    }

    /**
     * เลื่อนคิวสำรองคนแรกสุด (ตามลำดับที่ลงชื่อจริง) ขึ้นเป็นตัวจริงแทนที่ว่าง
     * เรียกใช้ทุกครั้งที่มีคน "ตัวจริง" ยกเลิก/ถูกนำออกจากรอบ
     */
    public function promoteNextReserve(): ?GroupRoundSignup
    {
        $next = GroupRoundSignup::where('group_round_id', $this->id)
            ->where('status', 'confirmed')
            ->where('is_reserve', true)
            ->orderBy('order_number')
            ->first();

        if (! $next) {
            return null;
        }

        $next->update(['is_reserve' => false]);

        $notifyUserId = $next->user_id ?? $next->booked_by;

        if ($notifyUserId) {
            Notification::create([
                'user_id' => $notifyUserId,
                'title' => 'เลื่อนจากสำรองเป็นตัวจริงแล้ว',
                'message' => "ที่นั่งของ \"{$next->displayName()}\" เลื่อนจากคิวสำรองขึ้นเป็นตัวจริงในรอบ \"{$this->title}\" เรียบร้อยแล้ว",
                'action_url' => null,
                'is_read' => false,
            ]);
        }

        return $next;
    }

    /**
     * เมื่อหมดเวลาสละสิทธิ์แล้ว (cancel_deadline ผ่านไปแล้ว) ให้คืนเครดิตให้คิวสำรอง
     * ที่ยังไม่ได้เลื่อนเป็นตัวจริงทั้งหมด แล้วปิดจ็อบไม่ให้ประมวลผลซ้ำอีก
     */
    public function processExpiredReserves(): void
    {
        if (! $this->cancel_deadline || $this->reserves_processed_at) {
            return;
        }

        if (now()->lessThan($this->cancel_deadline)) {
            return;
        }

        DB::transaction(function () {
            $round = self::where('id', $this->id)->lockForUpdate()->first();

            if (! $round || $round->reserves_processed_at || ! $round->cancel_deadline || now()->lessThan($round->cancel_deadline)) {
                return;
            }

            $reserves = GroupRoundSignup::where('group_round_id', $round->id)
                ->where('status', 'confirmed')
                ->where('is_reserve', true)
                ->get();

            foreach ($reserves as $signup) {
                $payerId = $signup->booked_by ?? $signup->user_id;

                if ($payerId && $signup->credit_used > 0) {
                    User::where('id', $payerId)->increment('credit_balance', $signup->credit_used * 100);
                }

                $signup->update(['status' => 'cancelled']);

                if ($payerId) {
                    Notification::create([
                        'user_id' => $payerId,
                        'title' => 'การจองกลุ่มเล่นบาสถูกยกเลิก',
                        'message' => "ที่นั่งของ \"{$signup->displayName()}\" ในรอบ \"{$round->title}\" เต็มจำนวนตัวจริงและหมดเวลาสละสิทธิ์แล้ว ระบบคืนเครดิต ฿".number_format($signup->credit_used, 2)." ให้คุณเรียบร้อยแล้ว",
                        'action_url' => null,
                        'is_read' => false,
                    ]);
                }
            }

            $round->update(['reserves_processed_at' => now()]);
        });

        $this->refresh();
    }
}