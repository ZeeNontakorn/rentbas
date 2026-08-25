<?php

namespace App\Mail;

use App\Models\PrivateTrainingBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrivateTrainingRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PrivateTrainingBooking $booking)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "มีคำขอจอง Private Training ใหม่ #".str_pad((string) $this->booking->id, 6, '0', STR_PAD_LEFT),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.private-training-requested',
            with: [
                'booking' => $this->booking,
            ],
        );
    }
}
