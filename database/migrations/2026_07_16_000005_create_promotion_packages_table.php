<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // แพ็กเกจโปรโมชั่นราคาคงที่ (ไม่ได้คิดตามชั่วโมง x rate ปกติ) — A/B/C ตาม image.png
        Schema::create('promotion_packages', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // personal_general, personal_student, group_full, group_half, private_group
            $table->string('label');
            $table->enum('category', ['personal', 'group', 'private'])->default('personal');
            $table->unsignedTinyInteger('duration_hours')->default(2);
            $table->unsignedSmallInteger('max_people')->default(1);
            $table->unsignedInteger('base_price');            // ราคาปกติ (สตางค์)
            $table->unsignedInteger('holiday_price')->nullable();       // ราคาวันหยุดนักขัตฤกษ์
            $table->unsignedInteger('weekend_special_price')->nullable(); // ราคาเสาร์-อาทิตย์ช่วงพิเศษ
            $table->time('weekend_special_start')->nullable(); // เช่น 07:00
            $table->time('weekend_special_end')->nullable();   // เช่น 11:00
            $table->boolean('requires_verification')->default(false); // ต้องแสดงบัตรนักเรียน/นักศึกษา
            $table->unsignedTinyInteger('session_count')->nullable();  // เช่น private = 4 ครั้ง
            $table->unsignedSmallInteger('validity_days')->nullable(); // เช่น อายุคอร์ส 60 วัน
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_packages');
    }
};
