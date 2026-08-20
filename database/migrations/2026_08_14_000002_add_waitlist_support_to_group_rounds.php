<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    /**
     * เพิ่มระบบ "สำรอง" (waitlist) ให้รอบกลุ่มเล่นบาส:
     *
     * - group_rounds.cancel_deadline      = เดดไลน์ที่สมาชิกยังยกเลิกจองเองได้ (วัน+เวลา)
     * - group_rounds.reserves_processed_at = เวลาที่ระบบคืนเครดิตให้สำรองที่เหลือไปแล้ว (กันประมวลผลซ้ำ)
     * - group_round_signups.is_reserve     = true ถ้าเป็นคิวสำรอง (ลงชื่อตอนที่ตัวจริงเต็มแล้ว)
     *
     * ทำไมใช้ is_reserve แยกต่างหาก แทนการเทียบ order_number > max_players:
     * เพราะ order_number ของแถวเดิมจะไม่ถูกไล่เลขใหม่เวลามีคนยกเลิก (เพื่อรักษาลำดับเวลาจริง)
     * ถ้าใช้แค่ order_number เทียบ max_players เฉยๆ จะไม่รู้ว่าตอนนี้ "ใครคือตัวจริงจริงๆ"
     * หลังมีการเลื่อนสำรองขึ้นมาแทนที่คนที่ยกเลิกไปแล้ว
     */
    public function up(): void
    {
        Schema::table('group_rounds', function (Blueprint $table) {
            if (!Schema::hasColumn('group_rounds', 'cancel_deadline')) {
                $table->dateTime('cancel_deadline')->nullable()->after('credit_cost');
            }
            if (!Schema::hasColumn('group_rounds', 'reserves_processed_at')) {
                $table->timestamp('reserves_processed_at')->nullable()->after('cancel_deadline');
            }
        });

        Schema::table('group_round_signups', function (Blueprint $table) {
            if (!Schema::hasColumn('group_round_signups', 'is_reserve')) {
                $table->boolean('is_reserve')->default(false)->after('order_number');
            }
        });

        // ตั้งค่า is_reserve ให้ข้อมูลเก่าที่มีอยู่แล้ว: ใครลำดับเกิน max_players ของรอบตัวเอง = สำรอง
        // รันซ้ำได้ปลอดภัย เพราะเป็นการคำนวณใหม่ทุกครั้ง ไม่ใช่การเพิ่มข้อมูลซ้ำ
        DB::statement('
            UPDATE group_round_signups s
            INNER JOIN group_rounds r ON r.id = s.group_round_id
            SET s.is_reserve = (s.order_number > r.max_players)
        ');
    }

    public function down(): void
    {
        Schema::table('group_round_signups', function (Blueprint $table) {
            if (Schema::hasColumn('group_round_signups', 'is_reserve')) {
                $table->dropColumn('is_reserve');
            }
        });
        Schema::table('group_rounds', function (Blueprint $table) {
            $columns = array_filter(['cancel_deadline', 'reserves_processed_at'], function ($col) {
                return Schema::hasColumn('group_rounds', $col);
            });
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};