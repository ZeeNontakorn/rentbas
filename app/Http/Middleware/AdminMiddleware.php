<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AdminMiddleware — ตรวจสอบว่าผู้ใช้ปัจจุบันมี role == 'admin' หรือ 'superadmin' หรือไม่
 *
 * ถ้าผู้ใช้ยังไม่ล็อกอิน → ส่งกลับไปหน้า login
 * ถ้าผู้ใช้ล็อกอินแต่ไม่ใช่ admin/superadmin → redirect ไปหน้า Home ของผู้ใช้ทั่วไป (/ )
 * เพื่อป้องกันไม่ให้ User ธรรมดาเข้าถึงหน้า admin ได้
 */
class AdminMiddleware
{
    /**
     * Role ที่อนุญาตให้เข้าถึง admin route
     */
    private const ALLOWED_ROLES = ['admin', 'superadmin'];

    public function handle(Request $request, Closure $next): Response
    {
        // ยังไม่ล็อกอิน → ให้ไป login
        if (! $request->user()) {
            return redirect()->route('login');
        }

        // ล็อกอินแล้วแต่ไม่ใช่ admin/superadmin → กลับไปหน้าจองของ user
        if (! in_array($request->user()->role, self::ALLOWED_ROLES, true)) {
            return redirect()->route('home')
                ->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        return $next($request);
    }
}
