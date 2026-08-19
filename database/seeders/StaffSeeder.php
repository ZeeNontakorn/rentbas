<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\StaffProfile;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $coach = User::firstOrCreate(
            ['email' => 'somchai@test.com'],
            [
                'us_name' => 'โค้ชสมชาย สายเสมอ',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'membership_type' => 'coach',
            ]
        );

        $staff = User::firstOrCreate(
            ['email' => 'somsri@test.com'],
            [
                'us_name' => 'ผู้ช่วยสมศรี ขยันยิ่ง',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'membership_type' => 'coach',
            ]
        );

        StaffProfile::updateOrCreate(
            ['user_id' => $coach->id],
            [
                'specialty' => 'ผู้ฝึกสอนเทนนิสระดับ Pro (A-License)',
                'bio' => 'มีใจรักในการบริการ มีประสบการณ์แนะนำและดูแลลูกค้าผู้เข้าใช้บริการสนามมากกว่า 3 ปี',
                'experience_years' => 5
            ]
        );

        StaffProfile::updateOrCreate(
            ['user_id' => $staff->id],
            [
                'specialty' => 'ผู้ดูแลความเรียบร้อยและอุปกรณ์ประจำสนาม',
                'bio' => 'ขยันขันแข็ง ดูแลความเรียบร้อยของพื้นที่และคอยบริการลูกค้าอย่างยิ้มแย้ม',
                'experience_years' => 2
            ]
        );
    }
}
