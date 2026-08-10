<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_topup_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // แพ็กเกจที่เลือก (null ถ้าเป็นการกรอกจำนวนเงินเอง / custom amount)
            $table->foreignId('credit_topup_package_id')->nullable()->constrained()->nullOnDelete();
            // ยอดที่ต้องจ่าย และเครดิตที่จะได้รับ (หน่วยสตางค์) — snapshot ไว้ตอนยื่นคำขอ กันแพ็กเกจถูกแก้ทีหลังแล้วยอดเพี้ยน
            $table->unsignedBigInteger('price_satang');
            $table->unsignedBigInteger('credit_satang');
            // ช่องทางชำระเงินที่ผู้ใช้เลือกในขั้นตอนสุดท้าย
            $table->enum('payment_method', ['promptpay', 'line', 'cash_counter'])->default('promptpay');
            // สลิปการโอนเงิน (จำเป็นเฉพาะช่องทาง promptpay)
            $table->string('slip_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            // แอดมินที่อนุมัติ/ปฏิเสธคำขอนี้
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejected_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_topup_requests');
    }
};
