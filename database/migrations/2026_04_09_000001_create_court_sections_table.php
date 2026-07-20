<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สนาม 1 สนาม (courts) สามารถถูกแบ่งออกเป็นหลาย "ส่วนที่จองได้" (section)
 * เช่น เต็มสนาม (full), ครึ่งซ้าย (a), ครึ่งขวา (b)
 * แต่ละ booking ในอนาคตจะอ้างอิงไปที่ court_section แทนที่จะอ้าง court ตรงๆ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_sections', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);   // 'full' | 'a' | 'b' | ... (ขยายได้ในอนาคตถ้าแบ่งมากกว่า 2 ส่วน)
            $table->string('name');       // ชื่อที่แสดงผล เช่น "เต็มสนาม", "ครึ่งซ้าย"
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['court_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_sections');
    }
};
