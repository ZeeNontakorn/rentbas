<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('private_training_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // ผู้ใช้ (role=staff, membership_type=coach) ที่ถูกจอง — อ้างอิงตาราง users ตัวเดียวกับที่ admin ใช้จัดการ "โค้ช และผู้ช่วย"
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('note', 500)->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();

            $table->index(['coach_id', 'date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_training_bookings');
    }
};