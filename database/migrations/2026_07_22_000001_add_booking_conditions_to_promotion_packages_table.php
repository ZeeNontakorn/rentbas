<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_packages', function (Blueprint $table) {
            $table->string('court_type', 10)->nullable()->after('category');
            $table->json('available_days')->nullable()->after('court_type');
            $table->time('available_start_time')->nullable()->after('available_days');
            $table->time('available_end_time')->nullable()->after('available_start_time');
        });

        DB::table('promotion_packages')
            ->whereIn('category', ['personal', 'group'])
            ->update(['available_days' => json_encode(['weekday'])]);
    }

    public function down(): void
    {
        Schema::table('promotion_packages', function (Blueprint $table) {
            $table->dropColumn(['court_type', 'available_days', 'available_start_time', 'available_end_time']);
        });
    }
};
