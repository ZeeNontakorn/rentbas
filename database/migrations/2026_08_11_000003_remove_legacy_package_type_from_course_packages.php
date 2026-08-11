<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $standardTypeId = DB::table('course_types')->where('slug', 'standard')->value('id');

        if (! $standardTypeId) {
            throw new RuntimeException('Standard course type is required before removing the legacy package_type column.');
        }

        DB::table('course_packages')
            ->whereNull('course_type_id')
            ->update(['course_type_id' => $standardTypeId]);

        Schema::table('course_packages', function (Blueprint $table) {
            $table->unsignedBigInteger('course_type_id')->nullable(false)->change();
            $table->dropColumn('package_type');
        });
    }

    public function down(): void
    {
        Schema::table('course_packages', function (Blueprint $table) {
            $table->string('package_type')->nullable()->after('course_type_id');
        });

        DB::table('course_types')->pluck('slug', 'id')->each(function ($slug, $id) {
            DB::table('course_packages')
                ->where('course_type_id', $id)
                ->update(['package_type' => $slug]);
        });

        Schema::table('course_packages', function (Blueprint $table) {
            $table->string('package_type')->nullable(false)->default('standard')->change();
            $table->unsignedBigInteger('course_type_id')->nullable()->change();
        });
    }
};
