<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            // ประเภทแพ็กเกจ เช่น private (เทรนส่วนตัว) ตอนนี้มีแค่ private, เผื่อเพิ่มประเภทอื่นในอนาคต (เช่น group)
            $table->enum('type', ['private'])->default('private')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
