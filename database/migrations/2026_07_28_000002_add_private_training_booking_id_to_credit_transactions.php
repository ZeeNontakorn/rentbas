<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * credit_transactions.booking_id ผูก FK ไว้กับตาราง bookings (จองสนาม) โดยเฉพาะอยู่แล้ว
 * เอาไปใช้อ้างอิง private_training_bookings ไม่ได้ (คนละตารางกัน) จึงต้องเพิ่มคอลัมน์
 * แยกต่างหากสำหรับการหักเครดิต/คืนเครดิตของ Private Training โดยเฉพาะ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->foreignId('private_training_booking_id')->nullable()->after('booking_id')
                ->constrained('private_training_bookings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('private_training_booking_id');
        });
    }
};
