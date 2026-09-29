<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * รัน Laravel scheduler (`schedule:run`) แบบไม่ต้องพึ่ง cron/SSH ของเซิร์ฟเวอร์เลย — ใช้ตอนโฮสต์เป็น
 * shared hosting ที่ไม่มีแม้แต่เมนู cron job ให้ตั้ง (ต่างจากแนวทาง endpoint+external scheduler)
 *
 * แนวคิด: ทุกครั้งที่มี HTTP request เข้ามาตามปกติ (ผู้ใช้เข้าเว็บ) middleware นี้จะเช็คว่าเวลาผ่านไป
 * นานพอหรือยังนับจากครั้งล่าสุดที่รัน schedule:run (เก็บ timestamp ไว้ใน cache) ถ้านานพอก็จะรันให้
 * "หลังจาก" ส่ง response กลับไปให้ผู้ใช้แล้ว (ผ่าน terminate()) เพื่อไม่ให้ผู้ใช้ต้องรอ
 *
 * เรื่องความแม่นยำเรื่องเวลา: ตัว schedule:run เองเช็ค isDue() ของแต่ละงานเทียบกับเวลาจริงเสมอ (เช่น
 * ->dailyAt('01:00') หรือ ->everyMinute()) ไม่ได้อิงจำนวนครั้งที่มีคนเข้าเว็บ ดังนั้นถ้ามี traffic
 * สม่ำเสมอ (เว็บจองสนามน่าจะมีคนเข้าเรื่อยๆ) งานจะรันตรงเวลาไม่ต่างจาก cron มากนัก จุดอ่อนเดียวคือ
 * ถ้าไม่มีใครเข้าเว็บเลยในช่วงเวลาที่งานควรรัน งานนั้นจะไปรันเอาตอนมีคนเข้าเว็บครั้งถัดไป (delay ไม่ใช่
 * หายไปเลย เพราะ isDue() ของงานที่เป็น ->dailyAt() ยังนับว่า due อยู่จนกว่าจะรันสำเร็จในวันนั้น)
 *
 * ข้อควรระวังสำคัญ: terminate() จะ "ไม่บล็อกผู้ใช้" จริงๆ ก็ต่อเมื่อรันอยู่หลัง PHP-FPM ซึ่งเรียก
 * fastcgi_finish_request() ปิดการเชื่อมต่อกับ browser ก่อนแล้วค่อยรัน terminate() ต่อเบื้องหลัง — แต่
 * production ของโปรเจกต์นี้รันด้วย `php artisan serve` ผ่าน supervisorctl (ดู memory: ไม่ใช่ php-fpm)
 * ซึ่งไม่มี fastcgi_finish_request ผู้ใช้ที่บังเอิญเป็นคนยิง request ที่ทำให้ schedule ทำงานพอดี
 * จะต้องรอ response จนกว่างานนั้นจะรันเสร็จจริงๆ (เช่น วันที่ credits:expire-due ส่งอีเมลหาหลายคน
 * อาจทำให้หน้านั้นโหลดช้าไปสองสามวินาที) ถ้าจะให้ไม่บล็อกผู้ใช้เลย ต้องย้าย production ไปรันผ่าน
 * PHP-FPM (เช่น nginx + php-fpm) แทน `php artisan serve` — ทางเลือกนี้จึงเหมาะกับงานที่ไม่ได้ไวมาก
 * (query DB สั้นๆ) เท่านั้น เพื่อจำกัด impact ต่อผู้ใช้ที่โชคไม่ดี
 *
 * ทดสอบตอน dev แล้วพบว่า ถ้า DB ต่อไม่ติด (ทดสอบโดยปิด MySQL ไว้) PHP จะ fatal ด้วย
 * "Maximum execution time exceeded" ซึ่งไม่สามารถ catch ด้วย try/catch(\Throwable) ได้ เพราะเป็น
 * fatal error ระดับ engine ไม่ใช่ exception ธรรมดา ทำให้ request ของผู้ใช้ที่โชคไม่ดีพังไปเต็มๆ (500)
 * แทนที่จะ fail อย่างสวยงาม ในทางปฏิบัติ DB ของ production เป็น external MySQL ที่ควรเสถียรอยู่แล้ว
 * แต่ถ้าจะให้ทนทานกว่านี้ ควรพิจารณาทางเลือกที่ 1 (cron-job.org ยิง endpoint) แทน เพราะ request ของ
 * ผู้ใช้จริงจะไม่ถูกดึงไปรัน schedule เลย
 */
class RunScheduleOpportunistically
{
    /**
     * อย่างน้อยกี่วินาทีถึงจะลองรัน schedule:run อีกครั้ง — ตั้งไว้ใกล้เคียง 1 นาทีเหมือน cron ปกติ
     * แต่ไม่ต้องเป๊ะ เพราะขึ้นกับ traffic จริงอยู่แล้ว ตั้งสั้นไปจะทำให้ทุก request ต้องมาแย่ง lock
     */
    private const MIN_INTERVAL_SECONDS = 55;

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * ทำงานหลังส่ง response กลับไปให้ผู้ใช้แล้ว (FastCGI finish_request / เทียบเท่า) ผู้ใช้จึงไม่ต้องรอ
     * เวลารันงาน schedule ที่อาจกินเวลา แม้จะรันแบบ synchronous อยู่ในรีเควสต์เดียวกันก็ตาม
     */
    public function terminate(Request $request, Response $response): void
    {
        // เว้น asset/health-check request ที่ยิงถี่ๆ ไม่ต้องมาแย่ง cache ทุกครั้งโดยไม่จำเป็น
        if ($request->is('up') || $request->is('internal/*')) {
            return;
        }

        // ทำเฉพาะ GET/HEAD (เข้าดูหน้าเว็บทั่วไป) — ไม่ทำตอน POST/PUT/DELETE เพื่อไม่ให้การจอง/ชำระเงิน/
        // ฟอร์มต่างๆ ต้องมาเสี่ยงรอ schedule:run รันเสร็จก่อน (ดูหมายเหตุเรื่อง php artisan serve ด้านบน)
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return;
        }

        $lastRunAt = Cache::get('opportunistic-schedule-last-run-at');

        if ($lastRunAt && now()->diffInSeconds($lastRunAt) < self::MIN_INTERVAL_SECONDS) {
            return;
        }

        // Cache::lock กันสอง request ที่เข้ามาพร้อมกันรันซ้อนกัน (atomic แม้ cache driver จะเป็น database)
        $lock = Cache::lock('opportunistic-schedule-lock', 50);

        if (! $lock->get()) {
            return;
        }

        try {
            Artisan::call('schedule:run');
            Cache::put('opportunistic-schedule-last-run-at', now(), now()->addDay());
        } catch (\Throwable $e) {
            Log::error('รัน schedule:run แบบ opportunistic (ไม่มี cron) ไม่สำเร็จ: '.$e->getMessage());
        } finally {
            $lock->release();
        }
    }
}
