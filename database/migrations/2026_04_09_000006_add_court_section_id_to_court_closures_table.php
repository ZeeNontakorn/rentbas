<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * court_closures เดิมปิดทั้งสนาม (court_id) เท่านั้น
 * เพิ่ม court_section_id (nullable) เพื่อให้ปิดได้เฉพาะครึ่งใดครึ่งหนึ่งด้วย
 * - court_section_id = null  => ปิดทั้งสนาม (ทุก section ของ court นั้น)
 * - court_section_id = ระบุ  => ปิดเฉพาะ section นั้น (เช่น ปิดซ่อมเฉพาะครึ่ง A)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('court_closures', function (Blueprint $table) {
            $table->foreignId('court_section_id')
                ->nullable()
                ->after('court_id')
                ->constrained('court_sections')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('court_closures', function (Blueprint $table) {
            $table->dropConstrainedForeignId('court_section_id');
        });
    }
};
