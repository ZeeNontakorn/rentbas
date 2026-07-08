<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\OtpToken;
use App\Mail\SendOtpMail;
use Carbon\Carbon;

class ProfileController extends Controller
{
    //แสดงหน้าภาพรวมโปรไฟล์
    public function index(){
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    //แสดงหน้าแก้ไขโปรไฟล์
    public function edit(){
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    public function updateProfile(Request $request){

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . Auth::id(),
            'phone' => 'sometimes|nullable|max:10',
            'current_password' => 'required_with:password|string',
            'password' => 'nullable|required_with:current_password|string|min:6|confirmed',
            'otp' => 'nullable|string|size:6'
        ],[
            'name.string' => 'กรุณากรอกชื่อให้ถูกต้อง',
            'name.max' => 'ชื่อต้องมีความยาวไม่เกิน :max ตัวอักษร',
            'email.email' => 'กรุณากรอกอีเมลให้ถูกต้อง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'phone.max' => 'เบอร์โทรศัพท์ต้องมีความยาวไม่เกิน :max ตัวอักษร',
            'current_password.required_with' => 'กรุณากรอกรหัสผ่านเดิมเพื่อเปลี่ยนรหัสผ่านใหม่',
            'current_password.string' => 'กรุณากรอกรหัสผ่านเดิมให้ถูกต้อง',
            'password.required_with' => 'กรุณากรอกรหัสผ่านใหม่',
            'password.string' => 'กรุณากรอกรหัสผ่านใหม่ให้ถูกต้อง',
            'password.min' => 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย :min ตัวอักษร',
            'password.confirmed' => 'การยืนยันรหัสผ่านไม่ตรงกัน',
            'otp.string' => 'กรุณากรอกรหัส OTP ให้ถูกต้อง',
            'otp.size' => 'รหัส OTP ต้องมีความยาว :size หลัก',
        ]);

        $user = Auth::user();
        $name = $request->has('name') ? $request->name : $user->name;
        $email = $request->has('email') ? $request->email : $user->email;
        $phone = $request->has('phone') ? $request->phone : $user->phone;
        $emailChanged = $user->email !== $email;

        if ($request->filled('current_password')) {
            if (! Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'รหัสผ่านเดิมไม่ถูกต้อง'])->withInput();
            }
        }

        // ถ้ามีการเปลี่ยนอีเมลต้องตรวจสอบ OTP
        if ($emailChanged) {
            if (empty($request->otp)) {
                return back()->withErrors(['otp' => 'กรุณาป้อนรหัส OTP เมื่อเปลี่ยนอีเมล']);
            }

            $otpToken = OtpToken::where('user_id', $user->id)
                                ->where('otp_code', $request->otp)
                                ->first();

            if (!$otpToken) {
                return back()->withErrors(['otp' => 'รหัส OTP ไม่ถูกต้อง']);
            }

            if ($otpToken->isExpired()) {
                return back()->withErrors(['otp' => 'รหัส OTP หมดอายุแล้ว']);
            }

            // ลบ OTP token หลังจากใช้งานแล้ว
            $otpToken->delete();
        }

        // อัปเดตข้อมูลทั้งหมด
        $user->name = $name;
        $user->email = $email;
        $user->phone = $phone;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return back()->with('success','อัปเดตข้อมูลสำเร็จ');
    }

    public function requestOtpForEmailChange(Request $request){

        $request->validate([
            'email' => 'required|email|unique:users,email,' . Auth::id()
        ],[
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'กรุณากรอกอีเมลให้ถูกต้อง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
        ]);

        $user = Auth::user();

        // สร้าง OTP code 6 หลัก
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // ลบ OTP token เก่าที่ยังไม่หมดอายุ
        OtpToken::where('user_id', $user->id)->delete();

        // สร้าง OTP token ใหม่ (หมดอายุใน 10 นาที)
        OtpToken::create([
            'user_id' => $user->id,
            'otp_code' => $otp,
            'expired_at' => Carbon::now()->addMinutes(10)
        ]);

        // ส่ง OTP ไปยังอีเมลใหม่
        Mail::to($request->email)->send(new SendOtpMail($otp));

        return response()->json(['success' => true, 'message' => 'ส่งรหัส OTP ไปยังอีเมลแล้ว']);
    }
}