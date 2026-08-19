<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    // หน้าค้นหาและแสดงรายชื่อผู้ใช้
    public function index(Request $request)
    {
        $search = $request->query('search');

        $users = User::whereIn('role', ['admin','user','staff', 'superadmin'])
            ->where('id', '>', 0)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
                });
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
        $actor = $request->user();
        $actorIsSuperadmin = $actor->role === 'superadmin';

        if (! $actorIsSuperadmin && $user->role === 'superadmin') {
            abort(403, 'ไม่สามารถแก้ไข role ของ superadmin ได้');
        }

        $allowedRoles = $actorIsSuperadmin
            ? ['user', 'staff', 'admin', 'superadmin']
            : ['user', 'staff', 'admin'];

        $request->validate([
            'role' => ['required', Rule::in($allowedRoles)],
        ], [
            'role.in' => 'Role ไม่ถูกต้อง',
        ]);

        $newRole = $request->role;
        $updates = ['role' => $newRole];

        if (in_array($newRole, ['admin', 'superadmin'], true)) {
            // Admin/Superadmin ต้องมี membership_type เป็น 'admin' เสมอ บังคับ overwrite ทุกครั้ง
            $updates['membership_type'] = 'admin';
        } else {
            // เดิม: กรณี user/staff เท่านั้น เช็คว่าค่าปัจจุบัน valid กับ role ใหม่ไหม
            $validTypesForNewRole = $newRole === 'staff'
                ? array_keys(User::STAFF_TYPES)
                : array_keys(User::MEMBERSHIP_TYPES);

            if (!in_array($user->membership_type, $validTypesForNewRole, true)) {
                $updates['membership_type'] = $newRole === 'staff' ? 'permanent' : 'customer';
            }
        }

        $user->update($updates);

        return back()->with('success', "เปลี่ยน role ของ {$user->us_name} เป็น {$newRole} เรียบร้อยแล้ว");
    }

    // แก้ไขประเภทสมาชิก (ชุดตัวเลือกจะต่างกันตาม role ของ user คนนั้น)
    public function updateMembershipType(Request $request, User $user)
    {
        abort_if(
            in_array($user->role, ['admin', 'superadmin'], true),
            403,
            'ไม่สามารถแก้ไขประเภทสมาชิกของแอดมินได้'
        );

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

        return back()->with('success', "อัปเดตประเภทสมาชิกของ {$user->us_name} เรียบร้อย");
    }

    public function updateProfile(Request $request, User $user)
    {
        $actor = $request->user();
        $actorIsSuperadmin = $actor->role === 'superadmin';

        // 1. ป้องกันไม่ให้แอดมินธรรมดาแก้ไขข้อมูลของ Super Admin
        if (! $actorIsSuperadmin && $user->role === 'superadmin') {
            abort(403, 'ไม่สามารถแก้ไขข้อมูลของ superadmin ได้');
        }

        // 2. กำหนด Role ที่อนุญาตให้เปลี่ยนได้ตามสิทธิ์ของคนแก้
        $allowedRoles = $actorIsSuperadmin
            ? ['user', 'staff', 'admin', 'superadmin']
            : ['user', 'staff', 'admin'];

        $rules = [
            'name' => 'required|string|max:255',
            'us_name' => 'required|string|max:255|unique:users,us_name,'.$user->id,
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'role' => ['required', Rule::in($allowedRoles)],
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];

        // 3. ถ้า Role ใหม่ที่ส่งมา ไม่ใช่แอดมิน จำเป็นต้องตรวจสอบประเภทสมาชิกด้วย
        if (! in_array($request->role, ['admin', 'superadmin'], true)) {
            // อนุญาตค่า membership_type ทั้งแบบ staff และ user (รวม Array Keys เข้าด้วยกัน)
            $validMembershipTypes = array_merge(array_keys(User::MEMBERSHIP_TYPES), array_keys(User::STAFF_TYPES));
            $rules['membership_type'] = ['required', Rule::in($validMembershipTypes)];
        }

        $validated = $request->validate($rules, [
            'name.required' => 'กรุณากรอกชื่อ-นามสกุล',
            'name.max' => 'ชื่อ-นามสกุลต้องมีความยาวไม่เกิน :max ตัวอักษร',
            'us_name.required' => 'กรุณากรอกชื่อบัญชีผู้ใช้',
            'us_name.unique' => 'ชื่อบัญชีผู้ใช้นี้ถูกใช้แล้ว',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'role.required' => 'กรุณาเลือกบทบาท',
            'role.in' => 'บทบาทไม่ถูกต้อง',
            'membership_type.required' => 'กรุณาเลือกประเภทสมาชิก',
            'membership_type.in' => 'ประเภทสมาชิกไม่ถูกต้อง',
            'avatar.image' => 'ไฟล์ที่อัปโหลดต้องเป็นรูปภาพเท่านั้น',
            'avatar.mimes' => 'รองรับเฉพาะไฟล์ jpeg, png, jpg และ webp',
            'avatar.max' => 'ขนาดรูปภาพต้องไม่เกิน 2MB',
        ]);

        // 1. กรณีสั่งลบรูปภาพ
        if ($request->input('remove_avatar') == '1') {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = null;

            // อัปเดตให้ฝั่งโปรไฟล์โค้ชว่างเปล่าด้วย (ถ้ามีข้อมูล)
            if ($user->staffProfile) {
                $user->staffProfile->profile_image = null;
                $user->staffProfile->save();
            }
        }

        // 2. จัดการอัปโหลดรูปภาพโปรไฟล์ใหม่
        if ($request->hasFile('avatar')) {
            // ลบรูปภาพเดิมใน storage ถ้ามี
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            
            // บันทึกรูปใหม่และเก็บ path
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $avatarPath;

            // ซิงค์ path รูปภาพเดียวกันให้กับฝั่งโปรไฟล์โค้ชด้วย (ถ้ามีข้อมูล)
            if ($user->staffProfile) {
                $user->staffProfile->profile_image = $avatarPath;
                $user->staffProfile->save();
            }
        }

        $user->fill($request->only(['name', 'us_name', 'email', 'phone']));
        $user->role = $validated['role'];

        if (in_array($validated['role'], ['admin', 'superadmin'], true)) {
            $user->membership_type = 'admin';

            // 4. อัปเดตข้อมูลทั่วไป
            $user->fill($request->only(['name', 'us_name', 'email', 'phone']));
            $user->role = $validated['role'];
        }
        
        // 5. จัดการ Membership Type ตามเงื่อนไขของ Role
        if (in_array($validated['role'], ['admin', 'superadmin'], true)) {
            $user->membership_type = 'admin'; // บังคับเป็น admin
        } else {
            if ($request->filled('membership_type')) {
                $user->membership_type = $validated['membership_type'];
            } else {
                // กรณีฉุกเฉิน fallback
                $user->membership_type = $validated['role'] === 'staff' ? 'permanent' : 'customer';
            }
        }

        $user->save();

        return redirect()->back()->with('success', "อัปเดตข้อมูลส่วนตัวของ {$user->name} เรียบร้อยแล้ว");
    }

    public function destroy(Request $request, User $user)
    {
        $actor = $request->user();

        abort_if($user->id === $actor->id, 403, 'ไม่สามารถลบบัญชีของตนเองได้');
        abort_if($user->role === 'superadmin', 403, 'ไม่สามารถลบบัญชี Super Admin ได้');
        abort_if(
            $user->role === 'admin' && $actor->role !== 'superadmin',
            403,
            'เฉพาะ Super Admin เท่านั้นที่สามารถลบบัญชี Admin ได้'
        );

        $profileImage = $user->staffProfile?->profile_image;
        $name = $user->us_name;

        $user->delete();

        if ($profileImage) {
            Storage::disk('public')->delete($profileImage);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', "ลบบัญชีผู้ใช้ {$name} ออกจากระบบเรียบร้อยแล้ว");
    }
}
