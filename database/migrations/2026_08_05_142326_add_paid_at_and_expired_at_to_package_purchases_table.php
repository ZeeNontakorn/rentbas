<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_purchases', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('remaining_use');
            $table->timestamp('expired_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('package_purchases', function (Blueprint $table) {
            $table->dropColumn(['paid_at', 'expired_at']);
        });
    }
};
