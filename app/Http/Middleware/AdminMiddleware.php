<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AdminMiddleware — ตรวจสอบว่าผู้ใช้ปัจจุบันมี role == 'admin' หรือไม่
 *
 * ถ้าผู้ใช้ยังไม่ล็อกอิน → ส่งกลับไปหน้า login
 * ถ้าผู้ใช้ล็อกอินแต่ไม่ใช่ admin → redirect ไปหน้า Home ของผู้ใช้ทั่วไป (/booking)
 *   เพื่อป้องกันไม่ให้ User ธรรมดาเข้าถึงหน้า admin ได้
 */
class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // ยังไม่ล็อกอิน → ให้ไป login
        if (! $request->user()) {
            return redirect()->route('login');
        }

        // ล็อกอินแล้วแต่ไม่ใช่ admin → กลับไปหน้าจองของ user
        if ($request->user()->role !== 'admin') {
            return redirect()->route('booking.index')
                ->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        return $next($request);
    }
}
