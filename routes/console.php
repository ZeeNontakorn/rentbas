<?php

use App\Services\CreditService;
use App\Services\PrivateTrainingBookingLifecycleService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;
use Mailtrap\Helper\ResponseHelper;
use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mime\Address;
use App\Models\GroupRound;


// Command เดิม
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Command ส่งเมลที่ปรับปรุงแล้ว
Artisan::command('send-mail', function () {
    $this->info('กำลังเริ่มส่งอีเมลยืนยัน OTP...'); // แสดงสถานะใน Terminal

    try {
        $email = (new MailtrapEmail)
            ->from(new Address('hello@demomailtrap.co', 'BCCB Arena System'))
            ->to(new Address('dreamtori2005@gmail.com'))
            ->subject('Your OTP Verification Code')
            ->category('OTP BCBS verification')
            ->text('ขอบคุณที่ใช้บริการ BCCB! รหัสยืนยันของคุณคือ: '.rand(1000, 9999));

        $response = MailtrapClient::initSendingEmails(
            apiKey: config('services.mailtrap.token') ?? 'c11ee6fc73c7421322868772cfe52d51'
        )->send($email);

        $this->info('ส่งเมลสำเร็จแล้ว!');
        $this->line(json_encode(ResponseHelper::toArray($response), JSON_PRETTY_PRINT));

    } catch (Exception $e) {
        $this->error('เกิดข้อผิดพลาด: '.$e->getMessage());
    }
})->purpose('ส่งเมลทดสอบสำหรับระบบ OTP');

Schedule::call(function (): void {
    app(PrivateTrainingBookingLifecycleService::class)->expireUnprocessedBookings();
})->name('private-training:expire-unprocessed')->everyMinute()->withoutOverlapping();

// เซ็ตยอดเครดิตของ user ที่หมดอายุแล้วให้เป็น 0 จริง พร้อมบันทึกลง credit_transactions
//
// สำคัญ: ไม่ใช้ ->dailyAt('01:00') ตรงๆ อีกต่อไป — บนโฮสต์ที่ไม่มี cron จริง (ตัว schedule:run ถูก
// กระตุ้นผ่าน traffic จริงโดย RunScheduleOpportunistically middleware แทน) ตัว isDue() ของ dailyAt()
// เช็คตรงนาทีเป๊ะๆ เหมือน cron (เทียบเท่า "0 1 * * *") ถ้าไม่มี request ใดเข้ามาเลยในนาทีนั้นพอดี (เช่น
// ตี 1 ไม่มีใครเข้าเว็บ) งานจะถูก "ข้ามไปเลยทั้งวัน" ไม่ใช่แค่ "ไปรันช้าตอนมีคนเข้าเว็บครั้งถัดไป" ตามที่
// เข้าใจผิดไว้ตอนแรก (พบจริงจาก production: อัปโหลดแล้วรอข้ามคืน เครดิตก็ไม่ถูกตัดเพราะไม่มีใครเข้าเว็บ
// ช่วงตี 1 พอดี) จึงเปลี่ยนมาเช็คเองแบบ "ทำได้ทุกเวลาตั้งแต่ 01:00 เป็นต้นไป ถ้ายังไม่เคยทำของวันนี้"
// แทน — ทนทานต่อการถูกเช็คช้าแค่ไหนก็ได้ ขอแค่มี request เข้ามาสักครั้งหลัง 01:00 ของวันนั้น
Schedule::call(function (): void {
    $today = now()->toDateString();

    if (now()->hour < 1 || Cache::get('schedule-ran:credits-expire-due') === $today) {
        return;
    }

    app(CreditService::class)->expireDueCredits();
    Cache::put('schedule-ran:credits-expire-due', $today, now()->addDays(2));
})->name('credits:expire-due')->everyMinute()->withoutOverlapping();

// แจ้งเตือน user ที่เครดิตใกล้หมดอายุ (ภายใน 7 วัน) ทั้งขึ้นกระดิ่งในเว็บและอีเมล — ใช้ pattern เดียวกับ
// ด้านบน (ทำได้ทุกเวลาตั้งแต่ 08:00 เป็นต้นไป ถ้ายังไม่เคยทำของวันนี้) ด้วยเหตุผลเดียวกัน
Schedule::call(function (): void {
    $today = now()->toDateString();

    if (now()->hour < 8 || Cache::get('schedule-ran:credits-notify-expiring-soon') === $today) {
        return;
    }

    app(CreditService::class)->notifyExpiringSoonCredits();
    Cache::put('schedule-ran:credits-notify-expiring-soon', $today, now()->addDays(2));
})->name('credits:notify-expiring-soon')->everyMinute()->withoutOverlapping();
// คืนเครดิตให้คิวสำรองอัตโนมัติ เมื่อหมดเวลาสละสิทธิ์ หรือรอบเล่นจบไปแล้ว
Schedule::call(function (): void {
    GroupRound::whereNull('reserves_processed_at')
        ->where(function ($q) {
            $q->whereNotNull('cancel_deadline')->where('cancel_deadline', '<=', now())
              ->orWhereRaw('play_date < ?', [now()->toDateString()]);
        })
        ->get()
        ->each(fn ($round) => $round->processExpiredReserves());
})->name('group-round:process-expired-reserves')->everyMinute()->withoutOverlapping();
