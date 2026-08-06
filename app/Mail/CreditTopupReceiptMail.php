<?php

namespace App\Mail;

use App\Models\CreditTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * ใบเสร็จ + แจ้งเตือน "เติมเครดิตสำเร็จ" ส่งให้ลูกค้าทุกครั้งที่เครดิตถูกเติมเข้าบัญชีจริง
 * (ไม่ว่าจะมาจากแอดมินเติมให้มือ หรือจากคำขอเติมด้วยตัวเองที่แอดมินอนุมัติแล้ว) ใช้ CreditTransaction
 * เดียวกับที่บันทึกไว้ในระบบเป็นแหล่งข้อมูล ไม่ต้องส่งพารามิเตอร์แยกให้ซ้ำซ้อน/เพี้ยนจากของจริง
 *
 * ส่งแบบ synchronous — ดูเหตุผลใน CreditTopupRequestedMail (ไม่มี queue worker ให้ใช้บนโฮสติ้งนี้)
 */
class CreditTopupReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CreditTransaction $transaction)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "ใบเสร็จเติมเครดิต #{$this->transaction->id} — THATA HOMECOURT",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credit-topup-receipt',
            with: ['transaction' => $this->transaction],
        );
    }
}
