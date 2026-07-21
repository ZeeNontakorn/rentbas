<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY membership_type ENUM('customer','sponsor','student','permanent','temporary','intern','admin', 'coach','court_assistant') DEFAULT 'customer'");
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET membership_type = 'permanent' WHERE membership_type IN ('coach','court_assistant')");
        DB::statement("ALTER TABLE users MODIFY membership_type ENUM('customer','sponsor','student','permanent','temporary','intern') DEFAULT 'customer'");
    }
};
