<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // topup = แอดมินเติมเครดิตให้ผู้ใช้ | deduct = หักเครดิตตอนจ่ายค่าจอง
            // refund = คืนเครดิตกรณีจองไม่สำเร็จ/ถูกปฏิเสธหลังหักไปแล้ว
            $table->enum('type', ['topup', 'deduct', 'refund'])->default('topup');
            // จำนวนเงินหน่วยสตางค์ เก็บเป็นค่าบวกเสมอ ทิศทาง +/- ดูจาก type
            $table->unsignedBigInteger('amount');
            // ยอดคงเหลือหลังทำรายการนี้ (snapshot) เอาไว้ตรวจสอบ/ออดิทย้อนหลังได้โดยไม่ต้องคำนวณสะสมใหม่
            $table->unsignedBigInteger('balance_after');
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            // แอดมินที่กดเติมเครดิตให้ (null ถ้าเป็นรายการที่ระบบสร้างเอง เช่น deduct/refund จากการจอง)
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
