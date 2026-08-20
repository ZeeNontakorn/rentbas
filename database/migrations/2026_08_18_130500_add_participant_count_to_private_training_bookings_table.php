<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('private_training_bookings', function (Blueprint $table) {
            $table->unsignedTinyInteger('participant_count')->default(1)->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('private_training_bookings', function (Blueprint $table) {
            $table->dropColumn('participant_count');
        });
    }
};
