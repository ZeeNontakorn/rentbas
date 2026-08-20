<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\CourseCalendarOverride;
use App\Models\CourseSchedule;
use App\Models\CourtSection;
use App\Models\User;
use App\Services\CalendarEventOccurrenceService;
use App\Services\StaffScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CalendarController extends Controller
{
    private const SAMPLE_STUDENTS = ['น้องพีท', 'น้องข้าวหอม', 'น้องภูมิ', 'น้องมินท์', 'น้องไทเกอร์'];

    public function __construct(
        private readonly CalendarEventOccurrenceService $occurrences,
        private readonly StaffScheduleService $staffSchedule,
    ) {}

    public function calendar()
    {
        $courtSections = CourtSection::with('court')->get()->map(fn (CourtSection $section) => [
            'id' => $section->id,
            'label' => $section->court->name.' — '.$section->name,
        ]);
        $coaches = User::query()
            ->where('role', 'staff')
            ->where('membership_type', 'coach')
            ->orderBy('us_name')
            ->get(['id', 'us_name']);

        return view('admin.calendars.course-calendar', compact('coaches', 'courtSections'));
    }

    public function events(Request $request)
    {
        $data = $request->validate(['start' => ['required', 'date'], 'end' => ['required', 'date', 'after:start']]);
        $from = Carbon::parse($data['start'])->startOfDay();
        $until = Carbon::parse($data['end'])->endOfDay();
        $overrides = CourseCalendarOverride::with('courtSection.court')->whereBetween('occurrence_date', [$from->toDateString(), $until->toDateString()])->get()->keyBy(fn ($override) => $override->course_schedule_id.'-'.$override->occurrence_date->toDateString());
        $courseEvents = CourseSchedule::with(['course', 'course.targetGroups', 'course.packages.courseType', 'courtSection.court'])->get()->flatMap(fn (CourseSchedule $schedule) => $this->courseOccurrences($schedule, $from, $until, $overrides));
        $personalEvents = CalendarEvent::with(['courtSection.court', 'coach'])
            ->where('event_type', 'school_class')
            ->get()
            ->flatMap(
                fn (CalendarEvent $event) => collect($this->occurrences->between($event, $from, $until))
                    ->map(fn (array $occurrence) => $this->toEvent($event, $occurrence['start'], $occurrence['end']))
            );

        return response()->json($courseEvents->concat($personalEvents)->values());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $event = isset($data['coach_id'])
            ? $this->staffSchedule->createEvent(User::findOrFail($data['coach_id']), $data)
            : CalendarEvent::create($data);

        return response()->json($this->toEvent($event), 201);
    }

    public function update(Request $request, CalendarEvent $calendarEvent)
    {
        $data = $this->validated($request);
        if (isset($data['coach_id'])) {
            $this->staffSchedule->updateEvent(User::findOrFail($data['coach_id']), $calendarEvent, $data);
        } else {
            $calendarEvent->update($data);
        }

        return response()->json($this->toEvent($calendarEvent->fresh()));
    }

    public function destroy(CalendarEvent $calendarEvent)
    {
        $calendarEvent->delete();

        return response()->noContent();
    }

    public function updateCourseEvent(Request $request, CourseSchedule $schedule, string $date)
    {
        $occurrenceDate = Carbon::parse($date)->toDateString();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at'],
            'package_type' => ['nullable', 'in:group,private'], 'court_section_id' => ['nullable', 'exists:court_sections,id'],
        ]);
        CourseCalendarOverride::updateOrCreate(['course_schedule_id' => $schedule->id, 'occurrence_date' => $occurrenceDate], [
            'title_override' => $data['title'], 'starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at'],
            'package_type' => $data['package_type'] ?? null, 'court_section_id' => $data['court_section_id'] ?? null,
        ]);

        return response()->noContent();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'starts_at' => ['required', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at'], 'all_day' => ['boolean'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'recurrence' => ['required', 'in:none,daily,weekly,monthly'], 'recurrence_until' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'recurrence_days' => ['nullable', 'array'], 'recurrence_days.*' => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'event_type' => ['required', 'in:school_class,general'],
            'coach_id' => [Rule::requiredIf($request->input('event_type') === 'school_class'), 'nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'staff')->where('membership_type', 'coach'))],
            'coach_name' => ['nullable', 'string', 'max:100'], 'package_type' => ['nullable', 'in:group,private'], 'court_section_id' => ['nullable', 'exists:court_sections,id'], 'student_names' => ['nullable', 'array'], 'student_names.*' => ['string', 'max:100'],
        ]);
    }

    private function courseOccurrences(CourseSchedule $schedule, Carbon $from, Carbon $until, $overrides): array
    {
        $coach = 'ยังไม่ระบุโค้ช';
        $days = $schedule->day_type === 'weekday' ? [Carbon::MONDAY, Carbon::WEDNESDAY, Carbon::FRIDAY] : [Carbon::SATURDAY, Carbon::SUNDAY];
        $result = [];
        for ($date = $from->copy(); $date->lte($until); $date->addDay()) {
            if (! in_array($date->dayOfWeek, $days, true)) {
                continue;
            }
            $override = $overrides->get($schedule->id.'-'.$date->toDateString());
            $start = $override?->starts_at ?? Carbon::parse($date->toDateString().' '.$schedule->start_time);
            $end = $override?->ends_at ?? Carbon::parse($date->toDateString().' '.$schedule->end_time);
            $color = $this->courseColor($schedule);
            $courtSection = $override?->courtSection ?? $schedule->courtSection;
            $package = $schedule->course->packages->first();
            $packageType = $override?->package_type ?? $package?->courseType?->slug;
            $packageTypeLabel = $override?->package_type
                ? $this->calendarPackageTypeLabel($override->package_type)
                : $package?->package_type_label;
            $result[] = ['id' => 'course-'.$schedule->id.'-'.$date->toDateString(), 'title' => $override?->title_override ?: $schedule->course->course_name, 'start' => $start->toIso8601String(), 'end' => $end->toIso8601String(), 'backgroundColor' => $color, 'borderColor' => $color, 'editable' => false,
                'extendedProps' => ['kind' => 'course', 'coach' => $coach, 'capacity' => $schedule->spots_label, 'students' => array_slice(self::SAMPLE_STUDENTS, 0, min($schedule->capacity ?? 3, 5)), 'description' => $schedule->course->description, 'scheduleLabel' => $schedule->day_type_label,
                    'sourceScheduleId' => $schedule->id, 'occurrenceDate' => $date->toDateString(), 'packageTypeValue' => $packageType,
                    'packageType' => $packageTypeLabel ?? 'ยังไม่กำหนดประเภทแพ็กเกจ',
                    'courtSectionId' => $courtSection?->id, 'court' => $courtSection ? $courtSection->court->name.' — '.$courtSection->name : 'ยังไม่ระบุสนาม']];
        }

        return $result;
    }

    private function toEvent(CalendarEvent $event, ?Carbon $start = null, ?Carbon $end = null): array
    {
        $eventStart = $start ?? $event->starts_at;

        return ['id' => 'event-'.$event->id.'-'.$eventStart->format('YmdHis'), 'title' => $event->title, 'start' => $eventStart->toIso8601String(), 'end' => ($end ?? $event->ends_at)?->toIso8601String(), 'allDay' => $event->all_day, 'backgroundColor' => $event->color, 'borderColor' => $event->color,
            'extendedProps' => ['kind' => 'personal', 'eventId' => $event->id, 'eventType' => $event->event_type, 'coachId' => $event->coach_id, 'coach' => $event->coach?->us_name ?? $event->coach_name, 'students' => $event->student_names ?? [], 'description' => $event->description, 'recurrence' => $event->recurrence, 'recurrenceUntil' => $event->recurrence_until?->toDateString(),
                'recurrenceDays' => $event->recurrence_days ?? [],
                'packageTypeValue' => $event->package_type,
                'packageType' => $event->package_type === 'private' ? 'Private Class (ส่วนตัว)' : ($event->package_type === 'group' ? 'Standard Group Class (กลุ่มเรียนรวม)' : 'ยังไม่ระบุประเภทแพ็กเกจ'),
                'courtSectionId' => $event->court_section_id,
                'court' => $event->courtSection ? $event->courtSection->court->name.' — '.$event->courtSection->name : 'ยังไม่ระบุสนาม']];
    }

    private function courseColor(CourseSchedule $schedule): string
    {
        return match ($schedule->course->targetGroups->first()?->target_group) {
            'Junior' => '#7c3aed', 'Player' => '#ea580c', default => '#2563eb'
        };
    }

    private function calendarPackageTypeLabel(?string $packageType): string
    {
        return match ($packageType) {
            'group' => 'Standard Group Class (กลุ่มเรียนรวม)',
            'private' => 'Private Class (ส่วนตัว)',
            default => 'ยังไม่กำหนดประเภทแพ็กเกจ',
        };
    }
}
