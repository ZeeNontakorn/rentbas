<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มค่ากำหนด "ความละเอียดของเวลา" ต่อสนาม เพื่อรองรับเวลาที่ไม่เต็มชั่วโมง
 * - slot_interval_minutes: ผู้ใช้เลือกเวลาเริ่ม/จบได้ทีละกี่นาที เช่น 30 = เลือกได้แค่ :00 หรือ :30
 * - min_booking_minutes: ระยะเวลาจองขั้นต่ำต่อ 1 รายการ
 *
 * เก็บเป็นค่าต่อสนาม (ไม่ใช้ global setting กลาง) เพราะแต่ละสนาม/ประเภทกีฬาอาจ
 * ต้องการความละเอียดไม่เท่ากัน (เช่น สนามหนึ่งจองได้ทีละ 30 นาที อีกสนามขั้นต่ำ 60 นาที)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courts', function (Blueprint $table) {
            $table->unsignedSmallInteger('slot_interval_minutes')->default(30)->after('court_status');
            $table->unsignedSmallInteger('min_booking_minutes')->default(60)->after('slot_interval_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('courts', function (Blueprint $table) {
            $table->dropColumn(['slot_interval_minutes', 'min_booking_minutes']);
        });
    }
};
