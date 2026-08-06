<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE private_training_bookings MODIFY payment_status ENUM('unpaid', 'paid', 'paid_by_package') NOT NULL DEFAULT 'unpaid'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE private_training_bookings MODIFY payment_status ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid'");
    }
};
