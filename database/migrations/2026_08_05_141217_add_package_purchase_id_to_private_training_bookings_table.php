<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('private_training_bookings', function (Blueprint $table) {
            $table->foreignId('package_purchase_id')->nullable()
                ->after('user_id')
                ->constrained('package_purchases')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('private_training_bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_purchase_id');
        });
    }
};
