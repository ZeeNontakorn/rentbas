<?php

namespace App\Models;

use App\Mail\ReservePromoted;
use App\Services\CreditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;


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

        // payerId() ใช้ is_null() เช็คแทน ?? หรือ truthy ตรงๆ เพราะระบบนี้มี user id=0 จริง
        // (ดูรายละเอียดในคอมเมนต์ของ GroupRoundSignup::payerId())
        $notifyUserId = $next->payerId();

        if ($notifyUserId !== null) {
            Notification::create([
                'user_id' => $notifyUserId,
                'title' => 'เลื่อนจากสำรองเป็นตัวจริงแล้ว',
                'message' => "ที่นั่งของ \"{$next->displayName()}\" เลื่อนจากคิวสำรองขึ้นเป็นตัวจริงในรอบ \"{$this->title}\" เรียบร้อยแล้ว",
                'action_url' => route('group-rounds.my-bookings'),
                'is_read' => false,
            ]);

            $notifyUser = User::find($notifyUserId);

            if ($notifyUser && $notifyUser->email) {
                Mail::to($notifyUser->email)->send(new ReservePromoted($this, $next));
            }
        }

        return $next;
    }

    /**
     * เมื่อหมดเวลาสละสิทธิ์แล้ว (cancel_deadline ผ่านไปแล้ว) หรือรอบเล่นจบแล้ว
     * ให้คืนเครดิตให้คิวสำรองที่ยังไม่ได้เลื่อนเป็นตัวจริงทั้งหมด แล้วปิดจ็อบไม่ให้ประมวลผลซ้ำอีก
     */
    public function processExpiredReserves(): void
    {
        if ($this->reserves_processed_at) {
            return;
        }

        $deadlinePassed = $this->cancel_deadline && now()->greaterThanOrEqualTo($this->cancel_deadline);
        $roundEnded = $this->hasRoundEnded();

        if (! $deadlinePassed && ! $roundEnded) {
            return;
        }

        DB::transaction(function () {
            $round = self::where('id', $this->id)->lockForUpdate()->first();

            if (! $round || $round->reserves_processed_at) {
                return;
            }

            $deadlinePassed = $round->cancel_deadline && now()->greaterThanOrEqualTo($round->cancel_deadline);
            $roundEnded = $round->hasRoundEnded();

            if (! $deadlinePassed && ! $roundEnded) {
                return;
            }

            $reserves = GroupRoundSignup::where('group_round_id', $round->id)
                ->where('status', 'confirmed')
                ->where('is_reserve', true)
                ->get();

            $creditService = app(CreditService::class);

            foreach ($reserves as $signup) {
                // payerId() ใช้ is_null() เช็คแทน ?? หรือ truthy ตรงๆ เพราะระบบนี้มี user id=0 จริง
                // (ดูรายละเอียดในคอมเมนต์ของ GroupRoundSignup::payerId())
                $payerId = $signup->payerId();

                if ($payerId !== null && $signup->credit_used > 0) {
                    // คืนเครดิตผ่าน CreditService เพื่อให้มีบันทึกใน credit_transactions
                    // (เดิมใช้ increment() ตรงๆ ทำให้ไม่มีประวัติธุรกรรมเลย)
                    $creditService->refundForGroupRound(
                        $payerId,
                        $signup,
                        "หมดเวลาสละสิทธิ หรือ รอบ\"{$round->title}\"เล่นจบแล้ว "
                    );
                }

                $signup->update(['status' => 'cancelled']);

                if ($payerId !== null) {
                    Notification::create([
                        'user_id' => $payerId,
                        'title' => 'การจองกลุ่มเล่นบาสถูกยกเลิก',
                        'message' => "ที่นั่งของ \"{$signup->displayName()}\" ในรอบ \"{$round->title}\" เต็มจำนวนตัวจริงและหมดเวลาสละสิทธิ์แล้ว ระบบคืนเครดิต ฿".number_format($signup->credit_used, 2)." ให้คุณเรียบร้อยแล้ว",
                        'action_url' => route('group-rounds.my-bookings'),
                        'is_read' => false,
                    ]);
                }
            }

            $round->update(['reserves_processed_at' => now()]);
        });

        $this->refresh();
    }

    /** รอบนี้ถึงเวลาเล่นจบแล้วหรือยัง (คำนวณจาก play_date + end_time) */
    public function hasRoundEnded(): bool
    {
        if (! $this->play_date) {
            return false;
        }

        $end = $this->end_time
            ? \Carbon\Carbon::parse($this->play_date->format('Y-m-d').' '.$this->end_time)
            : $this->play_date->copy()->endOfDay();

        return now()->greaterThan($end);
    }
    /**
     * หมดสิทธิ์รับคิวสำรอง/คนใหม่แล้วหรือยัง — true ถ้า "หมดเวลายกเลิกจอง" หรือ "รอบเล่นจบไปแล้ว"
     * อย่างใดอย่างหนึ่ง (รอบที่ไม่ตั้ง cancel_deadline ไว้ canSelfCancel() จะ true เสมอ แต่ถ้ารอบ
     * เล่นจบไปแล้วก็ควรถือว่าปิดรับคนใหม่ด้วย ไม่งั้นจะเพิ่มคนได้ไม่จำกัดแม้รอบจบไปแล้วก็ตาม)
     */
    public function reservationsClosed(): bool
    {
        return ! $this->canSelfCancel() || $this->hasRoundEnded();
    }
}