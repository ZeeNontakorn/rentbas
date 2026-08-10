<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Court;
use App\Models\PrivateTrainingBooking;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{
    private const MEMBERSHIP_TYPE_RULE = 'required|in:coach,court_assistant';

    public function index(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');

        $staffs = User::where('role', 'staff')
            ->whereIn('membership_type', ['coach', 'court_assistant'])
            ->when($type && in_array($type, ['coach', 'court_assistant']), fn($query) => $query->where('membership_type', $type))
            ->when($search, fn($query) => $query->where(
                fn($q) =>
                $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
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
            ->where(fn($query) => $query->where('date', '>', $today)->orWhere(fn($q) => $q->where('date', $today)->where('end_time', '>', $nowTime)))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $pastServices = $staff->availabilities()
            ->where(fn($query) => $query->where('status', 'booked')->orWhere('date', '<', $today)->orWhere(fn($q) => $q->where('date', $today)->where('end_time', '<=', $nowTime)))
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->get();

        return view('admin.staffs.show', [
            'staff' => $staff,
            'staffProfile' => $staff->staffProfile,
            'upcomingAvailabilities' => $upcomingAvailabilities,
            'pastServices' => $pastServices,
            'courts' => Court::orderBy('name', 'asc')->get()
        ]);
    }

    public function scheduleEvents(Request $request, User $staff)
    {
        $this->assertIsStaff($staff);
        $range = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);
        $from = Carbon::parse($range['start'])->toDateString();
        $until = Carbon::parse($range['end'])->toDateString();

        $availabilityEvents = $staff->availabilities()
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<', $until)
            ->get()
            ->map(fn ($slot) => [
                'id' => 'availability-'.$slot->id,
                'title' => $slot->detail ?: ($slot->status === 'available' ? 'ว่าง' : 'ไม่ว่าง'),
                'start' => $slot->date.'T'.substr($slot->start_time, 0, 8),
                'end' => $slot->date.'T'.substr($slot->end_time, 0, 8),
                'backgroundColor' => $slot->status === 'available' ? '#10b981' : '#64748b',
                'borderColor' => $slot->status === 'available' ? '#059669' : '#475569',
                'extendedProps' => [
                    'kind' => 'availability',
                    'statusLabel' => $slot->status === 'available' ? 'ว่าง' : 'ไม่ว่าง',
                    'detail' => $slot->detail,
                ],
            ]);

        if ($staff->membership_type !== 'coach') {
            return response()->json($availabilityEvents->values());
        }

        $bookingEvents = PrivateTrainingBooking::with('user')
            ->where('coach_id', $staff->id)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<', $until)
            ->whereIn('status', ['pending', 'awaiting_court', 'confirmed'])
            ->get()
            ->map(fn (PrivateTrainingBooking $booking) => [
                'id' => 'private-'.$booking->id,
                'title' => 'Private: '.$booking->user->name,
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

        return response()->json($availabilityEvents->concat($bookingEvents)->values());
    }

    public function storeAvailability(Request $request, User $staff)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => 'required|in:available,booked',
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
                'status' => $validated['status'],
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
            'email' => 'required|string|email|max:255|unique:users,email,' . $staff->id,
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
