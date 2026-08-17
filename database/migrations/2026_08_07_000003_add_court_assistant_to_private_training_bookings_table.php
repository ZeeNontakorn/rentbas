<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('private_training_bookings', function (Blueprint $table) {
            $table->boolean('assistant_requested')->default(false)->after('coach_id');
            $table->foreignId('court_assistant_id')
                ->nullable()
                ->after('assistant_requested')
                ->constrained('users')
                ->nullOnDelete();
            $table->index(
                ['court_assistant_id', 'date', 'status'],
                'private_training_assistant_schedule_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('private_training_bookings', function (Blueprint $table) {
            $table->dropIndex('private_training_assistant_schedule_index');
            $table->dropConstrainedForeignId('court_assistant_id');
            $table->dropColumn('assistant_requested');
        });
    }
};
