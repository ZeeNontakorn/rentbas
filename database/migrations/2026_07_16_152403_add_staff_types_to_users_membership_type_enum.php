<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
             $table->enum('membership_type', ['customer', 'sponsor', 'student'])
                ->default('customer')
                ->after('role');
        });

        DB::statement("ALTER TABLE users MODIFY membership_type ENUM('customer','sponsor','student','permanent','temporary','intern') DEFAULT 'customer'");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
             Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('membership_type');
        });
        });

        DB::statement("UPDATE users SET membership_type = 'customer' WHERE membership_type IN ('permanent','temporary','intern')");
        DB::statement("ALTER TABLE users MODIFY membership_type ENUM('customer','sponsor','student') DEFAULT 'customer'");
    }
};
