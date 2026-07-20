<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_schedules', function (Blueprint $table) {
            // ใช้ section เพื่อระบุได้ทั้งเต็มสนาม/ครึ่งสนาม โดยไม่ไปปิดสนามอัตโนมัติ
            $table->foreignId('court_section_id')->nullable()->after('course_id')->constrained('court_sections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('course_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('court_section_id');
        });
    }
};
