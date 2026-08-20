<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * แจ้งลูกค้าเมื่อเครดิตของตัวเองใกล้หมดอายุ (ภายใน 7 วัน) — ส่งจาก CreditService::notifyExpiringSoonCredits()
 * ซึ่งรันเป็น scheduled job รายวัน แจ้งครั้งเดียวต่อรอบวันหมดอายุ (ดู credit_expiry_notified_for)
 *
 * ส่งแบบ synchronous — ดูเหตุผลใน CreditTopupRequestedMail (ไม่มี queue worker ให้ใช้บนโฮสติ้งนี้)
 */
class CreditExpiringSoonMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'เครดิตของคุณใกล้หมดอายุ — THATA HOMECOURT',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credit-expiring-soon',
            with: ['user' => $this->user],
        );
    }
}
