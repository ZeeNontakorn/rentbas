<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ระบบ "จอย กลุ่มเล่นบาสค่ำ"
     *
     * 3 ตาราง:
     * 1. group_sessions        = เทมเพลตรอบประจำ (เช่น "กลุ่มเล่นบาสค่ำ อังคาร 18:00-20:00")
     * 2. group_rounds          = รอบที่ถูกเปิดจริงในแต่ละวัน (สร้างจากเทมเพลต หรือเปิดแบบ one-time)
     * 3. group_round_signups   = รายชื่อคนลงเล่นในแต่ละรอบ เรียงตามเวลาที่ลงชื่อจริง
     *
     * หมายเหตุสำคัญ: migration นี้ "สมมติ" ว่ามีตาราง `users` ที่มีคอลัมน์เครดิตอยู่แล้ว
     * และมีตาราง `courts` (จากระบบจองสนามเดิม) — ถ้าชื่อคอลัมน์/ตารางไม่ตรง ให้แจ้งแล้วผมแก้ให้ตรงได้เลย
     */
    public function up(): void
    {
        // 1) เทมเพลตรอบประจำ
        Schema::create('group_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // เช่น "กลุ่มเล่นบาสค่ำ"
            $table->unsignedTinyInteger('day_of_week'); // 0 = อาทิตย์ ... 2 = อังคาร, 6 = เสาร์ (ตาม Carbon)
            $table->time('start_time'); // เช่น 18:00
            $table->time('end_time');   // เช่น 20:00
            $table->foreignId('court_id')->nullable()->constrained('courts')->nullOnDelete();
            $table->unsignedSmallInteger('max_players')->default(25);
            $table->unsignedInteger('credit_cost')->default(0); // เครดิตต่อคนต่อรอบ
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2) รอบที่เปิดจริง (instance ของแต่ละสัปดาห์ หรือรอบ one-time)
        Schema::create('group_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_session_id')->nullable()
                ->constrained('group_sessions')->nullOnDelete(); // null = รอบ one-time ที่ไม่ได้มาจากเทมเพลต
            $table->string('title'); // เช่น "กลุ่มเล่นบาสค่ำ อังคาร 25 คน สนาม A เทา"
            $table->date('play_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('court_id')->nullable()->constrained('courts')->nullOnDelete();
            $table->unsignedSmallInteger('max_players')->default(25);
            $table->unsignedInteger('credit_cost')->default(0);
            $table->enum('status', ['open', 'closed', 'completed', 'cancelled'])->default('open');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['play_date', 'status']);
        });

        // 3) รายชื่อคนลงเล่นในแต่ละรอบ
        Schema::create('group_round_signups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_round_id')->constrained('group_rounds')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('order_number'); // ลำดับที่ลงเล่น 1-25 ตามเวลาจริง
            $table->unsignedInteger('credit_used')->default(0);
            $table->enum('status', ['confirmed', 'cancelled'])->default('confirmed');
            $table->timestamp('signed_up_at'); // เวลาลงชื่อจริง (ใช้ตัดสินลำดับ)
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete(); // ถ้าแอดมินเป็นคนเพิ่มให้
            $table->timestamps();

            $table->unique(['group_round_id', 'user_id']); // 1 คนลงชื่อได้ 1 ครั้งต่อรอบ
            $table->index(['group_round_id', 'order_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_round_signups');
        Schema::dropIfExists('group_rounds');
        Schema::dropIfExists('group_sessions');
    }
};