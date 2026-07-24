<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\CourseSchedule;
use App\Models\CourseTargetGroup;
use App\Models\Court;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ManageCourseController extends Controller
{
    /**
     * GET /admin/courses/create -> route('admin.courses.create')
     * เปิดหน้าเพิ่มคอร์ส
     */
    const MAX_PACKAGE_PRICE = 1000000;
    public function create()
    {
        return view('admin.courses.create', [
            'courts' => Court::with('sections')->orderBy('name')->get(),
            'existingSchedules' => collect(),
            'existingPackages' => collect(),
            'maxPackagePrice' => self::MAX_PACKAGE_PRICE,

        ]);
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
            ->orderByRaw("case when course_type = 'schedule' then 0 else 1 end")
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
            // หนึ่งคอร์สมีตัวเลือกแพ็กเกจได้หลายแบบ เช่น 1 / 4 / 8 ครั้ง
            foreach ($data['packages'] as $package) {
                CoursePackage::create(array_merge($package, ['course_id' => $course->id]));
            }

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

        return view('admin.courses.edit', [
            'course' => $course,
            'courts' => Court::with('sections')->orderBy('name')->get(),
            'existingSchedules' => $this->scheduleFormRows($course),
            'existingPackages' => $course->packages->values(),
            'maxPackagePrice' => self::MAX_PACKAGE_PRICE,

        ]);
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

            // สร้างแพ็กเกจใหม่จากรายการในฟอร์มทั้งหมด
            $course->packages()->delete();
            foreach ($data['packages'] as $package) {
                CoursePackage::create(array_merge($package, ['course_id' => $course->id]));
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
     * แปลง schedules ของคอร์สให้อยู่ในรูปแบบที่ฟอร์ม (JS) ใช้ประกอบแถวรอบเวลาเรียนได้ตรงๆ
     * ใช้ตอนเปิดหน้าแก้ไขคอร์สเท่านั้น
     */
    private function scheduleFormRows(Course $course)
    {
        return $course->schedules->map(fn ($schedule) => [
            'weekdays' => $schedule->weekdays ?: ['mon', 'wed', 'fri'],
            'start' => Carbon::parse($schedule->start_time)->format('H:i'),
            'end' => Carbon::parse($schedule->end_time)->format('H:i'),
            'limited' => $schedule->is_limited_spots,
            'capacity' => $schedule->capacity,
        ])->values();
    }

    /**
     * Validate + normalize ข้อมูลจากฟอร์ม แล้วแยกเป็น 3 กลุ่ม: course / target_groups / schedules / package
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'course_name' => ['required', 'string', 'max:255'],
            'min_age' => ['required', 'integer', 'min:0'],
            'max_age' => ['nullable', 'integer', 'min:0', 'gte:min_age'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:20480'], // สูงสุด 20MB

            'target_groups' => ['required', 'array', 'min:1'],
            'target_groups.*' => ['required', 'in:Rookie,Beginner,Junior,Player'],

            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.day_type' => ['nullable', 'in:weekday,weekend'],
            'schedules.*.weekdays' => ['required', 'array', 'min:1'],
            'schedules.*.weekdays.*' => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'schedules.*.court_section_id' => ['nullable', 'exists:court_sections,id'],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.is_limited_spots' => ['nullable', 'boolean'],
            // ถ้าติ๊ก "จำกัดจำนวน" (is_limited_spots = 1) ต้องกรอกจำนวนคนสูงสุดด้วยเสมอ
            'schedules.*.capacity' => ['nullable', 'required_if:schedules.*.is_limited_spots,1', 'integer', 'min:1'],

            'packages' => ['required', 'array', 'min:1'],
            'packages.*.package_type' => ['required', 'in:group,private'],
            'packages.*.total_sessions' => ['required', 'integer', 'min:1'],
            'packages.*.total_price' => ['required', 'numeric', 'min:0.01', 'max:'.self::MAX_PACKAGE_PRICE],
            'packages.*.validity_value' => ['required', 'integer', 'min:1'],
            'packages.*.validity_unit' => ['required', 'in:days,hours'],
            'packages.*.recommendation_text' => ['nullable', 'string', 'max:255'],
        ], [
            // --- ข้อมูลทั่วไป ---
            'course_name.required' => 'กรุณากรอกชื่อคลาสเรียน',
            'course_name.max' => 'ชื่อคลาสเรียนต้องไม่เกิน 255 ตัวอักษร',

            'min_age.required' => 'กรุณากรอกอายุขั้นต่ำ',
            'min_age.integer' => 'กรุณากรอกอายุขั้นต่ำเป็นตัวเลขเท่านั้น',
            'min_age.min' => 'กรุณากรอกอายุขั้นต่ำเป็นตัวเลขเท่านั้น',
            'max_age.integer' => 'กรุณากรอกอายุสูงสุดเป็นตัวเลขเท่านั้น',
            'max_age.min' => 'กรุณากรอกอายุสูงสุดเป็นตัวเลขเท่านั้น',
            'max_age.gte' => 'อายุสูงสุดต้องมากกว่าหรือเท่ากับอายุขั้นต่ำ',

            'target_groups.required' => 'กรุณาเลือกกลุ่มเป้าหมายอย่างน้อย 1 กลุ่ม',
            'target_groups.min' => 'กรุณาเลือกกลุ่มเป้าหมายอย่างน้อย 1 กลุ่ม',

            // --- รอบเวลาเรียน ---
            'schedules.*.weekdays.required' => 'กรุณาเลือกวันเรียนอย่างน้อย 1 วัน',
            'schedules.*.weekdays.min' => 'กรุณาเลือกวันเรียนอย่างน้อย 1 วัน',
            'schedules.*.start_time.required' => 'กรุณาเลือกเวลาเริ่ม',
            'schedules.*.end_time.required' => 'กรุณาเลือกเวลาสิ้นสุด',
            'schedules.*.end_time.after' => 'เวลาเริ่มต้องน้อยกว่าเวลาสิ้นสุด',
            'schedules.*.capacity.required_if' => 'กรุณากรอกจำนวนคนสูงสุด เมื่อเลือก "จำกัดจำนวน" สำหรับรอบเวลาเรียนนี้',
            'schedules.*.capacity.integer' => 'จำนวนคนสูงสุดต้องเป็นตัวเลข',
            'schedules.*.capacity.min' => 'จำนวนคนสูงสุดต้องมากกว่า 0',

            // --- แพ็กเกจ ---
            'packages.*.total_sessions.required' => 'กรุณากรอกจำนวนครั้ง',
            'packages.*.total_sessions.integer' => 'จำนวนครั้งต้องเป็นตัวเลข',
            'packages.*.total_sessions.min' => 'จำนวนครั้งต้องเป็นจำนวนเต็มบวก',

            'packages.*.total_price.required' => 'กรุณากรอกราคาแพ็กเกจ',
            'packages.*.total_price.numeric' => 'ราคาต้องเป็นตัวเลข',
            'packages.*.total_price.min' => 'ราคาต้องมากกว่า 0',
            'packages.*.total_price.max' => 'ราคาต้องไม่เกิน '.number_format(self::MAX_PACKAGE_PRICE).' บาท',

            'packages.*.validity_value.required' => 'กรุณากรอกอายุแพ็กเกจ',
            'packages.*.validity_value.integer' => 'อายุแพ็กเกจต้องเป็นตัวเลข',
            'packages.*.validity_value.min' => 'อายุแพ็กเกจต้องมากกว่าหรือเท่ากับ 0',
        ]);

        // วนลูปทุกข้อมูลใน Array แล้วแปลงข้อมูลใหม่กลับมาเป็น Array อีกชุดหนึ่ง
        $schedules = array_map(function ($schedule) {
            $isLimited = filter_var($schedule['is_limited_spots'] ?? false, FILTER_VALIDATE_BOOLEAN);

            return [
                'day_type' => $schedule['day_type'],
                'weekdays' => array_values($schedule['weekdays']),
                'court_section_id' => $schedule['court_section_id'] ?? null,
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
                'is_limited_spots' => $isLimited,
                // capacity มีความหมายเฉพาะตอน is_limited_spots = true เท่านั้น
                // ถ้าไม่จำกัด ให้บันทึกเป็น NULL เสมอ แม้ client จะส่งค่าอะไรมาก็ตาม
                'capacity' => $isLimited ? (int) $schedule['capacity'] : null,
            ];
        }, $validated['schedules'] ?? []);

        $packages = array_map(function ($package) {
            return [
                'package_type' => $package['package_type'],
                'total_sessions' => (int) $package['total_sessions'],
                'total_price' => $package['total_price'],
                'price_per_session' => round($package['total_price'] / $package['total_sessions'], 2),
                'validity_value' => (int) $package['validity_value'],
                'validity_unit' => $package['validity_unit'],
                'recommendation_text' => $package['recommendation_text'] ?? null,
            ];
        }, $validated['packages']);

        return [
            'course' => [
                'course_name' => $validated['course_name'],
                'course_type' => 'schedule',
                'min_age' => $validated['min_age'],
                'max_age' => $validated['max_age'] ?? null,
                'description' => $validated['description'] ?? null,
            ],
            'target_groups' => array_values(array_unique($validated['target_groups'] ?? [])),
            'schedules' => $schedules,
            'packages' => $packages,
        ];
    }
}