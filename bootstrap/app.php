<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\EnsureEmailIsVerified; // 1. อย่าลืม Import Class มาด้วย
use App\Http\Middleware\RunScheduleOpportunistically;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'verified_otp' => EnsureEmailIsVerified::class, // 2. เพิ่มบรรทัดนี้เข้าไปครับ
            'staff_or_admin' => \App\Http\Middleware\EnsureStaffOrAdmin::class,
            'permanent_staff_or_admin' => \App\Http\Middleware\EnsurePermanentStaffOrAdmin::class,
        ]);

        // โฮสต์ไม่มี cron/SSH เลย — รัน schedule:run แบบ opportunistic ต่อท้าย request ปกติแทน
        // (ดู RunScheduleOpportunistically) ทำงานหลังส่ง response แล้ว ไม่ทำให้ผู้ใช้ต้องรอ
        $middleware->appendToGroup('web', RunScheduleOpportunistically::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
