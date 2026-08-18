<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_topup_requests', function (Blueprint $table) {
            // snapshot จำนวนวันหมดอายุจากแพ็กเกจไว้ตอนยื่นคำขอ กันแพ็กเกจถูกแก้ทีหลังแล้วยอดเพี้ยน
            // (แพทเทิร์นเดียวกับ price_satang/credit_satang ด้านบน)
            $table->unsignedInteger('expiry_days')->nullable()->after('credit_satang');
        });
    }

    public function down(): void
    {
        Schema::table('credit_topup_requests', function (Blueprint $table) {
            $table->dropColumn('expiry_days');
        });
    }
};
