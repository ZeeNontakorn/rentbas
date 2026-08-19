<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // วันหมดอายุของยอดเครดิตทั้งก้อน (credit_balance) — เติมเครดิตครั้งใหม่จะขยับวันนี้ออกไป
            // เฉพาะกรณีที่ทำให้หมดอายุ "ช้าลง" เท่านั้น (ดูตรรกะใน CreditService::extendExpiry)
            // null = ไม่มีเครดิตที่ต้องนับวันหมดอายุ (ยอด 0 หรือยังไม่เคยเติม)
            $table->timestamp('credit_expires_at')->nullable()->after('credit_balance');
            // เก็บว่า credit_expires_at ค่าไหนที่เพิ่งแจ้งเตือน "ใกล้หมดอายุ" ไปแล้ว กันแจ้งซ้ำทุกวัน
            // ถ้า credit_expires_at เปลี่ยน (เติมเพิ่มจนขยับออกไป) ค่านี้จะไม่ตรงกันอีกต่อไป ระบบจะแจ้งใหม่ได้ถูกรอบ
            $table->timestamp('credit_expiry_notified_for')->nullable()->after('credit_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['credit_expires_at', 'credit_expiry_notified_for']);
        });
    }
};
