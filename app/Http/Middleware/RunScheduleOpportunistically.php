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
 * fastcgi_finish_request() ปิดการเชื่อมต่อกับ browser ก่อนแล้วค่อยรัน terminate() ต่อเบื้องหลัง —
 * ตรวจสอบแล้วว่า production จริงของโดเมน courts.ninetytwotech.co.th (ที่ deploy แบบ FTP บน ruk-com)
 * วิ่งผ่าน nginx + PHP-FPM (เห็น header X-ACCEL-INTERNAL ตอน debug ปัญหาอื่น) ไม่ใช่ `php artisan
 * serve` แบบที่เอกสารรุ่นก่อนหน้าสมมติไว้ (อันนั้นเป็นข้อมูลของโดเมนเก่า demo.ninetytwotech.co.th
 * คนละ deployment กัน) ดังนั้นในทางปฏิบัติ terminate() ควรไม่บล็อก user จริงตามที่ออกแบบไว้
 *
 * เหตุผลที่เลือกใช้แนวทางนี้แทนคู่มือ cron ปกติ: บนแพ็กเกจ ruk-com ที่ใช้อยู่ DirectAdmin ให้ตั้ง
 * cron job ได้ แต่พอรันจริงกลับ error/ใช้ไม่ได้ (คาดว่า PHP-CLI ถูกปิดสำหรับ cron บนแพ็กเกจนี้)
 * middleware นี้เลี่ยงปัญหานั้นได้เพราะรันผ่าน request ของเว็บปกติ (PHP-FPM ที่เสิร์ฟหน้าเว็บอยู่แล้ว)
 * ไม่ได้พึ่ง PHP-CLI เลย และในทางปฏิบัติมี traffic กระตุ้นถี่มากอยู่แล้วจาก polling ที่มีอยู่ก่อนแล้ว
 * ในระบบ (navbar.blade.php: แจ้งเตือนทุก 10 วิ, ยอดเครดิตทุก 5 วิ ตราบใดที่มีแท็บเปิดอยู่) จึงมักไม่
 * ต้องรอ "คนเข้าเว็บบังเอิญ" เลย — ขอแค่มีคนล็อกอินเปิดหน้าทิ้งไว้ก็พอ
 *
 * Trade-off ที่ยังคงมีอยู่เทียบกับ cron ทุกนาที (ไม่เปลี่ยนแม้ยืนยันแล้วว่าเป็น PHP-FPM):
 * 1) ทุก GET/HEAD request ที่ผ่านเข้ามา (รวม polling ที่ถี่มาก) ต้องเสีย Cache::get() เพิ่ม 1 ครั้งเพื่อ
 *    เช็คว่าถึงเวลารึยัง — ถ้า CACHE_STORE เป็น database คือ query เพิ่มทุก request จริงๆ (แม้เล็กน้อย)
 * 2) ตอนถึงเวลารันจริง (~ทุก 55 วิ) จะไปกิน CPU/DB time ของ worker ที่กำลังเสิร์ฟ user คนนั้นอยู่พอดี
 *    ก่อนปล่อยคืน pool ต่างจาก cron ที่รันแยกโปรเซสไม่แย่ง worker ของ user จริงเลย
 * ถ้าโหลดสูงขึ้นมากในอนาคตจนกังวลจุดนี้ พิจารณาทางเลือก feature/cron-http-endpoint (external
 * scheduler ยิง URL แยก) แทน แต่ต้องเช็คก่อนว่า route แบบ HTTP ธรรมดาใช้ได้ปกติบนโฮสต์นี้
 *
 * ทดสอบตอน dev แล้วพบว่า ถ้า DB ต่อไม่ติด (ทดสอบโดยปิด MySQL ไว้) PHP จะ fatal ด้วย
 * "Maximum execution time exceeded" ซึ่งไม่สามารถ catch ด้วย try/catch(\Throwable) ได้ เพราะเป็น
 * fatal error ระดับ engine ไม่ใช่ exception ธรรมดา ทำให้ request ของผู้ใช้ที่โชคไม่ดีพังไปเต็มๆ (500)
 * แทนที่จะ fail อย่างสวยงาม ในทางปฏิบัติ DB ของ production เป็น external MySQL ที่ควรเสถียรอยู่แล้ว
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
        // ฟอร์มต่างๆ ต้องมาเสี่ยงรอ schedule:run รันเสร็จก่อน (กันไว้เผื่อกรณีที่ fastcgi_finish_request
        // ใช้ไม่ได้ด้วยเหตุผลอะไรก็ตาม — ดูหมายเหตุเรื่อง PHP-FPM ด้านบน)
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return;
        }

        // ครอบทั้งก้อนด้วย try/catch ชั้นนอกสุด — middleware ตัวนี้รันทุก request ทั้งเว็บ ต่อให้ภายใน
        // พังด้วยเหตุผลอะไรก็ตาม (cache เพี้ยน, DB ล่ม, ฯลฯ) ต้อง "กลืน" error ไว้เอง ห้ามหลุดออกไปทำให้
        // ทั้งหน้าเว็บ 500 เด็ดขาด เพราะงาน schedule ไม่ควรมีสิทธิ์ทำให้ผู้ใช้จริงเข้าเว็บไม่ได้
        //
        // บั๊กที่เคยเกิดจริง (เจอตอนทดสอบ local): เดิมเก็บ now() (object Carbon) ลง Cache::put() ตรงๆ
        // พอ unserialize กลับมาแล้วเจอ "__PHP_Incomplete_Class" (สาเหตุ deserialize object จาก cache
        // driver ที่ใช้ serialize() ธรรมดา ผิดพลาดได้ง่ายกว่าที่คิด) แล้วโค้ดจุดเทียบเวลาที่อยู่ "นอก"
        // try/catch เดิม โยน TypeError ออกมาตรงๆ ทำให้ทุกหน้าในเว็บ 500 หมด — แก้โดยเก็บเป็น string
        // (toDateTimeString) แทน object กัน deserialize พังแบบนี้ซ้ำ และย้าย try/catch มาครอบทั้งก้อน
        try {
            $lastRunAt = Cache::get('opportunistic-schedule-last-run-at');

            // ใช้ abs() เสมอ — Carbon 3 (โปรเจกต์นี้ใช้ ^3.8) เปลี่ยน diffInSeconds() ให้คืนค่าติดลบได้
            // เมื่อ $lastRunAt อยู่ในอดีต (ต่างจาก Carbon 2 ที่ default เป็นค่าสัมบูรณ์เสมอ) ถ้าลืม abs()
            // ผลลัพธ์จะติดลบตลอด (เช่น -1398) ซึ่ง < 55 เสมอ ทำให้เข้าเงื่อนไข "ยังไม่ถึงเวลา" ทุกครั้ง
            // แล้ว schedule:run จะไม่ถูกเรียกอีกเลยหลังจากครั้งแรก (บั๊กนี้เกิดขึ้นจริงตอนทดสอบ)
            if ($lastRunAt && abs(now()->diffInSeconds($lastRunAt)) < self::MIN_INTERVAL_SECONDS) {
                return;
            }

            // Cache::lock กันสอง request ที่เข้ามาพร้อมกันรันซ้อนกัน (atomic แม้ cache driver จะเป็น database)
            $lock = Cache::lock('opportunistic-schedule-lock', 50);

            if (! $lock->get()) {
                return;
            }

            try {
                Artisan::call('schedule:run');
                Cache::put('opportunistic-schedule-last-run-at', now()->toDateTimeString(), now()->addDay());
            } finally {
                $lock->release();
            }
        } catch (\Throwable $e) {
            Log::error('รัน schedule:run แบบ opportunistic (ไม่มี cron) ไม่สำเร็จ: '.$e->getMessage());
        }
    }
}
