<?php

namespace App\Mail;

use App\Models\CreditTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * แจ้งลูกค้าเมื่อแอดมินหักปรับยอดเครดิตให้ด้วยตนเอง (เช่น แก้ไขเติมผิด/เติมเกิน) — กันลูกค้าตกใจ
 * เห็นยอดหายไปโดยไม่รู้สาเหตุ ส่งจาก CreditController::deduct() หลังหักสำเร็จ
 *
 * ส่งแบบ synchronous — ดูเหตุผลใน CreditTopupRequestedMail (ไม่มี queue worker ให้ใช้บนโฮสติ้งนี้)
 */
class CreditDeductedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CreditTransaction $transaction)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'เครดิตของคุณถูกปรับยอดโดยแอดมิน — THATA HOMECOURT',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credit-deducted',
            with: ['transaction' => $this->transaction],
        );
    }
}
