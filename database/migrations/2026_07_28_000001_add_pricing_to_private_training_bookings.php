<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มฟิลด์ราคา/โปรโมชั่น/สถานะจ่ายเงิน ให้ private_training_bookings
 *
 * ราคาคำนวณ "ตอนแอดมินจัดสนามให้" (assignCourt) ไม่ใช่ตอนลูกค้าส่งคำขอ (store)
 * เพราะราคาต้องรู้ court_type (เต็มสนาม/ครึ่งสนาม) ก่อน ซึ่งจะรู้ก็ต่อเมื่อแอดมิน
 * เลือก section ให้แล้วเท่านั้น — ก่อนหน้านั้นฟิลด์เหล่านี้จะเป็น null ทั้งหมด
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('private_training_bookings', function (Blueprint $table) {
            $table->foreignId('promotion_package_id')->nullable()->after('note')
                ->constrained('promotion_packages')->nullOnDelete();
            $table->unsignedBigInteger('price')->nullable()->after('promotion_package_id'); // หน่วยสตางค์
            $table->json('price_breakdown')->nullable()->after('price');
            $table->foreignId('pricing_rule_id')->nullable()->after('price_breakdown')
                ->constrained('pricing_rules')->nullOnDelete();
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid')->after('pricing_rule_id');
        });
    }

    public function down(): void
    {
        Schema::table('private_training_bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_package_id');
            $table->dropConstrainedForeignId('pricing_rule_id');
            $table->dropColumn(['price', 'price_breakdown', 'payment_status']);
        });
    }
};
