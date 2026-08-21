<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // ตัวแยกประเภทแจ้งเตือนแบบ machine-readable — ใช้ตอนต้องเช็คจาก JS ว่าเป็นแจ้งเตือนแบบไหน
            // (เช่น 'credit_expired' ที่ต้องเด้ง SweetAlert บังคับกดรับทราบ) แยกจาก visualType() เดิมที่
            // เดาสีจาก keyword ใน title เพราะ type นี้ต้อง match เป๊ะ ไม่ใช้ heuristic
            $table->string('type')->nullable()->after('action_url');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
