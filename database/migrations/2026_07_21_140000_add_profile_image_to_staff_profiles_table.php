<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('staff_profiles', 'profile_image')) {
            Schema::table('staff_profiles', function (Blueprint $table) {
                $table->string('profile_image')->nullable()->after('experience_years');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('staff_profiles', 'profile_image')) {
            Schema::table('staff_profiles', function (Blueprint $table) {
                $table->dropColumn('profile_image');
            });
        }
    }
};
