<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ปรับ courses ให้เหลือแค่ "ข้อมูลทั่วไปของคลาสเรียน" ตามฟอร์มใหม่ของ Gemini
     * ส่วนราคา/แพ็กเกจ ย้ายไปตาราง course_packages
     * ส่วนวันเรียน/เวลา ย้ายไปตาราง course_schedules
     * ส่วนกลุ่มเป้าหมาย ย้ายไปตาราง course_target_groups
     *
     * หมายเหตุ: ถ้ามีข้อมูลเดิมในตารางอยู่แล้ว ควรสำรอง/ย้ายข้อมูลก่อนรัน migration นี้
     * เพราะคอลัมน์ day_type, name_th, session_count, price, price_per_session,
     * validity_value, validity_unit, recommended_note จะถูกลบทิ้ง
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('course_name')->after('id')->nullable();
            $table->unsignedInteger('min_age')->default(0)->after('course_name');
            $table->unsignedInteger('max_age')->nullable()->after('min_age'); // NULL = ไม่มีขีดจำกัดบน
        });

        // คัดลอกข้อมูลเดิมมาไว้ในคอลัมน์ใหม่ก่อนลบคอลัมน์เก่า (กันข้อมูลหาย)
        if (Schema::hasColumn('courses', 'name')) {
            \DB::table('courses')->update([
                'course_name' => \DB::raw('COALESCE(name_th, name)'),
            ]);
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->string('course_name')->nullable(false)->change();

            // is_featured / is_active / sort_order ย้ายไปอยู่ระดับ "แพ็กเกจ" (course_packages)
            // แทน เพราะแต่ละคอร์สมีได้หลายแพ็กเกจ และแต่ละแพ็กเกจเปิด/ปิดหรือปักหมุดแยกกันได้
            foreach (['day_type', 'name', 'name_th', 'session_count', 'price',
                      'price_per_session', 'validity_value', 'validity_unit',
                      'recommended_note', 'is_featured', 'is_active', 'sort_order'] as $column) {
                if (Schema::hasColumn('courses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['course_name', 'min_age', 'max_age']);

            $table->string('day_type')->nullable();
            $table->string('name')->nullable();
            $table->string('name_th')->nullable();
            $table->unsignedInteger('session_count')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('price_per_session', 10, 2)->nullable();
            $table->unsignedInteger('validity_value')->nullable();
            $table->string('validity_unit')->nullable();
            $table->string('recommended_note')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
        });
    }
};
