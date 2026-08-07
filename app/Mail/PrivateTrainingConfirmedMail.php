<?php

namespace App\Mail;

use App\Models\PrivateTrainingBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrivateTrainingConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PrivateTrainingBooking $booking)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ยืนยันการจอง Private Training ของคุณแล้ว',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.private-training-confirmed',
        );
    }
}
