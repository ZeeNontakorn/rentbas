<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\EnsureEmailIsVerified; // 1. อย่าลืม Import Class มาด้วย
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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
