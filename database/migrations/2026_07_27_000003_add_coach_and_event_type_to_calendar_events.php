<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->foreignId('coach_id')
                ->nullable()
                ->after('coach_name')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('event_type', 30)
                ->default('school_class')
                ->after('coach_id');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coach_id');
            $table->dropColumn('event_type');
        });
    }
};
