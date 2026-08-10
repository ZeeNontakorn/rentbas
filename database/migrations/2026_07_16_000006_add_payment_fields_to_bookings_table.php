<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // ขยาย enum เดิม (pending, approved, rejected, cancelled) เพิ่ม:
            // pending_payment = กำลังล็อกช่วงเวลาไว้รอชำระเงิน (ภายใน 15 นาที)
            // expired          = หมดเวลาชำระเงิน ระบบยกเลิกอัตโนมัติ
            $table->string('status', 20)->default('pending')->change();

            // ที่มาของการจอง: บอกว่าควรอยู่ใน flow ไหน และมีลำดับความสำคัญยังไงตอนชนกัน
            // manual = จองแบบเดิม รอแอดมินอนุมัติ (priority ต่ำสุด)
            // credit = จ่ายด้วยเครดิต อนุมัติอัตโนมัติทันที
            // promptpay = จ่ายผ่าน QR พร้อมเพย์ (WIP)
            $table->enum('booking_source', ['manual', 'credit', 'promptpay'])->default('manual')->after('status');

            $table->enum('payment_method', ['credit', 'promptpay'])->nullable()->after('booking_source');
            $table->enum('payment_status', ['unpaid', 'pending_slip', 'paid', 'failed', 'refunded'])
                ->default('unpaid')->after('payment_method');

            $table->unsignedInteger('price')->nullable()->after('payment_status'); // ราคาสุทธิ หน่วยสตางค์
            $table->foreignId('pricing_rule_id')->nullable()->after('price')->constrained()->nullOnDelete();
            $table->foreignId('promotion_package_id')->nullable()->after('pricing_rule_id')->constrained()->nullOnDelete();

            // เวลาที่ล็อกสล็อตไว้จะหมดอายุ (now() + 15 นาที ตอนเริ่ม checkout)
            $table->timestamp('locked_until')->nullable()->after('promotion_package_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['pricing_rule_id']);
            $table->dropForeign(['promotion_package_id']);
            $table->dropColumn([
                'booking_source', 'payment_method', 'payment_status',
                'price', 'pricing_rule_id', 'promotion_package_id', 'locked_until',
            ]);
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending')->change();
        });
    }
};
