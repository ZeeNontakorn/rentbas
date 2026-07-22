<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\CourseSchedule;
use App\Models\CourtSection;
use App\Models\CourseCalendarOverride;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    private const COACHES = ['โค้ชต้น', 'โค้ชฟ้า', 'โค้ชบี'];
    private const SAMPLE_STUDENTS = ['น้องพีท', 'น้องข้าวหอม', 'น้องภูมิ', 'น้องมินท์', 'น้องไทเกอร์'];

    public function calendar()
    {
        $courtSections = CourtSection::with('court')->get()->map(fn (CourtSection $section) => [
            'id' => $section->id,
            'label' => $section->court->name.' — '.$section->name,
        ]);

        return view('admin.calendars.course-calendar', ['coaches' => self::COACHES, 'courtSections' => $courtSections]);
    }

    public function events(Request $request)
    {
        $data = $request->validate(['start' => ['required', 'date'], 'end' => ['required', 'date', 'after:start']]);
        $from = Carbon::parse($data['start'])->startOfDay(); $until = Carbon::parse($data['end'])->endOfDay();
        $overrides = CourseCalendarOverride::with('courtSection.court')->whereBetween('occurrence_date', [$from->toDateString(), $until->toDateString()])->get()->keyBy(fn ($override) => $override->course_schedule_id.'-'.$override->occurrence_date->toDateString());
        $courseEvents = CourseSchedule::with(['course', 'course.targetGroups', 'course.packages', 'courtSection.court'])->get()->flatMap(fn (CourseSchedule $schedule) => $this->courseOccurrences($schedule, $from, $until, $overrides));
        $personalEvents = CalendarEvent::with('courtSection.court')->get()->flatMap(fn (CalendarEvent $event) => $this->eventOccurrences($event, $from, $until));
        return response()->json($courseEvents->concat($personalEvents)->values());
    }

    public function store(Request $request) { return response()->json($this->toEvent(CalendarEvent::create($this->validated($request))), 201); }
    public function update(Request $request, CalendarEvent $calendarEvent) { $calendarEvent->update($this->validated($request)); return response()->json($this->toEvent($calendarEvent->fresh())); }
    public function destroy(CalendarEvent $calendarEvent) { $calendarEvent->delete(); return response()->noContent(); }

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
            'coach_name' => ['nullable', 'string', 'max:100'], 'package_type' => ['nullable', 'in:group,private'], 'court_section_id' => ['nullable', 'exists:court_sections,id'], 'student_names' => ['nullable', 'array'], 'student_names.*' => ['string', 'max:100'],
        ]);
    }

    private function courseOccurrences(CourseSchedule $schedule, Carbon $from, Carbon $until, $overrides): array
    {
        $coach = self::COACHES[($schedule->id - 1) % count(self::COACHES)]; $days = $schedule->day_type === 'weekday' ? [Carbon::MONDAY, Carbon::WEDNESDAY, Carbon::FRIDAY] : [Carbon::SATURDAY, Carbon::SUNDAY]; $result = [];
        for ($date = $from->copy(); $date->lte($until); $date->addDay()) {
            if (! in_array($date->dayOfWeek, $days, true)) continue;
            $override = $overrides->get($schedule->id.'-'.$date->toDateString());
            $start = $override?->starts_at ?? Carbon::parse($date->toDateString().' '.$schedule->start_time); $end = $override?->ends_at ?? Carbon::parse($date->toDateString().' '.$schedule->end_time); $color = $this->courseColor($schedule);
            $courtSection = $override?->courtSection ?? $schedule->courtSection;
            $packageType = $override?->package_type ?? $schedule->course->packages->first()?->package_type;
            $result[] = ['id' => 'course-'.$schedule->id.'-'.$date->toDateString(), 'title' => $override?->title_override ?: $schedule->course->course_name, 'start' => $start->toIso8601String(), 'end' => $end->toIso8601String(), 'backgroundColor' => $color, 'borderColor' => $color, 'editable' => false,
                'extendedProps' => ['kind' => 'course', 'coach' => $coach, 'capacity' => $schedule->spots_label, 'students' => array_slice(self::SAMPLE_STUDENTS, 0, min($schedule->capacity ?? 3, 5)), 'description' => $schedule->course->description, 'scheduleLabel' => $schedule->day_type_label,
                    'sourceScheduleId' => $schedule->id, 'occurrenceDate' => $date->toDateString(), 'packageTypeValue' => $packageType,
                    'packageType' => $packageType === 'private' ? 'Private Class (ส่วนตัว)' : ($packageType === 'group' ? 'Standard Group Class (กลุ่มเรียนรวม)' : 'ยังไม่กำหนดประเภทแพ็กเกจ'),
                    'courtSectionId' => $courtSection?->id, 'court' => $courtSection ? $courtSection->court->name.' — '.$courtSection->name : 'ยังไม่ระบุสนาม']];
        }
        return $result;
    }

    private function eventOccurrences(CalendarEvent $event, Carbon $from, Carbon $until): array
    {
        $result = []; $start = $event->starts_at->copy(); $end = $event->ends_at?->copy(); $last = $event->recurrence_until ? $event->recurrence_until->copy()->endOfDay() : $until;
        while ($start->lte($until) && $start->lte($last)) {
            if ($start->gte($from)) $result[] = $this->toEvent($event, $start, $end);
            if ($event->recurrence === 'none') break;
            $unit = $event->recurrence === 'daily' ? '1 day' : ($event->recurrence === 'weekly' ? '1 week' : '1 month'); $start->add($unit); if ($end) $end->add($unit);
        }
        return $result;
    }

    private function toEvent(CalendarEvent $event, ?Carbon $start = null, ?Carbon $end = null): array
    {
        return ['id' => 'event-'.$event->id, 'title' => $event->title, 'start' => ($start ?? $event->starts_at)->toIso8601String(), 'end' => ($end ?? $event->ends_at)?->toIso8601String(), 'allDay' => $event->all_day, 'backgroundColor' => $event->color, 'borderColor' => $event->color,
            'extendedProps' => ['kind' => 'personal', 'eventId' => $event->id, 'coach' => $event->coach_name, 'students' => $event->student_names ?? [], 'description' => $event->description, 'recurrence' => $event->recurrence, 'recurrenceUntil' => $event->recurrence_until?->toDateString(),
                'packageTypeValue' => $event->package_type,
                'packageType' => $event->package_type === 'private' ? 'Private Class (ส่วนตัว)' : ($event->package_type === 'group' ? 'Standard Group Class (กลุ่มเรียนรวม)' : 'ยังไม่ระบุประเภทแพ็กเกจ'),
                'courtSectionId' => $event->court_section_id,
                'court' => $event->courtSection ? $event->courtSection->court->name.' — '.$event->courtSection->name : 'ยังไม่ระบุสนาม']];
    }

    private function courseColor(CourseSchedule $schedule): string { return match ($schedule->course->targetGroups->first()?->target_group) { 'Junior' => '#7c3aed', 'Player' => '#ea580c', default => '#2563eb' }; }
}
