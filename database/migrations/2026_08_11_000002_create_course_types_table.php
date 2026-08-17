<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('course_types')->insert([
            ['name' => 'Kinder Class', 'slug' => 'kinder', 'is_active' => true, 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Special Class', 'slug' => 'special', 'is_active' => true, 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Standard Class', 'slug' => 'standard', 'is_active' => true, 'sort_order' => 30, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Keep this legacy column as a string during the transition. Existing code can
        // continue reading the slug while new code uses the course_type_id relation.
        Schema::table('course_packages', function (Blueprint $table) {
            $table->string('package_type')->default('standard')->change();
            $table->foreignId('course_type_id')
                ->nullable()
                ->after('package_type')
                ->constrained('course_types')
                ->restrictOnDelete();
        });

        DB::table('course_types')->pluck('id', 'slug')->each(function ($id, $slug) {
            DB::table('course_packages')
                ->where('package_type', $slug)
                ->update(['course_type_id' => $id]);
        });
    }

    public function down(): void
    {
        Schema::table('course_packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_type_id');
        });

        DB::table('course_packages')
            ->whereNotIn('package_type', ['kinder', 'special', 'standard'])
            ->update(['package_type' => 'standard']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE course_packages MODIFY package_type ENUM('kinder','special','standard') NOT NULL DEFAULT 'standard'");
        }

        Schema::dropIfExists('course_types');
    }
};
