<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            $table->enum('package_type', ['group', 'private'])->default('group');
            $table->unsignedInteger('total_sessions');
            $table->decimal('total_price', 10, 2);
            $table->decimal('price_per_session', 10, 2)->nullable(); // คำนวณฝั่ง server เสมอ

            $table->unsignedInteger('validity_value');
            $table->enum('validity_unit', ['days', 'hours'])->default('days');

            $table->string('recommendation_text')->nullable();

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_packages');
    }
};
