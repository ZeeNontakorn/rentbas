<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpToken;
use App\Models\User;
use App\Mail\SendOtpMail;
use App\Mail\ResetPasswordOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:users,name'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'name.required' => 'กรุณากรอกชื่อผู้ใช้',
            'name.max' => 'ชื่อผู้ใช้ต้องมีความยาวไม่เกิน :max ตัวอักษร',
            'name.unique' => 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว กรุณาเลือกชื่ออื่น',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'กรุณากรอกอีเมลให้ถูกต้อง',
            'email.max' => 'อีเมลต้องมีความยาวไม่เกิน :max ตัวอักษร',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'phone.required' => 'กรุณากรอกเบอร์โทรศัพท์',
            'phone.max' => 'เบอร์โทรศัพท์ต้องมีความยาวไม่เกิน :max ตัวอักษร',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.min' => 'รหัสผ่านต้องมีความยาวอย่างน้อย :min ตัวอักษร',
            'password.confirmed' => 'การยืนยันรหัสผ่านไม่ตรงกัน',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'user',
            'is_verified' => false,
        ]);

        $this->issueOtp($user);

        // Remember pending user in session for OTP verification step
        $request->session()->put('pending_otp_user_id', $user->id);

        return redirect()->route('verify-otp')
            ->with('success', 'บัญชีถูกสร้างเรียบร้อยแล้ว — กรุณากรอกรหัส OTP ที่ส่งไปยังอีเมลของคุณ');
    }

    public function showVerifyOtp(Request $request)
    {
        if (! $request->session()->has('pending_otp_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.verify-otp');
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'otp_code' => ['required', 'string', 'size:6'],
        ]);

        $userId = $request->session()->get('pending_otp_user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);

        $token = OtpToken::where('user_id', $user->id)
            ->where('otp_code', $data['otp_code'])
            ->latest()
            ->first();

        if (! $token || $token->isExpired()) {
            return back()->withErrors(['otp_code' => 'รหัส OTP ไม่ถูกต้องหรือหมดอายุแล้ว']);
        }

        $user->update(['is_verified' => true]);
        OtpToken::where('user_id', $user->id)->delete();

        $request->session()->forget('pending_otp_user_id');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'ยืนยันบัญชีเรียบร้อย');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['email' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง'])->onlyInput('email');
        }

        if (! $user->is_verified) {
            $this->issueOtp($user); // ส่ง OTP รอบใหม่ให้ทันที เพราะรอบเก่าอาจหมดอายุ/ถูกลบไปแล้ว
            $request->session()->put('pending_otp_user_id', $user->id);

            return redirect()->route('verify-otp')
                ->with('error', 'กรุณายืนยันรหัส OTP ก่อนเข้าใช้งานระบบ — เราส่งรหัสใหม่ไปให้ทางอีเมลแล้ว');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Create a fresh 6-digit OTP valid for 10 minutes.
     */
    protected function issueOtp(User $user): string
    {
        OtpToken::where('user_id', $user->id)->delete();

        $code = (string) random_int(100000, 999999);

        OtpToken::create([
            'user_id' => $user->id,
            'otp_code' => $code,
            'expired_at' => now()->addMinutes(5),
        ]);

        Mail::to($user->email)->send(new SendOtpMail($code));

        return $code;
    }

    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        // เพื่อความปลอดภัย ไม่บอกว่าอีเมลมีหรือไม่มีในระบบ
        if ($user) {
            $otp = (string) random_int(100000, 999999);

            // เก็บ OTP ใน session พร้อม email และเวลาหมดอายุ
            $request->session()->put('reset_otp', [
                'code'       => $otp,
                'email'      => $user->email,
                'expires_at' => now()->addMinutes(5)->timestamp,
            ]);

            // ส่ง OTP ทาง email
            Mail::to($user->email)->send(new ResetPasswordOtpMail($otp));
        }

        // redirect ไปหน้า verify OTP เสมอ (ไม่ว่าจะหาเจอหรือไม่)
        return redirect()->route('password.otp.form')
            ->with('reset_email', $request->email)
            ->with('status', 'หากอีเมลนี้มีในระบบ รหัส OTP จะถูกส่งไปให้แล้ว');
    }

    public function resendOtp(Request $request)
    {
        $userId = $request->session()->get('pending_otp_user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);

        // ใช้ฟังก์ชัน issueOtp ที่เราแยกไว้ (นี่คือข้อดีของการแยก Method ครับ!)
        $this->issueOtp($user);

        return back()->with('success', 'ส่งรหัส OTP ใหม่ไปยังอีเมลของคุณเรียบร้อยแล้ว (มีอายุ 5 นาที)');
    }

    public function showResetOtpForm(Request $request)
    {
        // ถ้าไม่มี session OTP ให้กลับไปหน้าลืมรหัสผ่าน
        if (!$request->session()->has('reset_otp')) {
            return redirect()->route('password.request');
        }
        return view('auth.reset-password-otp', [
            'email' => $request->session()->get('reset_otp.email'),
        ]);
    }

    public function verifyResetOtp(Request $request)
    {
        $request->validate([
            'otp_code' => ['required', 'string', 'size:6'],
        ]);

        $session = $request->session()->get('reset_otp');

        if (!$session) {
            return redirect()->route('password.request')
                ->withErrors(['otp_code' => 'เซสชันหมดอายุแล้ว กรุณาขอ OTP ใหม่']);
        }

        if (now()->timestamp > $session['expires_at']) {
            $request->session()->forget('reset_otp');
            return redirect()->route('password.request')
                ->withErrors(['email' => 'รหัส OTP หมดอายุแล้ว กรุณาขอใหม่']);
        }

        if ($request->otp_code !== $session['code']) {
            return back()->withErrors(['otp_code' => 'รหัส OTP ไม่ถูกต้อง']);
        }

        // OTP ถูกต้อง — ให้ตั้งรหัสใหม่ได้
        $request->session()->put('reset_otp_verified', true);

        return redirect()->route('password.reset.form', [
            'email' => $session['email'],
        ]);
    }

    public function showResetPasswordForm(Request $request)
    {
        if (!$request->session()->get('reset_otp_verified')) {
            return redirect()->route('password.request');
        }

        $email = $request->session()->get('reset_otp.email');

        return view('auth.reset-password', compact('email'));
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // ตรวจสอบว่าผ่าน OTP แล้วจริง
        if (!$request->session()->get('reset_otp_verified')) {
            return redirect()->route('password.request');
        }

        $sessionEmail = $request->session()->get('reset_otp.email');
        if ($request->email !== $sessionEmail) {
            return back()->withErrors(['email' => 'อีเมลไม่ตรงกัน']);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'ไม่พบผู้ใช้งานในระบบ']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        // ล้าง session ที่เกี่ยวกับ reset
        $request->session()->forget(['reset_otp', 'reset_otp_verified']);

        return redirect()->route('login')
            ->with('status', 'รีเซ็ตรหัสผ่านเรียบร้อยแล้ว กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่');
    }
}
