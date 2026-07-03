<?php

use App\Models\Booking;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Mailtrap\Helper\ResponseHelper;
use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mime\Address;

// Command เดิม
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Command ส่งเมลที่ปรับปรุงแล้ว
Artisan::command('send-mail', function () {
    $this->info('กำลังเริ่มส่งอีเมลยืนยัน OTP...'); // แสดงสถานะใน Terminal

    try {
        $email = (new MailtrapEmail())
            ->from(new Address('hello@demomailtrap.co', 'BCCB Arena System'))
            ->to(new Address('dreamtori2005@gmail.com'))
            ->subject('Your OTP Verification Code')
            ->category('OTP BCBS verification')
            ->text('ขอบคุณที่ใช้บริการ BCCB! รหัสยืนยันของคุณคือ: ' . rand(1000, 9999));

        $response = MailtrapClient::initSendingEmails(
            apiKey: config('services.mailtrap.token') ?? 'c11ee6fc73c7421322868772cfe52d51'
        )->send($email);

        $this->info('ส่งเมลสำเร็จแล้ว!');
        $this->line(json_encode(ResponseHelper::toArray($response), JSON_PRETTY_PRINT));

    } catch (\Exception $e) {
        $this->error('เกิดข้อผิดพลาด: ' . $e->getMessage());
    }
})->purpose('ส่งเมลทดสอบสำหรับระบบ OTP');

