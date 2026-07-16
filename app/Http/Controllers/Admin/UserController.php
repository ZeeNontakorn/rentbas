<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // หน้าค้นหาและแสดงรายชื่อผู้ใช้
    public function index(Request $request)
    {
        $search = $request->query('search');

        $users = User::whereIn('role', ['admin','user','staff'])
            ->where('id', '>', 0)
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    // หน้าแสดงข้อมูลเชิงลึกของผู้ใช้ 1 คน (ประวัติ/คำขอปัจจุบัน)
    public function show(User $user)
    {
        $today = now()->toDateString();

        $currentBookings = Booking::with('court')
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('booking_date', '>=', $today)
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();

        $pastBookings = Booking::with('court')
            ->where('user_id', $user->id)
            ->where(function($q) use ($today) {
                $q->whereIn('status', ['rejected', 'cancelled'])
                  ->orWhereDate('booking_date', '<', $today);
            })
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->get();

        return view('admin.users.show', compact('user', 'currentBookings', 'pastBookings'));
    }

    // แก้ไข role (user / staff / admin)
    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => ['required', 'in:user,staff,admin'],
        ], [
            'role.in' => 'Role ไม่ถูกต้อง',
        ]);

        if ($user->id === 0) {
            abort(403, 'ไม่สามารถแก้ไข role ของ superadmin ได้');
        }

        $newRole = $request->role;
        $updates = ['role' => $newRole];

        // ถ้าเปลี่ยน role แล้วประเภทสมาชิกเดิมใช้ไม่ได้กับ role ใหม่ ให้รีเซ็ตเป็นค่าเริ่มต้นที่เหมาะสม
        $validTypesForNewRole = $newRole === 'staff'
            ? array_keys(User::STAFF_TYPES)
            : array_keys(User::MEMBERSHIP_TYPES);

        if (!in_array($user->membership_type, $validTypesForNewRole, true)) {
            $updates['membership_type'] = $newRole === 'staff' ? 'permanent' : 'customer';
        }

        $user->update($updates);

        return back()->with('success', "เปลี่ยน role ของ {$user->name} เป็น {$newRole} เรียบร้อยแล้ว");
    }

    // แก้ไขประเภทสมาชิก (ชุดตัวเลือกจะต่างกันตาม role ของ user คนนั้น)
    public function updateMembershipType(Request $request, User $user)
    {
        abort_if($user->role === 'admin', 403, 'ไม่สามารถแก้ไขประเภทสมาชิกของแอดมินได้');

        $allowedValues = $user->role === 'staff'
            ? array_keys(User::STAFF_TYPES)
            : array_keys(User::MEMBERSHIP_TYPES);

        $data = $request->validate([
            'membership_type' => ['required', Rule::in($allowedValues)],
        ]);

        $user->update(['membership_type' => $data['membership_type']]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'membership_type' => $user->membership_type,
                'label' => $user->membershipTypeLabel(),
            ]);
        }

        return back()->with('success', "อัปเดตประเภทสมาชิกของ {$user->name} เรียบร้อย");
    }
}