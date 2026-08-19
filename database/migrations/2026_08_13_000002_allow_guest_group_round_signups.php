<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_round_signups', function (Blueprint $table) {
            // 1. เช็กว่ามี Foreign Key นี้จริงไหม ถ้ามีค่อยสั่ง Drop
            if ($this->hasForeignKey('group_round_signups', 'group_round_signups_user_id_foreign')) {
                $table->dropForeign(['user_id']);
            }

            // 2. เปลี่ยนให้ user_id เป็น nullable
            $table->foreignId('user_id')->nullable()->change();
            
            // 3. เพิ่มคอลัมน์ guest_name ถ้ายังไม่มี
            if (!Schema::hasColumn('group_round_signups', 'guest_name')) {
                $table->string('guest_name')->nullable()->after('user_id');
            }

            // 4. ผูก Foreign Key กลับเข้าไปใหม่ (เช็กก่อนว่ามีแล้วหรือยัง)
            if (!$this->hasForeignKey('group_round_signups', 'group_round_signups_user_id_foreign')) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('group_round_signups', function (Blueprint $table) {
            if ($this->hasForeignKey('group_round_signups', 'group_round_signups_user_id_foreign')) {
                $table->dropForeign(['user_id']);
            }
            
            if (Schema::hasColumn('group_round_signups', 'guest_name')) {
                $table->dropColumn('guest_name');
            }

            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * ฟังก์ชันสำหรับเช็กว่ามี Foreign Key ในตารางหรือไม่
     */
    private function hasForeignKey(string $table, string $foreignKeyName): bool
    {
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = ? 
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
              AND CONSTRAINT_NAME = ?
        ", [$table, $foreignKeyName]);

        return count($foreignKeys) > 0;
    }
};