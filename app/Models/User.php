<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;



// #[Fillable(['name', 'email', 'phone', 'password', 'role', 'is_verified'])]
// #[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    protected $fillable = ['name', 'email', 'phone', 'password', 'role', 'is_verified', 'membership_type'];
    protected $hidden = ['password', 'remember_token'];

    // ประเภทสมาชิกสำหรับ role 'user' — ลูกค้าทั่วไป / ผู้สนับสนุน / นักเรียนบาส
    const MEMBERSHIP_TYPES = [
        'customer' => 'ลูกค้า',
        'sponsor'  => 'ผู้สนับสนุน',
        'student'  => 'นักเรียนบาส',
    ];

    // ประเภทสมาชิกสำหรับ role 'staff' — พนักงานประจำ / พนักงานชั่วคราว / นักศึกษาฝึกงาน
    // ประเภทสมาชิกสำหรับ role 'staff' — พนักงานประจำ / พนักงานชั่วคราว / นักศึกษาฝึกงาน / โค้ช / ผู้ช่วยสนาม
    const STAFF_TYPES = [
        'permanent'       => 'พนักงานประจำ',
        'temporary'       => 'พนักงานชั่วคราว',
        'intern'          => 'นักศึกษาฝึกงาน',
        'coach'           => 'โค้ช',
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
}
