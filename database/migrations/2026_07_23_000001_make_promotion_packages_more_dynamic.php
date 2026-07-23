<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ทำให้แพ็กเกจโปรโมชั่นสร้าง/ลบ/กำหนดเงื่อนไขเองได้เต็มที่จากหน้าแอดมิน โดยไม่ผูกกับ
     * "ชื่อ/หมวดหมู่" ที่ hardcode ไว้ในโค้ดอีกต่อไป (เดิม PricingService เช็ค category === 'private'
     * เพื่อยกเว้นกฎบางอย่าง ซึ่งพังทันทีถ้าแอดมินสร้างแพ็กเกจใหม่ชื่ออื่น):
     *
     * - duration_hours: nullable แล้ว (null = ไม่บังคับระยะเวลาตายตัว จองกี่ชั่วโมงก็ได้)
     * - category: เปลี่ยนจาก enum ตายตัว (personal/group/private) เป็น string ธรรมดา
     *   เพื่อให้แอดมินตั้งหมวดหมู่ใหม่เองได้ตอนสร้างแพ็กเกจ ไม่ต้องแก้โค้ด/migration ทุกครั้ง
     *
     * ใช้ raw SQL (ไม่ใช้ ->change()) เพราะโปรเจกต์นี้ไม่ได้ติดตั้ง doctrine/dbal
     * — เป็นแนวทางเดียวกับ migration ที่เคยแก้ enum ของ bookings.status มาก่อนหน้านี้
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE promotion_packages MODIFY duration_hours TINYINT UNSIGNED NULL DEFAULT NULL");
            DB::statement("ALTER TABLE promotion_packages MODIFY category VARCHAR(50) NOT NULL DEFAULT 'personal'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE promotion_packages SET duration_hours = 2 WHERE duration_hours IS NULL");
            DB::statement("ALTER TABLE promotion_packages MODIFY duration_hours TINYINT UNSIGNED NOT NULL DEFAULT 2");
            DB::statement("ALTER TABLE promotion_packages MODIFY category ENUM('personal','group','private') NOT NULL DEFAULT 'personal'");
        }
    }
};
