<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            // คนที่เขียนรีวิว
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // ที่มาของสิทธิ์รีวิว — ต้องมาจากการใช้บริการจริงอย่างใดอย่างหนึ่งเท่านั้น
            // booking_id = รีวิวสถานที่ (สนาม/คาเฟ่/ห้องน้ำ/บริการ/ความสะอาด)
            $table->foreignId('booking_id')->nullable()->constrained()->cascadeOnDelete();
            // private_training_booking_id = รีวิวโค้ชหลังเรียน Private จบ
            $table->foreignId('private_training_booking_id')->nullable()->constrained()->cascadeOnDelete();
            // โค้ชที่ถูกรีวิว — เก็บซ้ำไว้ตรงนี้ (denormalize) เพื่อรวมคะแนนต่อโค้ชได้โดยไม่ต้อง join
            // ผ่าน private_training_bookings ทุกครั้ง null สำหรับรีวิวสถานที่
            $table->foreignId('coach_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();

            // 1 การจอง = รีวิวได้ครั้งเดียว
            // MySQL ยอมให้ค่า NULL ซ้ำกันใน unique index ได้ สอง index นี้จึงไม่ตีกันเอง
            // (รีวิวสถานที่จะมี private_training_booking_id เป็น NULL และกลับกัน)
            $table->unique(['user_id', 'booking_id']);
            $table->unique(['user_id', 'private_training_booking_id']);

            $table->index(['coach_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
