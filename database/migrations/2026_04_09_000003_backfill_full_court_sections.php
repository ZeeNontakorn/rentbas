<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * สนามที่มีอยู่แล้วทุกสนาม (courts เดิมทั้งหมด) ยังไม่มี section ใดๆ เลย
 * migration นี้สร้าง section "เต็มสนาม" (code = full) ให้อัตโนมัติ เพื่อให้ระบบเดิม
 * ที่จองแบบเต็มสนามอยู่แล้ว ใช้งานต่อได้ทันทีโดยไม่พัง (ยังไม่มีครึ่งสนามให้เลือกจนกว่า
 * แอดมินจะไปเพิ่ม section 'a'/'b' เองทีหลังผ่านหน้าจัดการสนาม)
 */
return new class extends Migration
{
    public function up(): void
    {
        $courts = DB::table('courts')->get(['id']);

        foreach ($courts as $court) {
            $exists = DB::table('court_sections')
                ->where('court_id', $court->id)
                ->where('code', 'full')
                ->exists();

            if ($exists) {
                continue;
            }

            $fullSectionId = DB::table('court_sections')->insertGetId([
                'court_id'   => $court->id,
                'code'       => 'full',
                'name'       => 'เต็มสนาม',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // เต็มสนามบล็อกตัวเอง (จะบล็อกครึ่ง a/b เพิ่มด้วยทีหลัง ตอนสร้าง section เหล่านั้น)
            DB::table('court_section_blocks')->insert([
                'court_section_id'  => $fullSectionId,
                'blocks_section_id' => $fullSectionId,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    public function down(): void
    {
        // ย้อนกลับแบบระมัดระวัง: ลบเฉพาะ section 'full' ที่ไม่มี booking อ้างอิงอยู่
        $fullSections = DB::table('court_sections')->where('code', 'full')->get(['id']);

        foreach ($fullSections as $section) {
            $inUse = DB::table('bookings')->where('court_section_id', $section->id)->exists();
            if ($inUse) {
                continue;
            }

            DB::table('court_section_blocks')
                ->where('court_section_id', $section->id)
                ->orWhere('blocks_section_id', $section->id)
                ->delete();

            DB::table('court_sections')->where('id', $section->id)->delete();
        }
    }
};
