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
use Illuminate\Support\Facades\Storage;

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
            'us_name' => 'sometimes|string|max:100',
            'name' => 'sometimes|nullable|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . Auth::id(),
            'phone' => 'sometimes|nullable|max:10',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // แก้เป็น avatar
            'current_password' => 'required_with:password|string',
            'password' => 'nullable|required_with:current_password|string|min:6|confirmed',
            'otp' => 'nullable|string|size:6'
        ],[
            'us_name.string' => 'กรุณากรอกชื่อบัญชีให้ถูกต้อง',
            'us_name.max' => 'ชื่อบัญชีต้องมีความยาวไม่เกิน :max ตัวอักษร',
            'name.string' => 'กรุณากรอกชื่อ-นามสกุลให้ถูกต้อง',
            'email.email' => 'กรุณากรอกอีเมลให้ถูกต้อง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'phone.max' => 'เบอร์โทรศัพท์ต้องมีความยาวไม่เกิน :max ตัวอักษร',
            'avatar.image' => 'ไฟล์ที่อัปโหลดต้องเป็นรูปภาพเท่านั้น', // แก้เป็น avatar
            'avatar.mimes' => 'รองรับไฟล์รูปภาพประเภท jpeg, png, jpg, webp เท่านั้น', // แก้เป็น avatar
            'avatar.max' => 'ขนาดไฟล์รูปภาพต้องไม่เกิน 2MB', // แก้เป็น avatar
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
        $us_name = $request->has('us_name') ? $request->us_name : $user->us_name;
        $name = $request->has('name') ? $request->name : $user->name;
        $email = $request->has('email') ? $request->email : $user->email;
        $phone = $request->has('phone') ? $request->phone : $user->phone;
        $emailChanged = $user->email !== $email;

        if ($request->filled('current_password')) {
            if (! Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'รหัสผ่านเดิมไม่ถูกต้อง'])->withInput();
            }
        }

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
            $otpToken->delete();
        }

        // จัดการรูปโปรไฟล์ (ลบ หรือ อัปโหลดใหม่)
        if ($request->input('remove_avatar') == '1') {
            // หากผู้ใช้กดปุ่มลบรูปโปรไฟล์
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar); // ลบไฟล์จาก Storage
            }
            $user->avatar = null; // เคลียร์ชื่อไฟล์ในฐานข้อมูล
        } 
        elseif ($request->hasFile('avatar')) {
            // หากไม่มีการกดลบ แต่มีการอัปโหลดไฟล์ใหม่เข้ามาแทนที่
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $imagePath = $request->file('avatar')->store('profiles', 'public');
            $user->avatar = $imagePath;
        }

        $user->us_name = $us_name;
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
