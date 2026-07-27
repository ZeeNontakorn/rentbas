<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('private_training_bookings')
            ->where('status', 'approved')
            ->update(['status' => 'awaiting_court']);
    }

    public function down(): void
    {
        DB::table('private_training_bookings')
            ->where('status', 'awaiting_court')
            ->whereNull('court_section_id')
            ->update(['status' => 'approved']);
    }
};
