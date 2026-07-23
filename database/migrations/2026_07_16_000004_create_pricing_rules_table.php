<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ราคาต่อชั่วโมงตามช่วงเวลา (Sunset / Weekday / Holiday) x ประเภทสนาม (Full/Half)
        // แอดมินแก้ price_per_hour ได้จากหน้า admin โดยไม่ต้องแก้โค้ด
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // เช่น sunset_full, weekday_half, holiday_full
            $table->string('label');          // ชื่อแสดงผล เช่น "Sunset Time - Full Court"
            $table->enum('day_type', ['weekday', 'weekend', 'holiday', 'everyday'])->default('everyday');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('court_type', ['full', 'half'])->default('full');
            $table->unsignedInteger('price_per_hour'); // หน่วยสตางค์
            // ลำดับความสำคัญเวลามีหลายกฎที่ match ช่วงเวลาเดียวกัน (มากกว่า = สำคัญกว่า)
            $table->unsignedTinyInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['day_type', 'court_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
