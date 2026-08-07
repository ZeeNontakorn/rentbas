<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $exists = DB::table('settings')->where('key', 'promptpay_name')->exists();

        if (!$exists) {
            // ถ้ายังไม่มี ให้ทำการ Insert ข้อมูลใหม่
            DB::table('settings')->insert([
                'key' => 'promptpay_name',
                'value' => 'ชื่อบัญชี PromptPay', // กำหนดชื่อบัญชี PromptPay เริ่มต้นที่นี่
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ลบข้อมูลออกเมื่อสั่ง Rollback (เพื่อคืนค่าฐานข้อมูลให้กลับไปเหมือนก่อนรัน Migration)
        DB::table('settings')
            ->where('key', 'promptpay_name')
            ->delete();
    }
};
