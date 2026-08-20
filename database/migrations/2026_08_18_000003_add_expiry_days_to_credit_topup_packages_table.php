<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_topup_packages', function (Blueprint $table) {
            // จำนวนวันที่เครดิตจากแพ็กเกจนี้จะหมดอายุหลังเติม — null แปลว่าไม่มีวันหมดอายุ
            // แพ็กเกจเดิม (250/500/800/1600) เริ่มต้นเป็น null ทั้งหมด แอดมินตั้งค่าจริงทีหลังผ่านหน้าจัดการแพ็กเกจ
            $table->unsignedInteger('expiry_days')->nullable()->after('credit_satang');
        });
    }

    public function down(): void
    {
        Schema::table('credit_topup_packages', function (Blueprint $table) {
            $table->dropColumn('expiry_days');
        });
    }
};
