<?php

namespace App\Http\Controllers;

use App\Models\Course;

class CourseController extends Controller
{
    /**
     * GET /courses/{course} -> route('courses.show')
     * หน้ารายละเอียดคอร์สสำหรับผู้ใช้ทั่วไป (ยังไม่มีระบบซื้อ/ชำระเงินในระบบ
     * ตอนนี้จึงให้ผู้ใช้ติดต่อแอดมินเพื่อสมัครเรียน — เมื่อทีมตกลง flow การชำระเงินแล้ว
     * ค่อยเปลี่ยนส่วน CTA ในหน้านี้เป็นขั้นตอนสมัคร/ชำระเงินจริง)
     */
    public function show(Course $course)
    {
        $course->load([
            'targetGroups',
            'schedules',
            'packages' => function ($query) {
                $query->with('courseType')->where('is_active', true)->orderByDesc('is_featured')->orderBy('sort_order');
            },
        ]);

        // ถ้าคอร์สนี้ไม่มีแพ็กเกจที่เปิดใช้งานอยู่เลย ถือว่ายังไม่พร้อมให้ดูหน้ารายละเอียด
        abort_if($course->packages->isEmpty(), 404);

        return view('show-course', compact('course'));
    }
}
