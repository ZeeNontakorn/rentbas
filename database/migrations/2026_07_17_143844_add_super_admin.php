<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user','staff','admin', 'superadmin') NOT NULL DEFAULT 'user'");
    }

    public function down(): void
    {

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user','admin', 'superadmin') NOT NULL DEFAULT 'user'");
    }
};
