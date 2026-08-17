<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มระบบ "จองแทนเพื่อน" — 1 สมาชิกจองได้สูงสุด 5 ที่นั่งต่อรอบ (ตัวเอง + เพื่อน)
     * แต่ละที่นั่งเก็บเป็นชื่อ (เหมือนผู้จองภายนอก) แต่ผูกกับ booked_by = บัญชีคนที่จ่ายเงิน/จองให้
     */
    public function up(): void
    {
        Schema::table('group_round_signups', function (Blueprint $table) {
            $table->foreignId('booked_by')
                ->nullable()
                ->after('added_by')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('group_round_signups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booked_by');
        });
    }
};
