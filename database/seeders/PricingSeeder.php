<?php

namespace Database\Seeders;

use App\Models\PricingRule;
use App\Models\PromotionPackage;
use Illuminate\Database\Seeder;

class PricingSeeder extends Seeder
{
    /**
     * ราคาเริ่มต้นตามป้ายราคาในภาพ (image.png) ใช้ 1 บาท = 100 สตางค์
     * แอดมินแก้ไขได้ทีหลังผ่านหน้า admin.pricing โดยไม่ต้องรันโค้ดนี้ซ้ำ
     */
    public function run(): void
    {
        $rules = [
            // Sunset Time: ทุกวัน 16.00-21.00
            ['code' => 'sunset_full', 'label' => 'Sunset Time - Full Court', 'day_type' => 'everyday', 'start_time' => '16:00', 'end_time' => '21:00', 'court_type' => 'full', 'price_per_hour' => 140000, 'priority' => 10],
            ['code' => 'sunset_half', 'label' => 'Sunset Time - Half Court', 'day_type' => 'everyday', 'start_time' => '16:00', 'end_time' => '21:00', 'court_type' => 'half', 'price_per_hour' => 80000, 'priority' => 10],

            // Weekday Time: จันทร์-ศุกร์ 09.00-16.00
            ['code' => 'weekday_full', 'label' => 'Weekday Time - Full Court', 'day_type' => 'weekday', 'start_time' => '09:00', 'end_time' => '16:00', 'court_type' => 'full', 'price_per_hour' => 80000, 'priority' => 5],
            ['code' => 'weekday_half', 'label' => 'Weekday Time - Half Court', 'day_type' => 'weekday', 'start_time' => '09:00', 'end_time' => '16:00', 'court_type' => 'half', 'price_per_hour' => 50000, 'priority' => 5],

            // Holiday Time: วันหยุดนักขัตฤกษ์ (และเสาร์-อาทิตย์ 07.00-11.00 ตามป้าย) 09.00-16.00
            ['code' => 'holiday_full', 'label' => 'Holiday Time - Full Court', 'day_type' => 'holiday', 'start_time' => '09:00', 'end_time' => '16:00', 'court_type' => 'full', 'price_per_hour' => 110000, 'priority' => 8],
            ['code' => 'holiday_half', 'label' => 'Holiday Time - Half Court', 'day_type' => 'holiday', 'start_time' => '09:00', 'end_time' => '16:00', 'court_type' => 'half', 'price_per_hour' => 70000, 'priority' => 8],
            // เสาร์-อาทิตย์ ช่วง 07.00-11.00 ใช้เรทเดียวกับ Holiday ตามป้ายราคา
            ['code' => 'weekend_morning_full', 'label' => 'Weekend Morning (07-11) - Full Court', 'day_type' => 'weekend', 'start_time' => '07:00', 'end_time' => '11:00', 'court_type' => 'full', 'price_per_hour' => 110000, 'priority' => 8],
            ['code' => 'weekend_morning_half', 'label' => 'Weekend Morning (07-11) - Half Court', 'day_type' => 'weekend', 'start_time' => '07:00', 'end_time' => '11:00', 'court_type' => 'half', 'price_per_hour' => 70000, 'priority' => 8],
            // ช่วงเสาร์-อาทิตย์นอกเวลาข้างต้น (11.00-16.00) ยังไม่ระบุในป้าย — ใช้เรท weekday ชั่วคราว
            // ปรับได้ที่หน้า admin.pricing เมื่อมีเรทที่แน่ชัด
            ['code' => 'weekend_day_full', 'label' => 'Weekend Daytime (11-16) - Full Court', 'day_type' => 'weekend', 'start_time' => '11:00', 'end_time' => '16:00', 'court_type' => 'full', 'price_per_hour' => 80000, 'priority' => 4],
            ['code' => 'weekend_day_half', 'label' => 'Weekend Daytime (11-16) - Half Court', 'day_type' => 'weekend', 'start_time' => '11:00', 'end_time' => '16:00', 'court_type' => 'half', 'price_per_hour' => 50000, 'priority' => 4],
        ];

        foreach ($rules as $rule) {
            PricingRule::updateOrCreate(['code' => $rule['code']], $rule);
        }

        $packages = [
            [
                'code' => 'personal_general', 'label' => 'Personal Shooting 2 Hours (ประชาชนทั่วไป)',
                'category' => 'personal', 'duration_hours' => 2, 'max_people' => 1,
                'base_price' => 15000, 'holiday_price' => 20000,
                'weekend_special_price' => 20000, 'weekend_special_start' => '07:00', 'weekend_special_end' => '11:00',
                'requires_verification' => false,
            ],
            [
                'code' => 'personal_student', 'label' => 'Personal Shooting 2 Hours (นักเรียน/นักศึกษา)',
                'category' => 'personal', 'duration_hours' => 2, 'max_people' => 1,
                'base_price' => 10000, 'holiday_price' => 15000,
                'weekend_special_price' => 15000, 'weekend_special_start' => '07:00', 'weekend_special_end' => '11:00',
                'requires_verification' => true,
            ],
            [
                'code' => 'group_full', 'label' => 'Group Court 3 Hours (Full Court)',
                'category' => 'group', 'duration_hours' => 3, 'max_people' => 20,
                'base_price' => 190000, 'holiday_price' => 290000,
                'weekend_special_price' => 290000, 'weekend_special_start' => '07:00', 'weekend_special_end' => '11:00',
                'requires_verification' => false,
            ],
            [
                'code' => 'group_half', 'label' => 'Group Court 3 Hours (Half Court)',
                'category' => 'group', 'duration_hours' => 3, 'max_people' => 12,
                'base_price' => 110000, 'holiday_price' => 160000,
                'weekend_special_price' => 160000, 'weekend_special_start' => '07:00', 'weekend_special_end' => '11:00',
                'requires_verification' => false,
            ],
            [
                'code' => 'private_group', 'label' => 'Private Group 4-Session Course',
                'category' => 'private', 'duration_hours' => 2, 'max_people' => 6, // 1.30 ชม./ครั้ง ปัดเป็น slot 2 ชม.
                'base_price' => 690000, 'holiday_price' => null,
                'weekend_special_price' => null, 'weekend_special_start' => null, 'weekend_special_end' => null,
                'requires_verification' => false, 'session_count' => 4, 'validity_days' => 60,
            ],
        ];

        foreach ($packages as $package) {
            PromotionPackage::updateOrCreate(['code' => $package['code']], $package);
        }
    }
}
