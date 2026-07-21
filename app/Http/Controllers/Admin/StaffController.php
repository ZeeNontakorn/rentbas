<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Court;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StaffController extends Controller
{
    private const MEMBERSHIP_TYPE_RULE = 'required|in:coach,court_assistant';

    public function index(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');

        $staffs = User::where('role', 'staff')
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

    public function storeAvailability(Request $request, User $staff)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'status' => 'required|in:available,booked',
            'court_id' => 'required|integer',
            'detail' => 'nullable|string',
        ]);

        $startHour = Carbon::parse($validated['start_time'])->hour;
        $endHour = Carbon::parse($validated['end_time'])->hour;

        for ($h = $startHour; $h < $endHour; $h++) {
            $staff->availabilities()->updateOrCreate(
                [
                    'date' => $validated['date'],
                    'start_time' => sprintf('%02d:00:00', $h),
                    'end_time' => sprintf('%02d:00:00', $h + 1),
                    'court_id' => $validated['court_id'],
                ],
                [
                    'status' => $validated['status'],
                    'detail' => $validated['detail'] ?? null,
                ]
            );
        }

        return redirect()->back()->with('success', 'บันทึกสถานะช่วงเวลาสำเร็จ!');
    }

    public function updateProfile(Request $request, User $staff)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $staff->id,
            'membership_type' => self::MEMBERSHIP_TYPE_RULE,
            'phone' => 'nullable|string|max:20',
            'specialty' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
        ]);

        $staff->update($request->only(['name', 'email', 'membership_type', 'phone']));

        $staff->staffProfile()->updateOrCreate(
            ['user_id' => $staff->id],
            $request->only(['specialty', 'bio'])
        );

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

    public function destroy(User $staff)
    {
        if ($staff->role !== 'staff') {
            return redirect()->back()->withErrors(['ไม่สามารถลบผู้ใช้งานนี้ได้']);
        }

        $staff->delete();

        return redirect()->back()->with('success', 'ลบข้อมูลบุคลากรเรียบร้อยแล้ว');
    }

    private function assertIsStaff(User $staff): void
    {
        abort_unless($staff->role === 'staff', 404, 'ไม่พบข้อมูลบุคลากร');
    }
}