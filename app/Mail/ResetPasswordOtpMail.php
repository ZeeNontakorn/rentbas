<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;

    public function __construct(string $otp)
    {
        $this->otp = $otp;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'รีเซ็ตรหัสผ่าน - Basketball Court Booking',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password-otp',
            with: ['otp' => $this->otp],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
