<?php

namespace App\Mail;

use App\Models\CreditTopupRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * แจ้งเตือนแอดมินทางอีเมลเมื่อลูกค้าส่งคำขอเติมเครดิตด้วยตัวเอง (แนบสลิป) เข้ามาใหม่
 * ต้องรอแอดมินเข้าไปตรวจสลิปแล้วกดอนุมัติ/ปฏิเสธที่หน้าแอดมินคำขอเติมเครดิตก่อน เครดิตถึงจะเข้าจริง
 *
 * หมายเหตุ: ส่งแบบ synchronous (->send() ตรงๆ) ไม่ใช้ ShouldQueue เพราะโฮสติ้งนี้ไม่มี
 * CLI/SSH/cron ให้รัน `php artisan queue:work` — ถ้า mail ถูก queue ไว้จะไม่มีอะไรมาประมวลผล
 * แล้วจะไม่ถูกส่งออกจริงเลย (ดู CheckoutController สำหรับรายละเอียดข้อจำกัดนี้)
 */
class CreditTopupRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CreditTopupRequest $topupRequest)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "แจ้งเตือน: มีคำขอเติมเครดิตใหม่ #{$this->topupRequest->id} รอตรวจสอบ",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credit-topup-requested',
            with: ['topupRequest' => $this->topupRequest],
        );
    }
}
