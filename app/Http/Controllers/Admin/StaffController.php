<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Court;
use App\Models\PrivateTrainingBooking;
use App\Models\User;
use App\Services\CalendarEventOccurrenceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{
    private const MEMBERSHIP_TYPE_RULE = 'required|in:coach,court_assistant';

    public function __construct(
        private readonly CalendarEventOccurrenceService $occurrences,
    ) {}

    public function index(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');

        $staffs = User::where('role', 'staff')
            ->whereIn('membership_type', ['coach', 'court_assistant'])
            ->when($type && in_array($type, ['coach', 'court_assistant']), fn ($query) => $query->where('membership_type', $type))
            ->when($search, fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->paginate(10)
            ->withQueryString();

        return view('admin.staffs.index', compact('staffs', 'search'));
    }

    public function show(User $staff)
    {
        $this->assertIsStaff($staff);

        $today = now()->toDateString();
        $nowTime = now()->toTimeString();

        $upcomingAvailabilities = $staff->availabilities()
            ->whereIn('status', ['available', 'booked'])
            ->where(fn ($query) => $query->where('date', '>', $today)->orWhere(fn ($q) => $q->where('date', $today)->where('end_time', '>', $nowTime)))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $pastServices = $staff->availabilities()
            ->where(fn ($query) => $query->where('status', 'booked')->orWhere('date', '<', $today)->orWhere(fn ($q) => $q->where('date', $today)->where('end_time', '<=', $nowTime)))
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->get();

        return view('admin.staffs.show', [
            'staff' => $staff,
            'staffProfile' => $staff->staffProfile,
            'upcomingAvailabilities' => $upcomingAvailabilities,
            'pastServices' => $pastServices,
            'courts' => Court::orderBy('name', 'asc')->get(),
        ]);
    }

    public function scheduleEvents(Request $request, User $staff)
    {
        $this->assertIsStaff($staff);
        $range = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);
        $fromDate = Carbon::parse($range['start']);
        $untilDate = Carbon::parse($range['end']);
        $from = $fromDate->toDateString();
        $until = $untilDate->toDateString();

        $availabilityEvents = $staff->availabilities()
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<', $until)
            ->get()
            ->map(fn ($slot) => [
                'id' => 'availability-'.$slot->id,
                'title' => $slot->detail ?: 'ไม่ว่าง',
                'start' => $slot->date.'T'.substr($slot->start_time, 0, 8),
                'end' => $slot->date.'T'.substr($slot->end_time, 0, 8),
                'backgroundColor' => '#64748b',
                'borderColor' => '#475569',
                'extendedProps' => [
                    'kind' => 'availability',
                    'statusLabel' => 'ไม่ว่าง',
                    'detail' => $slot->detail,
                ],
            ]);

        $calendarEvents = $this->occurrences
            ->forCoachesBetween([$staff->id], $fromDate, $untilDate)
            ->map(function (array $occurrence) {
                /** @var CalendarEvent $event */
                $event = $occurrence['event'];

                return [
                    'id' => 'calendar-'.$event->id.'-'.$occurrence['occurrenceKey'],
                    'title' => $event->title,
                    'start' => $occurrence['start']->toIso8601String(),
                    'end' => $occurrence['end']->toIso8601String(),
                    'backgroundColor' => $event->color,
                    'borderColor' => $event->color,
                    'editable' => false,
                    'extendedProps' => [
                        'kind' => 'calendar_event',
                        'statusLabel' => match ($event->event_type) {
                            'school_class' => 'คลาสโรงเรียนบาส',
                            'private_training_manual' => 'Private Training (กำหนดเอง)',
                            'work' => 'งาน',
                            'leave' => 'ลางาน',
                            default => 'กิจกรรมส่วนตัว',
                        },
                        'detail' => $event->description,
                    ],
                ];
            });

        $bookingEvents = PrivateTrainingBooking::with('user')
            ->where($staff->membership_type === 'coach' ? 'coach_id' : 'court_assistant_id', $staff->id)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<', $until)
            ->whereIn('status', ['pending', 'awaiting_court', 'confirmed'])
            ->get()
            ->map(fn (PrivateTrainingBooking $booking) => [
                'id' => 'private-'.$booking->id,
                'title' => ($staff->membership_type === 'coach' ? 'Private: ' : 'ผู้ช่วยสนาม: ').$booking->user->name,
                'start' => $booking->date->toDateString().'T'.substr($booking->start_time, 0, 8),
                'end' => $booking->date->toDateString().'T'.substr($booking->end_time, 0, 8),
                'backgroundColor' => match ($booking->status) {
                    'confirmed' => '#7c3aed',
                    'awaiting_court' => '#2563eb',
                    default => '#f97316',
                },
                'borderColor' => match ($booking->status) {
                    'confirmed' => '#6d28d9',
                    'awaiting_court' => '#1d4ed8',
                    default => '#ea580c',
                },
                'extendedProps' => [
                    'kind' => 'private_training',
                    'statusLabel' => match ($booking->status) {
                        'confirmed' => 'ยืนยันแล้ว',
                        'awaiting_court' => 'รอจัดสนาม',
                        default => 'รออนุมัติ',
                    },
                ],
            ]);

        return response()->json($availabilityEvents->concat($calendarEvents)->concat($bookingEvents)->values());
    }

    public function storeAvailability(Request $request, User $staff)
    {
        $this->assertIsStaff($staff);
        abort_if($staff->membership_type === 'coach', 403, 'Schedule ของโค้ชต้องบันทึกโดยบัญชีโค้ชเจ้าของตารางเท่านั้น');

        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => 'required|in:booked',
            'court_id' => 'nullable|integer|exists:courts,id',
            'detail' => 'nullable|string',
        ]);

        $staff->availabilities()->updateOrCreate(
            [
                'date' => $validated['date'],
                'start_time' => $validated['start_time'].':00',
                'end_time' => $validated['end_time'].':00',
                'court_id' => $validated['court_id'] ?? null,
            ],
            [
                'status' => 'booked',
                'detail' => $validated['detail'] ?? null,
            ]
        );

        return redirect()->back()->with('success', 'บันทึกสถานะช่วงเวลาสำเร็จ!');
    }

    public function updateProfile(Request $request, User $staff)
    {
        $this->assertIsStaff($staff);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$staff->id,
            'membership_type' => self::MEMBERSHIP_TYPE_RULE,
            'phone' => 'nullable|string|max:20',
            'specialty' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'gender' => 'nullable|in:male,female',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:20480',
            'remove_profile_image' => 'nullable|boolean',
        ], [
            'profile_image.image' => 'รูปโปรไฟล์ต้องเป็นไฟล์รูปภาพเท่านั้น',
            'profile_image.mimes' => 'รูปโปรไฟล์รองรับเฉพาะไฟล์ JPG, PNG และ WEBP',
            'profile_image.max' => 'รูปโปรไฟล์ต้องมีขนาดไม่เกิน 20MB',
        ]);

        $staff->update($request->only(['name', 'email', 'membership_type', 'phone']));

        $staffProfile = $staff->staffProfile()->firstOrNew(
            ['user_id' => $staff->id]
        );
        $staffProfile->fill($request->only(['specialty', 'bio', 'gender']));

        if ($request->hasFile('profile_image')) {
            if ($staffProfile->profile_image) {
                Storage::disk('public')->delete($staffProfile->profile_image);
            }

            $staffProfile->profile_image = $request->file('profile_image')
                ->store('coach-profiles', 'public');
        } elseif ($request->boolean('remove_profile_image')) {
            if ($staffProfile->profile_image) {
                Storage::disk('public')->delete($staffProfile->profile_image);
            }

            $staffProfile->profile_image = null;
        }

        $staffProfile->save();

        return redirect()->back()->with('success', 'แก้ไขข้อมูลโปรไฟล์สำเร็จเรียบร้อย!');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'membership_type' => self::MEMBERSHIP_TYPE_RULE,
        ]);

        $validated['role'] = 'staff';
        $validated['password'] = bcrypt($validated['password']);
        $validated['email_verified_at'] = now();

        User::create($validated);

        return redirect()->back()->with('success', 'เพิ่มบุคลากรใหม่เรียบร้อยแล้ว');
    }

    public function removeRole(User $staff)
    {
        $this->assertIsStaff($staff);

        $staff->update([
            'role' => 'user',
            'membership_type' => 'customer',
        ]);

        return redirect()->back()->with('success', 'ถอดบทบาทบุคลากรเรียบร้อยแล้ว บัญชีผู้ใช้และข้อมูลเดิมยังคงอยู่');
    }

    private function assertIsStaff(User $staff): void
    {
        abort_unless($staff->role === 'staff', 404, 'ไม่พบข้อมูลบุคลากร');
    }
}
