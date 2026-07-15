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

    // ประเภทสมาชิก: ลูกค้าทั่วไป / ผู้สนับสนุน / นักเรียนบาส
    const MEMBERSHIP_TYPES = [
        'customer' => 'ลูกค้า',
        'sponsor'  => 'ผู้สนับสนุน',
        'student'  => 'นักเรียนบาส',
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
        return $this->role === 'admin';
    }

    public function membershipTypeLabel(): string
    {
        return self::MEMBERSHIP_TYPES[$this->membership_type] ?? '-';
    }
}