<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\CourseSchedule;
use App\Models\CourseTargetGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ManageCourseController extends Controller
{

    /**
     * GET /admin/courses/create -> route('admin.courses.create')
     * เปิดหน้าเพิ่มคอร์ส
     */
    public function create()
    {
        return view('admin.courses.create');
    }


    /**
     * GET /admin/courses -> route('admin.courses')
     * ค้นหาจากชื่อคอร์ส และแสดงผลหน้าละ 10 รายการ
     */

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $courses = Course::query()
        // แสดงผลพร้อม targetGroups, schedules, packages เพื่อไม่ให้เกิด N+1 query
            ->with(['targetGroups', 'schedules', 'packages'])
            // ถ้ามี search ให้ค้นหาจาก course_name
            ->when($search !== '', function ($query) use ($search) {
                $query->where('course_name', 'like', "%{$search}%");
            })
            // เรียงตามชื่อคอร์ส แล้วแบ่งหน้า 10 รายการต่อหน้า
            ->orderBy('course_name')
            ->paginate(10)
            ->withQueryString();

        // ส่งตัวแปร $courses และ $search ไปที่ view เพื่อแสดงผล
        return view('admin.courses.index', compact('courses', 'search'));
    }


    /**
     * POST /admin/courses -> route('admin.courses.store')
     */
    public function store(Request $request)
    {
        $data = $this->validated($request); // Validate เพื่อตรวจสอบข้อมูลก่่อนบันทึก
        // ใช้ DB::transaction() เพื่อให้การบันทึกทั้งหมดเป็น atomic operation ถ้ามีข้อผิดพลาดใดๆ จะ rollback ทั้งหมด
        $course = DB::transaction(function () use ($request, $data) {
            $courseData = $data['course'];

            // ถ้ามีไฟล์รูปภาพที่อัปโหลดมา ให้บันทึก path ของไฟล์ลงใน database ด้วย
            if ($request->hasFile('image')) {
                $courseData['image_path'] = $request->file('image')->store('courses', 'public');
            }

            $course = Course::create($courseData);
            // สร้างกลุ่มเป้าหมาย
            foreach ($data['target_groups'] as $group) {
                CourseTargetGroup::create([
                    'course_id' => $course->id,
                    'target_group' => $group,
                ]);
            }
            // สร้างรอบเวลาเรียนใหม่ตามข้อมูลที่ส่งมา
            foreach ($data['schedules'] as $schedule) {
                CourseSchedule::create(array_merge($schedule, ['course_id' => $course->id]));
            }
            // สร้างแพ็กเกจแรกของคอร์ส
            CoursePackage::create(array_merge($data['package'], ['course_id' => $course->id]));
            // ส่งกลับ $course เพื่อใช้ใน redirect() ด้านล่าง
            return $course;
        });
        // ส่งกลับไปที่หน้า index พร้อม alert success
        return redirect()
            ->route('admin.courses')
            ->with('success', 'เพิ่มคอร์ส "'.$course->course_name.'" เรียบร้อยแล้ว');
    }

    /**
     * GET /admin/courses/{course}/edit -> route('admin.courses.edit')
     * แสดงข้อมูลคอร์สที่เลือกมาในฟอร์มแก้ไข
     * (รวม target_groups, schedules, packages ด้วย)
     */
    public function edit(Course $course)
    {
        $course->load(['targetGroups', 'schedules', 'packages']);

        return view('admin.courses.edit', compact('course'));
    }

    /**
     * PUT /admin/courses/{course} -> route('admin.courses.update')
     */
    public function update(Request $request, Course $course)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data, $course) {
            $courseData = $data['course'];

            // ถ้ามีไฟล์รูปภาพที่อัปโหลดมา ให้บันทึก path ของไฟล์ลงใน database ด้วย และลบไฟล์เก่าออกจาก storage
            if ($request->hasFile('image')) {
                if ($course->image_path) {
                    Storage::disk('public')->delete($course->image_path);
                }
                // บันทึก path ของไฟล์ใหม่ลงใน database
                $courseData['image_path'] = $request->file('image')->store('courses', 'public');
                
            // ถ้าเลือก "ลบรูปภาพ" ให้ลบไฟล์เก่าออกจาก storage และตั้งค่า image_path เป็น null
            } elseif ($request->boolean('remove_image')) {
                if ($course->image_path) {
                    Storage::disk('public')->delete($course->image_path);
                }
                $courseData['image_path'] = null;
            }
            // อัปเดตข้อมูลคอร์ส
            $course->update($courseData);

            // ลบกลุ่มเป้าหมาย/รอบเวลาเดิมทั้งหมด แล้วสร้างใหม่ตามที่ส่งมา 
            $course->targetGroups()->delete();
            foreach ($data['target_groups'] as $group) {
                CourseTargetGroup::create([
                    'course_id' => $course->id,
                    'target_group' => $group,
                ]);
            }
            // ลบรอบเวลาเรียนเดิมทั้งหมด แล้วสร้างใหม่ตามที่ส่งมา
            $course->schedules()->delete();
            foreach ($data['schedules'] as $schedule) {
                CourseSchedule::create(array_merge($schedule, ['course_id' => $course->id]));
            }

            // อัปเดตแพ็กเกจแรกของคอร์ส (ถ้ามี) หรือสร้างใหม่ถ้าไม่มี
            $package = $course->packages()->first();
            if ($package) {
                $package->update($data['package']);
            } else {
                CoursePackage::create(array_merge($data['package'], ['course_id' => $course->id]));
            }
        });

        return redirect()
            ->route('admin.courses')
            ->with('success', 'อัปเดตคอร์ส "'.$course->course_name.'" เรียบร้อยแล้ว');
    }

    /**
     * DELETE /admin/courses/{course} -> route('admin.courses.destroy')
     * target_groups / schedules / packages ถูกลบอัตโนมัติผ่าน cascadeOnDelete()
     */
    public function destroy(Course $course)
    {
        if ($course->image_path) {
            Storage::disk('public')->delete($course->image_path);
        }

        $course->delete();

        return redirect()
            ->route('admin.courses')
            ->with('success', 'ลบคอร์สเรียบร้อยแล้ว');
    }

    /**
     * PATCH /admin/courses/{course}/toggle-status -> route('admin.courses.toggleStatus')
     * เปิด/ปิดการใช้งานคอร์ส โดยสลับค่า is_active ของแพ็กเกจแรกของคอร์สนี้
     * (ฟอร์มนี้จัดการทีละ 1 แพ็กเกจต่อคอร์ส 
     */
    public function toggleStatus(Course $course)
    {
        $package = $course->packages()->first();

        if (! $package) {
            return redirect()
                ->route('admin.courses')
                ->with('error', 'คอร์ส "'.$course->course_name.'" ยังไม่มีแพ็กเกจ ไม่สามารถเปิด/ปิดใช้งานได้');
        }

        $package->update(['is_active' => ! $package->is_active]);

        $statusText = $package->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return redirect()
            ->route('admin.courses')
            ->with('success', 'เปลี่ยนสถานะคอร์ส "'.$course->course_name.'" เป็น '.$statusText.' แล้ว');
    }
    
    /**
     * Validate + normalize ข้อมูลจากฟอร์ม แล้วแยกเป็น 3 กลุ่ม: course / target_groups / schedules / package
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'course_name'    => ['required', 'string', 'max:255'],
            'min_age'        => ['required', 'integer', 'min:0'],
            'max_age'        => ['nullable', 'integer', 'gte:min_age'],
            'description'    => ['nullable', 'string'],
            'image'          => ['nullable', 'image', 'max:2048'], // สูงสุด 2MB

            'target_groups'             => ['required', 'array', 'min:1'],
            'target_groups.*'           => ['required', 'in:Rookie,Beginner,Junior,Player'],

            'schedules'                 => ['required', 'array', 'min:1'],
            'schedules.*.day_type'      => ['required', 'in:weekday,weekend'],
            'schedules.*.start_time'    => ['required', 'date_format:H:i'],
            'schedules.*.end_time'      => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.is_limited_spots' => ['nullable', 'boolean'],
            // ถ้าติ๊ก "จำกัดจำนวน" (is_limited_spots = 1) ต้องกรอกจำนวนคนสูงสุดด้วยเสมอ
            'schedules.*.capacity'      => ['nullable', 'required_if:schedules.*.is_limited_spots,1', 'integer', 'min:1'],

            'package_type'         => ['required', 'in:group,private'],
            'total_sessions'       => ['required', 'integer', 'min:1'],
            'total_price'          => ['required', 'numeric', 'min:0'],
            'validity_value'       => ['required', 'integer', 'min:1'],
            'validity_unit'        => ['required', 'in:days,hours'],
            'recommendation_text'  => ['nullable', 'string', 'max:255'],
        ], [
            'schedules.*.capacity.required_if' => 'กรุณากรอกจำนวนคนสูงสุด เมื่อเลือก "จำกัดจำนวน" สำหรับรอบเวลาเรียนนี้',
        ]);
        // วนลูปทุกข้อมูลใน Array แล้วแปลงข้อมูลใหม่กลับมาเป็น Array อีกชุดหนึ่ง
        $schedules = array_map(function ($schedule) {
            $isLimited = filter_var($schedule['is_limited_spots'] ?? false, FILTER_VALIDATE_BOOLEAN);

            return [
                'day_type' => $schedule['day_type'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
                'is_limited_spots' => $isLimited,
                // capacity มีความหมายเฉพาะตอน is_limited_spots = true เท่านั้น
                // ถ้าไม่จำกัด ให้บันทึกเป็น NULL เสมอ แม้ client จะส่งค่าอะไรมาก็ตาม
                'capacity' => $isLimited ? (int) $schedule['capacity'] : null,
            ];
        }, $validated['schedules']);

        return [
            'course' => [
                'course_name' => $validated['course_name'],
                'min_age' => $validated['min_age'],
                'max_age' => $validated['max_age'] ?? null,
                'description' => $validated['description'] ?? null,
            ],
            'target_groups' => array_values(array_unique($validated['target_groups'])),
            'schedules' => $schedules,
            'package' => [
                'package_type' => $validated['package_type'],
                'total_sessions' => $validated['total_sessions'],
                'total_price' => $validated['total_price'],
                // price_per_session คำนวณฝั่ง server เสมอ ไม่เชื่อค่าที่ client ส่งมา
                'price_per_session' => round($validated['total_price'] / $validated['total_sessions'], 2),
                'validity_value' => $validated['validity_value'],
                'validity_unit' => $validated['validity_unit'],
                'recommendation_text' => $validated['recommendation_text'] ?? null,
            ],
        ];
    }
}