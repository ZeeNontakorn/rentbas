<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ตาราง pivot บันทึกว่า transaction แต่ละรายการหัก/เติมก้อนเครดิต (credits) ไหนไปเท่าไหร่
        // ใช้ตอนหักเครดิตที่อาจต้องดึงจากหลายก้อนพร้อมกัน (FIFO by expiry) เพื่อ trace ย้อนหลังได้
        Schema::create('credit_transaction_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_satang');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transaction_lots');
    }
};
