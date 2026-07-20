<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม court_section_id ให้ bookings (nullable ก่อน เพื่อไม่ให้ของเดิมพังระหว่าง deploy)
 * แล้ว backfill ให้ booking เก่าทุกอันชี้ไปที่ section 'full' ของ court เดิมที่มันอ้างอิงอยู่
 *
 * ยังคง court_id ไว้เหมือนเดิมโดยตั้งใจ (ไม่ลบ) เพื่อไม่ต้องแก้โค้ดเก่าที่ query
 * ผ่าน court_id ทั้งหมดพร้อมกันในรอบเดียว — ค่อยย้ายไปใช้ court_section_id เป็นหลัก
 * และ deprecate court_id ใน migration ถัดไปหลังแก้โค้ด backend ทั้งหมดเสร็จแล้ว
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('court_section_id')
                ->nullable()
                ->after('court_id')
                ->constrained('court_sections')
                ->nullOnDelete();
        });

        DB::table('bookings')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                $sectionId = DB::table('court_sections')
                    ->where('court_id', $row->court_id)
                    ->where('code', 'full')
                    ->value('id');

                if ($sectionId) {
                    DB::table('bookings')->where('id', $row->id)->update([
                        'court_section_id' => $sectionId,
                    ]);
                }
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['court_section_id', 'booking_date', 'status'], 'bookings_section_date_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_section_date_status_idx');
            $table->dropConstrainedForeignId('court_section_id');
        });
    }
};
