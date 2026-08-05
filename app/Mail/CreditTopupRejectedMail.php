<?php

namespace App\Mail;

use App\Models\CreditTopupRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * แจ้งลูกค้าเมื่อคำขอเติมเครดิตด้วยตัวเอง (แนบสลิป) ถูกแอดมินปฏิเสธ — เช่น สลิปไม่ชัด,
 * ยอดเงินไม่ตรง, ตรวจสอบไม่พบรายการโอนจริง ฯลฯ พร้อมเหตุผลที่แอดมินระบุไว้
 *
 * ส่งแบบ synchronous — ดูเหตุผลใน CreditTopupRequestedMail (ไม่มี queue worker ให้ใช้บนโฮสติ้งนี้)
 */
class CreditTopupRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CreditTopupRequest $topupRequest)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "คำขอเติมเครดิต #{$this->topupRequest->id} ไม่ผ่านการตรวจสอบ",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credit-topup-rejected',
            with: ['topupRequest' => $this->topupRequest],
        );
    }
}
