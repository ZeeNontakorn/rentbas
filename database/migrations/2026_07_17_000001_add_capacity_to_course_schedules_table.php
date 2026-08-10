<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ capacity (จำนวนคนสูงสุด) ให้แต่ละรอบเวลาเรียน (course_schedules)
     * ใช้คู่กับ is_limited_spots: ถ้า is_limited_spots = true ต้องมีค่า capacity เสมอ
     * ถ้า is_limited_spots = false, capacity จะเป็น NULL (ไม่จำกัด)
     *
     * หมายเหตุ (สำหรับอนาคต): เมื่อทำระบบให้ user กดซื้อคอร์ส/จองคิวแล้ว
     * ควรมีตาราง course_bookings (หรือ course_purchases) แยกต่างหาก ที่มี
     * course_schedule_id, user_id, status (pending/paid/cancelled) เป็นต้น
     * แล้วนับจำนวนคนที่จองจริงจากตารางนั้น (booked_count) เทียบกับ capacity ที่นี่
     * เพื่อคำนวณจำนวนที่นั่งคงเหลือ (remaining spots) แบบเรียลไทม์
     * ยังไม่สร้างตารางนี้ในรอบนี้ เนื่องจากต้องตกลงเรื่อง flow การชำระเงินกับทีมก่อน
     */
    public function up(): void
    {
        Schema::table('course_schedules', function (Blueprint $table) {
            $table->unsignedInteger('capacity')->nullable()->after('is_limited_spots');
        });
    }

    public function down(): void
    {
        Schema::table('course_schedules', function (Blueprint $table) {
            $table->dropColumn('capacity');
        });
    }
};
