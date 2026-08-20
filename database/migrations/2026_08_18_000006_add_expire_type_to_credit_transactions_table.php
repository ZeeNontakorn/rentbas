<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * เพิ่มค่า 'expire' ให้ enum type ของ credit_transactions — ใช้บันทึกตอนระบบหักเครดิตที่หมดอายุ
     * ทิ้งอัตโนมัติ (scheduled job) แยกจาก 'deduct' ที่ใช้ตอนลูกค้าจ่ายค่าจอง/แพ็กเกจ หรือแอดมินหักแก้ไข
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE credit_transactions MODIFY type ENUM('topup','deduct','refund','expire') DEFAULT 'topup'");
    }

    public function down(): void
    {
        DB::statement("UPDATE credit_transactions SET type = 'deduct' WHERE type = 'expire'");
        DB::statement("ALTER TABLE credit_transactions MODIFY type ENUM('topup','deduct','refund') DEFAULT 'topup'");
    }
};
