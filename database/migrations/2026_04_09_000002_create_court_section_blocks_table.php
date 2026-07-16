<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตาราง "blocking matrix" — บอกว่าเมื่อ section A ถูกจอง แล้ว section ไหนบ้าง
 * ที่ถือว่าไม่ว่างไปด้วย (รวมตัวเองด้วยเสมอ)
 *
 * ตัวอย่างสนามที่แบ่งเป็นเต็ม/ครึ่งซ้าย/ครึ่งขวา:
 *   full  blocks  full, a, b
 *   a     blocks  a, full
 *   b     blocks  b, full
 *
 * เก็บเป็นตารางแทนการ hardcode ในโค้ด เพราะบางสนาม (เช่น แบดมินตัน) อาจแบ่งได้
 * มากกว่า 2 ส่วน หรือมีกติกาการบล็อกที่ไม่สมมาตรกันในอนาคต
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_section_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_section_id')->constrained('court_sections')->cascadeOnDelete();
            $table->foreignId('blocks_section_id')->constrained('court_sections')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['court_section_id', 'blocks_section_id'], 'court_section_blocks_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_section_blocks');
    }
};
