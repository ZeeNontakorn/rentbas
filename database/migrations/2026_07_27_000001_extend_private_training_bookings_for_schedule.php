<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('private_training_bookings', function (Blueprint $table) {
            $table->string('status', 30)->default('pending')->change();
            $table->foreignId('court_id')->nullable()->after('coach_id')->constrained()->nullOnDelete();
            $table->foreignId('court_section_id')->nullable()->after('court_id')->constrained()->nullOnDelete();
            $table->foreignId('court_assigned_by')->nullable()->after('court_section_id')->constrained('users')->nullOnDelete();
            $table->timestamp('court_assigned_at')->nullable()->after('court_assigned_by');
            $table->index(['court_section_id', 'date', 'status'], 'private_training_court_schedule_index');
        });
    }

    public function down(): void
    {
        Schema::table('private_training_bookings', function (Blueprint $table) {
            $table->dropIndex('private_training_court_schedule_index');
            $table->dropConstrainedForeignId('court_assigned_by');
            $table->dropConstrainedForeignId('court_section_id');
            $table->dropConstrainedForeignId('court_id');
        });
    }
};
