<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Drop the unique index if it exists, then promote `key` to primary key
        $uniqueIndexes = DB::select("SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'key' AND NON_UNIQUE = 0");
        foreach ($uniqueIndexes as $idx) {
            $name = $idx->INDEX_NAME ?? $idx->index_name ?? null;
            if ($name) {
                DB::statement("ALTER TABLE `settings` DROP INDEX `" . str_replace('`', '', $name) . "`");
            }
        }

        $hasPrimary = (bool) count(DB::select("SELECT 1 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'key' AND CONSTRAINT_NAME = 'PRIMARY'"));
        if (!$hasPrimary) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('key')->primary()->change();
            });
        }
    }

    public function down(): void
    {
        $hasPrimary = (bool) count(DB::select("SELECT 1 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'key' AND CONSTRAINT_NAME = 'PRIMARY'"));
        if ($hasPrimary) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropPrimary(['key']);
            });
        }

        $hasUnique = (bool) count(DB::select("SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'key' AND NON_UNIQUE = 0"));
        if (!$hasUnique) {
            Schema::table('settings', function (Blueprint $table) {
                $table->unique('key');
            });
        }
    }
};
