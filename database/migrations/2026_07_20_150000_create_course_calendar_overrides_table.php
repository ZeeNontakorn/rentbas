<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_calendar_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_schedule_id')->constrained()->cascadeOnDelete();
            $table->date('occurrence_date');
            $table->string('title_override')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->enum('package_type', ['group', 'private'])->nullable();
            $table->foreignId('court_section_id')->nullable()->constrained('court_sections')->nullOnDelete();
            $table->timestamps();
            $table->unique(['course_schedule_id', 'occurrence_date'], 'course_occurrence_unique');
        });
    }

    public function down(): void { Schema::dropIfExists('course_calendar_overrides'); }
};
