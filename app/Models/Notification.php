<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'action_url',
        'type',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * กลุ่มสีสำหรับแสดงผลแจ้งเตือน โดยดูจากความหมายของหัวข้อเดิม
     * เพื่อให้เพิ่มสีใน UI ได้โดยไม่ต้องเปลี่ยนโครงสร้างฐานข้อมูล
     */
    public function visualType(): string
    {
        $title = $this->title ?? '';

        foreach (['ปฏิเสธ', 'ยกเลิก', 'ล้มเหลว', 'ไม่สำเร็จ', 'หมดอายุ'] as $keyword) {
            if (str_contains($title, $keyword)) {
                return 'danger';
            }
        }

        foreach (['อนุมัติ', 'สำเร็จ', 'ยืนยัน', 'ผ่านการพิจารณา', 'ถูกเติมแล้ว'] as $keyword) {
            if (str_contains($title, $keyword)) {
                return 'success';
            }
        }

        foreach (['คำขอ', 'ใหม่', 'รอตรวจสอบ', 'รออนุมัติ', 'รีวิว'] as $keyword) {
            if (str_contains($title, $keyword)) {
                return 'warning';
            }
        }

        return 'info';
    }
}
