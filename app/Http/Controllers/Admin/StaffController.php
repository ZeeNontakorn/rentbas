<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Court;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $role = $request->input('role');

        $staffs = User::whereIn('role', ['coach', 'staff'])
            ->when($role && in_array($role, ['coach', 'staff']), function ($query) use ($role) {
                $query->where('role', $role);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->paginate(10)
            ->withQueryString();

        return view('admin.staffs.index', compact('staffs', 'search', 'role'));
    }

    public function show(User $staff)
    {
        if (!in_array($staff->role, ['staff', 'coach'])) {
            abort(404, 'ไม่พบข้อมูลบุคลากร');
        }

        $today = now()->toDateString();
        $nowTime = now()->toTimeString();

        // 1. ตารางงานในอนาคตและวันนี้ที่ยังไม่หมดเวลา
        $upcomingAvailabilities = $staff->availabilities()
            ->whereIn('status', ['available', 'booked'])
            ->where(function ($query) use ($today, $nowTime) {
                $query->where('date', '>', $today)
                    ->orWhere(function ($q) use ($today, $nowTime) {
                        $q->where('date', $today)->where('end_time', '>', $nowTime);
                    });
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        // 2. ประวัติงานที่ผ่านมาแล้วหรือโดนจอง
        $pastServices = $staff->availabilities()
            ->where(function ($query) use ($today, $nowTime) {
                $query->where('status', 'booked')
                    ->orWhere('date', '<', $today)
                    ->orWhere(function ($q) use ($today, $nowTime) {
                        $q->where('date', $today)->where('end_time', '<=', $nowTime);
                    });
            })
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->get();

        // 3. ดึงข้อมูลสนาม (เรียงตามชื่อสนามจากน้อยไปมาก หรือเปลี่ยนเป็น orderByDesc('id') ได้ตามต้องการ)
        $courts = Court::orderBy('name', 'asc')->get();

        return view('admin.staffs.show', [
            'staff' => $staff,
            'staffProfile' => $staff->staffProfile,
            'upcomingAvailabilities' => $upcomingAvailabilities,
            'pastServices' => $pastServices,
            'courts' => $courts
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
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'specialty' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
        ]);

        $staff->update($request->only(['name', 'phone']));

        $staff->staffProfile()->updateOrCreate(
            ['user_id' => $staff->id],
            $request->only(['specialty', 'bio'])
        );

        return redirect()->back()->with('success', 'แก้ไขข้อมูลโปรไฟล์สำเร็จเรียบร้อย!');
    }
}