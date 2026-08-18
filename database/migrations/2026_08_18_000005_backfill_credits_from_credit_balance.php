<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ย้ายยอด users.credit_balance เดิมของแต่ละคนมาเป็นก้อนเครดิต (credits) หนึ่งก้อน
     * ไม่หมดอายุ (expires_at = null) เพื่อรักษาพฤติกรรมเดิมของระบบก่อนมี expiry
     * ใช้ raw query ล้วน ไม่ผ่าน Eloquent model กัน side effect จาก cast/event ตอน migrate
     */
    public function up(): void
    {
        $now = now();

        DB::table('users')
            ->where('credit_balance', '>', 0)
            ->orderBy('id')
            ->chunkById(500, function ($users) use ($now) {
                $rows = $users->map(fn ($user) => [
                    'user_id' => $user->id,
                    'amount_satang' => $user->credit_balance,
                    'remaining_satang' => $user->credit_balance,
                    'expires_at' => null,
                    'source' => 'migration_seed',
                    'note' => 'ย้ายจากยอดเครดิตเดิม (users.credit_balance) ก่อนระบบมีวันหมดอายุ',
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('credits')->insert($rows);
            });
    }

    /**
     * ย้อนกลับไม่ได้ทั้งหมด: ถ้ามีการหัก/เติมเครดิตเกิดขึ้นแล้วหลัง backfill จะไม่สามารถคำนวณ
     * ยอด credit_balance เดิมกลับคืนได้อย่างถูกต้อง จึงลบเฉพาะก้อนที่ยัง untouched (remaining == amount)
     */
    public function down(): void
    {
        DB::table('credits')
            ->where('source', 'migration_seed')
            ->whereColumn('remaining_satang', '=', 'amount_satang')
            ->delete();
    }
};
