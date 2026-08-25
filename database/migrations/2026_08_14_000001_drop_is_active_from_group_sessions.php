<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เอาฟีเจอร์ "ปิดใช้งาน/เปิดใช้งาน" เทมเพลตรอบประจำออกทั้งหมด
     * เพราะไม่ได้มีผลจำกัดการทำงานจริง (แค่ badge เฉยๆ) จึงตัดออกให้เรียบง่ายขึ้น
     */
    public function up(): void
    {
        Schema::table('group_sessions', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('group_sessions', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });
    }
};