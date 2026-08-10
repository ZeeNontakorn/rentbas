<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // เก็บเป็นสตางค์ (integer, หน่วยสตางค์) เพื่อเลี่ยงปัญหา float rounding กับเงิน
            // เวลาแสดงผลค่อยหาร 100 ตอน format เป็นบาท
            $table->unsignedBigInteger('credit_balance')->default(0)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('credit_balance');
        });
    }
};
