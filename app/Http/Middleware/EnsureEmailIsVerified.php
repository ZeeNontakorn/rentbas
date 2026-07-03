<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
{
    // 1. เช็คว่า Login หรือยัง
    // 2. เช็คว่าใน DB ฟิลด์ is_verified เป็น true หรือไม่
    if (Auth::check() && !Auth::user()->is_verified) {
    return redirect()->route('verify-otp')
        ->with('error', 'กรุณายืนยันรหัส OTP ก่อนเข้าใช้งานระบบจอง');
    }

    return $next($request);
}
}
