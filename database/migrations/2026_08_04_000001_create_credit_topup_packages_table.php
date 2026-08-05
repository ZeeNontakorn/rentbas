<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_topup_packages', function (Blueprint $table) {
            $table->id();
            // ป้ายชื่อที่แสดงให้ผู้ใช้เห็น เช่น "250"
            $table->string('label');
            // ยอดเงินที่ผู้ใช้ต้องจ่าย (หน่วยสตางค์)
            $table->unsignedBigInteger('price_satang');
            // เครดิตที่ผู้ใช้จะได้รับ (หน่วยสตางค์) — ถ้ามากกว่า price_satang แปลว่ามีโบนัส/โปรโมชั่นให้
            $table->unsignedBigInteger('credit_satang');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // แพ็กเกจเริ่มต้น 250 / 500 / 800 / 1600 บาท (ไม่มีโบนัส ปรับได้ภายหลังในหน้าแอดมิน)
        $now = now();
        DB::table('credit_topup_packages')->insert([
            ['label' => '250', 'price_satang' => 25000, 'credit_satang' => 25000, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['label' => '500', 'price_satang' => 50000, 'credit_satang' => 50000, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['label' => '800', 'price_satang' => 80000, 'credit_satang' => 80000, 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['label' => '1600', 'price_satang' => 160000, 'credit_satang' => 160000, 'is_active' => true, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_topup_packages');
    }
};
