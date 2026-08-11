<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\CourseSchedule;
use App\Models\CourseTargetGroup;
use App\Models\CourseType;
use App\Models\Court;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
            'courseTypes' => $this->activeCourseTypes(),

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
            ->with(['targetGroups', 'schedules', 'packages.courseType'])
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
        $this->normalizeLegacyCourseTypeInput($request);
        $data = $this->validated($request); // Validate เพื่อตรวจสอบข้อมูลก่่อนบันทึก
        $imagePath = $this->storeUploadedImage($request);

        // ใช้ DB::transaction() เพื่อให้การบันทึกทั้งหมดเป็น atomic operation ถ้ามีข้อผิดพลาดใดๆ จะ rollback ทั้งหมด
        try {
            $course = DB::transaction(function () use ($data, $imagePath) {
                $courseData = $data['course'];

                if ($imagePath) {
                    $courseData['image_path'] = $imagePath;
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
                    unset($schedule['id']);
                    CourseSchedule::create(array_merge($schedule, ['course_id' => $course->id]));
                }
                // หนึ่งคอร์สมีตัวเลือกแพ็กเกจได้หลายแบบ เช่น 1 / 4 / 8 ครั้ง
                foreach ($data['packages'] as $package) {
                    unset($package['id']);
                    CoursePackage::create(array_merge($package, ['course_id' => $course->id]));
                }

                // ส่งกลับ $course เพื่อใช้ใน redirect() ด้านล่าง
                return $course;
            });
        } catch (\Throwable $exception) {
            // Database rollback ไม่ครอบคลุม filesystem จึงต้องลบไฟล์ใหม่เองเมื่อบันทึกไม่สำเร็จ
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

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
        $course->load(['targetGroups', 'schedules', 'packages.courseType']);

        return view('admin.courses.edit', [
            'course' => $course,
            'courts' => Court::with('sections')->orderBy('name')->get(),
            'existingSchedules' => $this->scheduleFormRows($course),
            'existingPackages' => $course->packages->values(),
            'maxPackagePrice' => self::MAX_PACKAGE_PRICE,
            'courseTypes' => $this->activeCourseTypes($course),

        ]);
    }

    /**
     * PUT /admin/courses/{course} -> route('admin.courses.update')
     */
    public function update(Request $request, Course $course)
    {
        $this->normalizeLegacyCourseTypeInput($request, $course);
        $this->normalizeLegacyScheduleIds($request, $course);
        $data = $this->validated($request, $course);
        $oldImagePath = $course->image_path;
        $newImagePath = $this->storeUploadedImage($request);
        $removeImage = ! $newImagePath && $request->boolean('remove_image');

        try {
            DB::transaction(function () use ($data, $course, $newImagePath, $removeImage) {
                $courseData = $data['course'];

                if ($newImagePath) {
                    $courseData['image_path'] = $newImagePath;
                } elseif ($removeImage) {
                    $courseData['image_path'] = null;
                }

                // อัปเดตข้อมูลคอร์ส
                $course->update($courseData);

                $course->targetGroups()->delete();
                foreach ($data['target_groups'] as $group) {
                    CourseTargetGroup::create([
                        'course_id' => $course->id,
                        'target_group' => $group,
                    ]);
                }

                // รักษา ID ของรายการเดิมไว้ เพื่อไม่ให้ calendar overrides และสถานะแพ็กเกจสูญหาย
                $this->syncSchedules($course, $data['schedules']);
                $this->syncPackages($course, $data['packages']);
            });
        } catch (\Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $exception;
        }

        // ลบรูปเก่าหลัง transaction สำเร็จแล้วเท่านั้น
        if (($newImagePath || $removeImage) && $oldImagePath && $oldImagePath !== $newImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

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
        $imagePath = $course->image_path;

        DB::transaction(fn () => $course->delete());

        // เก็บไฟล์ไว้หากการลบในฐานข้อมูลล้มเหลว และลบเมื่อ commit สำเร็จแล้วเท่านั้น
        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        return redirect()
            ->route('admin.courses')
            ->with('success', 'ลบคอร์สเรียบร้อยแล้ว');
    }

    /**
     * PATCH /admin/courses/{course}/toggle-status -> route('admin.courses.toggleStatus')
     * เปิด/ปิดการใช้งานคอร์ส โดยตั้งสถานะแพ็กเกจทั้งหมดของคอร์สให้ตรงกัน
     */
    public function toggleStatus(Course $course)
    {
        if (! $course->packages()->exists()) {
            return redirect()
                ->route('admin.courses')
                ->with('error', 'คอร์ส "'.$course->course_name.'" ยังไม่มีแพ็กเกจ ไม่สามารถเปิด/ปิดใช้งานได้');
        }

        $activate = ! $course->packages()->where('is_active', true)->exists();
        $course->packages()->update(['is_active' => $activate]);

        $statusText = $activate ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

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
            'id' => $schedule->id,
            'day_type' => $schedule->day_type,
            'weekdays' => $schedule->weekdays ?: ['mon', 'wed', 'fri'],
            'court_section_id' => $schedule->court_section_id,
            'start' => Carbon::parse($schedule->start_time)->format('H:i'),
            'end' => Carbon::parse($schedule->end_time)->format('H:i'),
            'limited' => $schedule->is_limited_spots,
            'capacity' => $schedule->capacity,
        ])->values();
    }

    /**
     * Validate + normalize ข้อมูลจากฟอร์ม แล้วแยกเป็น 3 กลุ่ม: course / target_groups / schedules / package
     */
    private function validated(Request $request, ?Course $course = null): array
    {
        $scheduleIdRules = $course
            ? ['nullable', 'integer', 'distinct', Rule::exists('course_schedules', 'id')->where(fn ($query) => $query->where('course_id', $course->id))]
            : ['prohibited'];
        $packageIdRules = $course
            ? ['nullable', 'integer', 'distinct', Rule::exists('course_packages', 'id')->where(fn ($query) => $query->where('course_id', $course->id))]
            : ['prohibited'];
        $existingCourseTypeIds = $course
            ? $course->packages()->pluck('course_type_id')->map(fn ($id) => (int) $id)->all()
            : [];

        $validated = $request->validate([
            'course_name' => ['required', 'string', 'max:255'],
            'min_age' => ['required', 'integer', 'min:0'],
            'max_age' => ['nullable', 'integer', 'min:0', 'gte:min_age'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:20480'], // สูงสุด 20MB

            'target_groups' => ['required', 'array', 'min:1'],
            'target_groups.*' => ['required', 'in:Rookie,Beginner,Junior,Player'],

            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.id' => $scheduleIdRules,
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
            'packages.*.id' => $packageIdRules,
            'packages.*.course_type_id' => [
                'required',
                'integer',
                Rule::exists('course_types', 'id')->where(function ($query) use ($existingCourseTypeIds) {
                    $query->where(function ($query) use ($existingCourseTypeIds) {
                        $query->where('is_active', true);
                        if ($existingCourseTypeIds !== []) {
                            $query->orWhereIn('id', $existingCourseTypeIds);
                        }
                    });
                }),
            ],
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
            'packages.*.validity_value.min' => 'อายุแพ็กเกจต้องมากกว่า 0',
        ]);

        // วนลูปทุกข้อมูลใน Array แล้วแปลงข้อมูลใหม่กลับมาเป็น Array อีกชุดหนึ่ง
        $schedules = array_map(function ($schedule) {
            $isLimited = filter_var($schedule['is_limited_spots'] ?? false, FILTER_VALIDATE_BOOLEAN);

            return [
                'id' => ! empty($schedule['id']) ? (int) $schedule['id'] : null,
                'day_type' => $schedule['day_type'] ?? 'weekday',
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
                'id' => ! empty($package['id']) ? (int) $package['id'] : null,
                'course_type_id' => (int) $package['course_type_id'],
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

    private function activeCourseTypes(?Course $course = null)
    {
        $existingCourseTypeIds = $course?->packages()->pluck('course_type_id')->all() ?? [];

        return CourseType::query()
            ->where(function ($query) use ($existingCourseTypeIds) {
                $query->where('is_active', true);
                if ($existingCourseTypeIds !== []) {
                    $query->orWhereIn('id', $existingCourseTypeIds);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Forms opened before the course-types deployment still submit package_type.
     * Preserve the existing type by row during updates so those stale pages do not
     * fail after the legacy database column has been removed.
     */
    private function normalizeLegacyCourseTypeInput(Request $request, ?Course $course = null): void
    {
        if ($request->input('course_form_version') === '2') {
            return;
        }

        $packages = $request->input('packages');
        if (! is_array($packages)) {
            return;
        }

        $existingPackages = $course
            ? $course->packages()->orderBy('id')->get()
            : collect();
        $unusedExistingPackages = $existingPackages->keyBy('id');
        $standardTypeId = CourseType::query()->where('slug', 'standard')->value('id');

        foreach ($packages as $index => &$package) {
            $existingPackage = null;
            if (! empty($package['id'])) {
                $existingPackage = $unusedExistingPackages->pull((int) $package['id']);
            } elseif ($course) {
                $existingPackage = $unusedExistingPackages->first(function (CoursePackage $existing) use ($package) {
                    return (int) $existing->total_sessions === (int) ($package['total_sessions'] ?? 0)
                        && (float) $existing->total_price === (float) ($package['total_price'] ?? -1)
                        && (int) $existing->validity_value === (int) ($package['validity_value'] ?? 0)
                        && $existing->validity_unit === ($package['validity_unit'] ?? null);
                });

                if ($existingPackage) {
                    $unusedExistingPackages->forget($existingPackage->id);
                } elseif (count($packages) === $existingPackages->count()) {
                    $candidate = $existingPackages->get($index);
                    if ($candidate && $unusedExistingPackages->has($candidate->id)) {
                        $existingPackage = $unusedExistingPackages->pull($candidate->id);
                    }
                }
            }

            if ($existingPackage && empty($package['id'])) {
                $package['id'] = $existingPackage->id;
            }

            if (! empty($package['course_type_id'])) {
                continue;
            }

            $courseTypeId = $existingPackage?->course_type_id;
            if (! $courseTypeId && ($package['package_type'] ?? null) === 'group') {
                $courseTypeId = $standardTypeId;
            }

            if ($courseTypeId) {
                $package['course_type_id'] = $courseTypeId;
            }
        }
        unset($package);

        $request->merge(['packages' => $packages]);
    }

    /**
     * Forms opened before schedule IDs were added do not submit an ID. Match rows
     * back to their records when possible so an old browser tab does not remove
     * calendar overrides merely because the course was edited.
     */
    private function normalizeLegacyScheduleIds(Request $request, Course $course): void
    {
        if ($request->input('course_form_version') === '2') {
            return;
        }

        $schedules = $request->input('schedules');
        if (! is_array($schedules)) {
            return;
        }

        $existingSchedules = $course->schedules()->orderBy('id')->get();
        $unusedExistingSchedules = $existingSchedules->keyBy('id');

        foreach ($schedules as $index => &$schedule) {
            if (! empty($schedule['id'])) {
                $unusedExistingSchedules->forget((int) $schedule['id']);

                continue;
            }

            $existingSchedule = $unusedExistingSchedules->first(function (CourseSchedule $existing) use ($schedule) {
                $existingDays = collect($existing->weekdays ?? [])->sort()->values()->all();
                $submittedDays = collect($schedule['weekdays'] ?? [])->sort()->values()->all();

                return $existingDays === $submittedDays
                    && substr((string) $existing->start_time, 0, 5) === ($schedule['start_time'] ?? null)
                    && substr((string) $existing->end_time, 0, 5) === ($schedule['end_time'] ?? null)
                    && (int) ($existing->court_section_id ?? 0) === (int) ($schedule['court_section_id'] ?? 0);
            });

            if ($existingSchedule) {
                $unusedExistingSchedules->forget($existingSchedule->id);
            } elseif (count($schedules) === $existingSchedules->count()) {
                $candidate = $existingSchedules->get($index);
                if ($candidate && $unusedExistingSchedules->has($candidate->id)) {
                    $existingSchedule = $unusedExistingSchedules->pull($candidate->id);
                }
            }

            if ($existingSchedule) {
                $schedule['id'] = $existingSchedule->id;
            }
        }
        unset($schedule);

        $request->merge(['schedules' => $schedules]);
    }

    private function syncSchedules(Course $course, array $schedules): void
    {
        $existingSchedules = $course->schedules()->lockForUpdate()->get()->keyBy('id');

        foreach ($schedules as $scheduleData) {
            $scheduleId = $scheduleData['id'] ?? null;
            unset($scheduleData['id']);

            if ($scheduleId) {
                $schedule = $existingSchedules->pull($scheduleId);
                $schedule->update($scheduleData);
            } else {
                $course->schedules()->create($scheduleData);
            }
        }

        if ($existingSchedules->isNotEmpty()) {
            $course->schedules()->whereKey($existingSchedules->keys())->delete();
        }
    }

    private function syncPackages(Course $course, array $packages): void
    {
        $existingPackages = $course->packages()->lockForUpdate()->get()->keyBy('id');
        $newPackageIsActive = $existingPackages->isEmpty()
            || $existingPackages->contains(fn (CoursePackage $package) => $package->is_active);

        foreach ($packages as $packageData) {
            $packageId = $packageData['id'] ?? null;
            unset($packageData['id']);

            if ($packageId) {
                $package = $existingPackages->pull($packageId);
                $package->update($packageData);
            } else {
                $course->packages()->create(array_merge($packageData, [
                    'is_active' => $newPackageIsActive,
                ]));
            }
        }

        if ($existingPackages->isNotEmpty()) {
            $course->packages()->whereKey($existingPackages->keys())->delete();
        }
    }

    private function storeUploadedImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $path = $request->file('image')->store('courses', 'public');
        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('ไม่สามารถบันทึกรูปคอร์สได้');
        }

        return $path;
    }
}
