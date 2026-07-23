<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->enum('gender', ['male', 'female'])->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};