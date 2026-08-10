<?php

namespace App\Mail;

use App\Models\PrivateTrainingBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrivateTrainingRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public PrivateTrainingBooking $booking;
    public string $reason;

    public function __construct(PrivateTrainingBooking $booking, string $reason)
    {
        $this->booking = $booking;
        $this->reason = $reason;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'การจอง Private Training ของคุณถูกปฏิเสธ',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.private-training-rejected',
            with: [
                'booking' => $this->booking,
                'reason' => $this->reason,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
