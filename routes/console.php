<?php

use App\Services\CreditService;
use App\Services\PrivateTrainingBookingLifecycleService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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
Schedule::call(function (): void {
    app(CreditService::class)->expireDueCredits();
})->name('credits:expire-due')->dailyAt('01:00')->withoutOverlapping();

// แจ้งเตือน user ที่เครดิตใกล้หมดอายุ (ภายใน 7 วัน) ทั้งขึ้นกระดิ่งในเว็บและอีเมล
Schedule::call(function (): void {
    app(CreditService::class)->notifyExpiringSoonCredits();
})->name('credits:notify-expiring-soon')->dailyAt('08:00')->withoutOverlapping();
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
