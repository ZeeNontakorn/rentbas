<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->enum('package_type', ['group', 'private'])->nullable()->after('coach_name');
            $table->foreignId('court_section_id')->nullable()->after('package_type')->constrained('court_sections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('court_section_id');
            $table->dropColumn('package_type');
        });
    }
};
