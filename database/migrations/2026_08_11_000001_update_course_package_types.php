<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Expand before converting existing rows so MySQL never truncates an enum value.
            DB::statement("ALTER TABLE course_packages MODIFY package_type ENUM('group','private','kinder','special','standard') NOT NULL DEFAULT 'group'");
            DB::table('course_packages')->where('package_type', 'group')->update(['package_type' => 'standard']);
            // Private packages have moved to their own flow. Keep legacy rows usable
            // as standard course packages instead of failing or truncating the migration.
            DB::table('course_packages')->where('package_type', 'private')->update(['package_type' => 'standard']);

            // Private courses now live in their own flow and are no longer valid course packages.
            DB::statement("ALTER TABLE course_packages MODIFY package_type ENUM('kinder','special','standard') NOT NULL DEFAULT 'standard'");

            return;
        }

        Schema::table('course_packages', function (Blueprint $table) {
            $table->string('package_type')->default('standard')->change();
        });

        DB::table('course_packages')->where('package_type', 'group')->update(['package_type' => 'standard']);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE course_packages MODIFY package_type ENUM('group','private','kinder','special','standard') NOT NULL DEFAULT 'standard'");
            DB::table('course_packages')->whereIn('package_type', ['kinder', 'special', 'standard'])->update(['package_type' => 'group']);
            DB::statement("ALTER TABLE course_packages MODIFY package_type ENUM('group','private') NOT NULL DEFAULT 'group'");

            return;
        }

        DB::table('course_packages')->whereIn('package_type', ['kinder', 'special', 'standard'])->update(['package_type' => 'group']);

        Schema::table('course_packages', function (Blueprint $table) {
            $table->string('package_type')->default('group')->change();
        });
    }
};
