<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTE: จงใจแยก migration นี้ออกจากชุด backfill (2026_08_18_000005) — ห้าม deploy migration นี้
     * จนกว่าจะ verify ใน production แล้วว่า SUM(credits.remaining_satang) ต่อ user ตรงกับ
     * users.credit_balance เดิมก่อน backfill ครบทุกคน (ดูรายละเอียดใน plan การย้ายระบบเครดิต)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('credit_balance');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('credit_balance')->default(0)->after('role');
        });
    }
};
