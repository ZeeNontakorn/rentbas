<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * รองรับ "ผู้จองภายนอก" — คนที่ยังไม่มีบัญชีสมาชิกในระบบ
     * เช่น จ่ายเงินผ่านไลน์แล้วแจ้งชื่อมา แอดมินกดเพิ่มชื่อเข้ารอบได้เลยโดยไม่ตัดเครดิต
     *
     * ทำให้ user_id เป็น nullable แล้วเพิ่ม guest_name
     * แถวที่เป็นสมาชิกจริง: user_id มีค่า, guest_name = null
     * แถวที่เป็นผู้จองภายนอก: user_id = null, guest_name มีค่า
     *
     * หมายเหตุ: unique index เดิม (group_round_id, user_id) ไม่ต้องแก้ —
     * MySQL/Postgres ถือว่า NULL แต่ละแถวไม่ซ้ำกัน จึงลงชื่อผู้จองภายนอกได้หลายคนในรอบเดียวกันตามปกติ
     */
    public function up(): void
    {
        Schema::table('group_round_signups', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('guest_name')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('group_round_signups', function (Blueprint $table) {
            $table->dropColumn('guest_name');
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};