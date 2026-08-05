<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            // ใช้กับรายการ "manual topup" ที่แอดมิน/พนักงานหน้าเคาน์เตอร์เติมให้ผู้ใช้โดยตรง (ไม่ผ่านหน้า
            // เว็บของลูกค้า) — บังคับระบุช่องทางรับเงินจริง + ชื่อผู้ทำรายการ เพื่อป้องกันการสวมรอย/เติมมั่ว
            // ภายในองค์กร (audit trail) รวมถึงใช้แสดงในใบเสร็จอีเมล (CreditTopupReceiptMail)
            //
            // สำหรับรายการที่มาจากคำขอเติมเครดิตด้วยตัวเอง (CreditTopupRequest ที่แอดมินอนุมัติ)
            // payment_method จะถูกคัดลอกมาจากคำขอนั้นด้วย (ดู CreditService::approveTopupRequest)
            // ส่วน processed_by_name จะเป็น null เพราะเป็นการโอนเงินเองผ่านหน้าเว็บ ไม่มีพนักงานรับเงิน
            $table->enum('payment_method', ['promptpay', 'line', 'cash_counter'])->nullable()->after('note');
            $table->string('processed_by_name')->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'processed_by_name']);
        });
    }
};
