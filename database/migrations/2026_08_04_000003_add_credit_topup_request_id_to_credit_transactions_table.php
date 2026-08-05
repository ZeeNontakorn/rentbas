<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            // ถ้ารายการนี้เกิดจากการอนุมัติคำขอเติมเครดิตของผู้ใช้ (ไม่ใช่ manual topup โดยแอดมิน)
            // จะผูกไว้กับคำขอนั้นเพื่อ audit ย้อนหลังได้ว่ารายการเงินนี้มาจากคำขอไหน สลิปอะไร
            $table->foreignId('credit_topup_request_id')->nullable()->after('admin_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('credit_topup_request_id');
        });
    }
};
