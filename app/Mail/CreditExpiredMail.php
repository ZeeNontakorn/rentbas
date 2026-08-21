<?php

namespace App\Mail;

use App\Models\CreditTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * แจ้งลูกค้าเมื่อเครดิตหมดอายุแล้วถูกระบบตัดยอดเป็น 0 อัตโนมัติ — ส่งจาก
 * CreditService::expireDueCredits() (scheduled job) ทันทีที่ตัดยอดสำเร็จ กันลูกค้าตกใจเห็นยอดหาย
 * โดยไม่รู้สาเหตุ
 *
 * ส่งแบบ synchronous — ดูเหตุผลใน CreditTopupRequestedMail (ไม่มี queue worker ให้ใช้บนโฮสติ้งนี้)
 */
class CreditExpiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CreditTransaction $transaction)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'เครดิตของคุณหมดอายุแล้ว — THATA HOMECOURT',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credit-expired',
            with: ['transaction' => $this->transaction],
        );
    }
}
