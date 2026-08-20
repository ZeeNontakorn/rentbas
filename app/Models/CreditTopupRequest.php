<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditTopupRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'credit_topup_package_id', 'price_satang', 'credit_satang', 'expiry_days',
        'payment_method', 'topper_name', 'slip_path', 'status',
        'approved_by', 'approved_at', 'rejected_reason',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public const PAYMENT_METHODS = [
        'promptpay' => 'โอนผ่าน PromptPay',
        'line' => 'เติมผ่าน LINE',
        'cash_counter' => 'เงินสดหน้าเคาน์เตอร์',
    ];

    public const STATUSES = [
        'pending' => 'รอตรวจสอบ',
        'approved' => 'อนุมัติแล้ว',
        'rejected' => 'ปฏิเสธแล้ว',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(CreditTopupPackage::class, 'credit_topup_package_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getSlipUrlAttribute(): ?string
    {
        return $this->slip_path ? asset('media/'.$this->slip_path) : null;
    }
}
