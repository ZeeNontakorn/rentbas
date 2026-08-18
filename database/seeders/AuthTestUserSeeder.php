<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AuthTestUserSeeder extends Seeder
{
    /**
     * Accounts used for manual and Playwright authentication testing.
     *
     * @var array<int, array<string, string>>
     */
    private const USERS = [
        [
            'name' => 'Super Admin Test',
            'email' => '66160366@go.buu.ac.th',
            'phone' => '0800000366',
            'password' => '123456',
            'role' => 'superadmin',
            'membership_type' => 'admin',
        ],
        [
            'name' => 'Coach Snotnew Test',
            'email' => 'snotnew1234@gmail.com',
            'phone' => '0800001234',
            'password' => '121212',
            'role' => 'staff',
            'membership_type' => 'coach',
        ],
        [
            'name' => 'Coach Pachara Test',
            'email' => 'pachara000004@gmail.com',
            'phone' => '0800000004',
            'password' => '123456',
            'role' => 'staff',
            'membership_type' => 'coach',
        ],
        [
            'name' => 'Assistant Staff Test',
            'email' => 'oamnaka03@gmail.com',
            'phone' => '0800000003',
            'password' => '121212',
            'role' => 'staff',
            'membership_type' => 'court_assistant',
        ],
        [
            'name' => 'Customer Test',
            'email' => 'enfroman6666@gmail.com',
            'phone' => '0800066666',
            'password' => '123456',
            'role' => 'user',
            'membership_type' => 'customer',
        ],
        [
            'name' => 'Customer Two Test',
            'email' => 'bubbleteachakaimook1234@gmail.com',
            'phone' => '0800012345',
            'password' => '123456',
            'role' => 'user',
            'membership_type' => 'customer',
        ],
    ];

    public function run(): void
    {
        User::where('email', 'like', '%@auth-test.local')->delete();

        foreach (self::USERS as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'phone' => $account['phone'],
                    'password' => Hash::make($account['password']),
                    'role' => $account['role'],
                    'membership_type' => $account['membership_type'],
                    'is_verified' => true,
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
