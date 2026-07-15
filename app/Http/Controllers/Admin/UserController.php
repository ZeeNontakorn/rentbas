<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // หน้าค้นหาและแสดงรายชื่อผู้ใช้
    public function index(Request $request)
    {
        $search = $request->query('search');

        // ค้นหาผู้ใช้จากชื่อ (ถ้ามีการค้นหา) และดึงเฉพาะ role 'user'
         $users = User::whereIn('role', ['admin','user'])
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

        // 1. คำขอจองปัจจุบัน (รอดำเนินการ หรือ อนุมัติแล้ว และวันที่ยังไม่ผ่านไป)
        $currentBookings = Booking::with('court')
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('booking_date', '>=', $today)
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();

        // 2. ประวัติการจอง (ถูกปฏิเสธ, ยกเลิก หรือ วันที่ผ่านไปแล้ว)
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

    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => ['required', 'in:user,staff,admin'],
        ], [
            'role.in' => 'Role ไม่ถูกต้อง',
        ]);

        // กันเหนียว ไม่ให้แก้ role ของ superadmin (id = 0) ผ่านช่องทางนี้
        if ($user->id === 0) {
            abort(403, 'ไม่สามารถแก้ไข role ของ superadmin ได้');
        }

        $user->update(['role' => $request->role]);

        return back()->with('success', "เปลี่ยน role ของ {$user->name} เป็น {$request->role} เรียบร้อยแล้ว");
    }
}