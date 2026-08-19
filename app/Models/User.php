<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// #[Fillable(['us_name', 'name', 'email', 'phone', 'password', 'role', 'is_verified'])]
// #[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['us_name', 'name', 'email', 'phone', 'password', 'role', 'is_verified', 'membership_type'];

    protected $hidden = ['password', 'remember_token'];

    // ประเภทสมาชิกสำหรับ role 'user' — ลูกค้าทั่วไป / ผู้สนับสนุน / นักเรียนบาส
    const MEMBERSHIP_TYPES = [
        'customer' => 'ลูกค้า',
        'sponsor' => 'ผู้สนับสนุน',
        'student' => 'นักเรียนบาส',
    ];


    // ประเภทสมาชิกสำหรับ role 'staff' — พนักงานประจำ / พนักงานชั่วคราว / นักศึกษาฝึกงาน / โค้ช / ผู้ช่วยสนาม
    const STAFF_TYPES = [
        'permanent' => 'พนักงานประจำ',
        'temporary' => 'พนักงานชั่วคราว',
        'intern' => 'นักศึกษาฝึกงาน',
        'coach' => 'โค้ช',
        'court_assistant' => 'ผู้ช่วยสนาม',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
        ];
    }

    // cache ผลรวมยอดเครดิตต่อ request เดียว กัน query ซ้ำๆ (เช่น navbar เรียก credit_balance หลายครั้งต่อหน้า)
    private ?int $creditBalanceCache = null;

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications(): HasMany
    {
        return $this->notifications()->where('is_read', false);
    }

    public function otpTokens(): HasMany
    {
        return $this->hasMany(OtpToken::class);
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function creditTopupRequests(): HasMany
    {
        return $this->hasMany(CreditTopupRequest::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin'], true);
    }

    // คืนชุดตัวเลือกประเภทสมาชิกที่ถูกต้องตาม role ปัจจุบันของ user คนนี้
    public function membershipTypeOptions(): array
    {
        return $this->role === 'staff' ? self::STAFF_TYPES : self::MEMBERSHIP_TYPES;
    }

    public function membershipTypeLabel(): string
    {
        return $this->membershipTypeOptions()[$this->membership_type] ?? '-';
    }

    /**
     * ดึงข้อมูลโปรไฟล์เฉพาะของ Coach/Staff
     */
    public function staffProfile(): HasOne
    {
        return $this->hasOne(StaffProfile::class, 'user_id');
    }

    /**
     * ดึงประวัติตารางเวลาทั้งหมด
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class, 'user_id');
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'coach_id');
    }

    /**
     * รายการคำขอจองเทรนเนอร์ส่วนตัวที่ user คนนี้เป็นคนจอง (ฝั่งลูกค้า)
     */
    public function privateTrainingBookings(): HasMany
    {
        return $this->hasMany(PrivateTrainingBooking::class, 'user_id');
    }

    /**
     * รายการคำขอจองเทรนเนอร์ส่วนตัวที่ user คนนี้เป็นโค้ชที่ถูกจอง (ฝั่งโค้ช)
     */
    public function coachingBookings(): HasMany
    {
        return $this->hasMany(PrivateTrainingBooking::class, 'coach_id');
    }

    public function assistedPrivateTrainingBookings(): HasMany
    {
        return $this->hasMany(PrivateTrainingBooking::class, 'court_assistant_id');
    }

    /**
     * ยอดเครดิตคงเหลือรวม (หน่วยสตางค์) คำนวณจากผลรวมก้อนเครดิต (credits) ที่ยังไม่หมดอายุ
     * แทนคอลัมน์ credit_balance เดิม — ตั้งชื่อ accessor เดิมไว้เพื่อให้โค้ด/view ที่อ่าน
     * $user->credit_balance อยู่แล้วไม่ต้องแก้ไข
     */
    public function getCreditBalanceAttribute(): int
    {
        if ($this->creditBalanceCache !== null) {
            return $this->creditBalanceCache;
        }

        if ($this->relationLoaded('credits')) {
            return $this->creditBalanceCache = (int) $this->credits
                ->filter(fn (Credit $c) => $c->remaining_satang > 0 && ($c->expires_at === null || $c->expires_at->isFuture()))
                ->sum('remaining_satang');
        }

        return $this->creditBalanceCache = (int) $this->credits()->valid()->sum('remaining_satang');
    }

    /**
     * ยอดเครดิตคงเหลือแบบบาท (float) สำหรับแสดงผล
     */
    public function getCreditBalanceBahtAttribute(): float
    {
        return $this->credit_balance / 100;
    }
}
