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
            $exists = DB::table('settings')->where('key', 'promptpay_number')->exists();

        if (!$exists) {
            // ถ้ายังไม่มี ให้ทำการ Insert ข้อมูลใหม่
            DB::table('settings')->insert([
                'key' => 'promptpay_number',
                'value' => '0000000000', // กำหนดเบอร์พร้อมเพย์เริ่มต้นที่นี่
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
            ->where('key', 'promptpay_number')
            ->delete();
    }
};
