<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // แก้ enum ให้รองรับค่า 'admin' เพิ่ม — ต้องเขียน raw SQL เพราะ Laravel Schema Builder
        // ไม่มี method แก้ enum values ตรงๆ (เปลี่ยน column type ทั้ง column แทน)
        DB::statement("ALTER TABLE users MODIFY membership_type ENUM('customer', 'sponsor', 'student', 'permanent', 'temporary', 'intern', 'admin') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY membership_type ENUM('customer', 'sponsor', 'student', 'permanent', 'temporary', 'intern') NOT NULL");
    }
};
