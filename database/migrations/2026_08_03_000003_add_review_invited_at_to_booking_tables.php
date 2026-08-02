<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เวลาที่ระบบยิงแจ้งเตือนชวนให้มารีวิวไปแล้ว — กันยิงซ้ำทุกครั้งที่ผู้ใช้เปิดหน้าประวัติ
     * null = ยังไม่เคยชวน
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('review_invited_at')->nullable()->after('locked_until');
        });

        Schema::table('private_training_bookings', function (Blueprint $table) {
            $table->timestamp('review_invited_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('review_invited_at');
        });

        Schema::table('private_training_bookings', function (Blueprint $table) {
            $table->dropColumn('review_invited_at');
        });
    }
};
