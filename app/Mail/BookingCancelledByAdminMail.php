<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCancelledByAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;
    public string $reason;

    public function __construct(Booking $booking, string $reason)
    {
        $this->booking = $booking;
        $this->reason = $reason;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'การจองของคุณถูกยกเลิกโดยระบบ',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-cancelled-by-admin',
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
