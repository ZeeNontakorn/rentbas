<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('name_th')->nullable();
            $table->text('description')->nullable();

            $table->string('day_type')->nullable();
            $table->unsignedInteger('session_count')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('price_per_session', 10, 2)->nullable();
            $table->unsignedInteger('validity_value')->nullable();
            $table->string('validity_unit')->nullable();
            $table->string('recommended_note')->nullable();

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
