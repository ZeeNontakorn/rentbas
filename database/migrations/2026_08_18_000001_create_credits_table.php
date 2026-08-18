<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // ยอดเดิมตอนสร้างก้อนนี้ (หน่วยสตางค์)
            $table->unsignedBigInteger('amount_satang');
            // ยอดคงเหลือของก้อนนี้ ถูกหักลงเรื่อยๆ ตอนใช้จ่าย
            $table->unsignedBigInteger('remaining_satang');
            // วันหมดอายุของก้อนนี้ — null แปลว่าไม่มีวันหมดอายุ
            $table->timestamp('expires_at')->nullable();
            // ที่มาของก้อนเครดิตนี้: topup_request | admin_manual | refund | migration_seed
            $table->string('source');
            $table->foreignId('credit_topup_request_id')->nullable()->constrained()->nullOnDelete();
            // แอดมินที่เป็นคนให้เครดิตก้อนนี้ (admin_manual / refund โดยแอดมิน)
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            // ใช้เรียงหาก้อนที่ใกล้หมดอายุที่สุดก่อนตอนหักเครดิต (FIFO by expiry)
            $table->index(['user_id', 'expires_at']);
            $table->index(['user_id', 'remaining_satang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
