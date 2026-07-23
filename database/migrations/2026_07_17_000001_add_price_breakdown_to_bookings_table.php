<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เก็บรายละเอียดการคำนวณราคา ณ ตอนจอง (breakdown จาก PricingService::calculate())
 * เป็น JSON ติดไว้กับ booking โดยตรง แทนที่จะคำนวณราคาใหม่ตอนออกใบเสร็จ
 *
 * เหตุผล: ใบเสร็จต้องแสดงยอดที่ "เคยเก็บเงินจริง" ณ เวลานั้น ถ้าคำนวณใหม่ตอนส่งอีเมล
 * แล้วแอดมินดันไปแก้ราคาใน pricing_rules/promotion_packages ไปแล้วก่อนหน้า ใบเสร็จจะ
 * แสดงราคาผิดเพี้ยนไปจากที่ลูกค้าจ่ายจริง
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->json('price_breakdown')->nullable()->after('promotion_package_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('price_breakdown');
        });
    }
};
