<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(CourtSeeder::class);

        User::updateOrCreate(
            ['email' => 'admin@court.local'],
            [
                'us_name' => 'Admin',
                'phone' => '0800000000',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_verified' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@court.local'],
            [
                'us_name' => 'Test User',
                'phone' => '0811111111',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'is_verified' => true,
            ]
        );
    }
}
