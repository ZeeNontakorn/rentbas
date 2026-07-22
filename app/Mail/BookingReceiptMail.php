<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    public function build()
    {
        return $this->subject('ใบเสร็จรับเงิน #' . $this->booking->id . ' - THATA HOMECOURT')
            ->view('emails.booking-receipt', [
                'booking' => $this->booking,
                'breakdown' => $this->booking->price_breakdown ?? [],
            ]);
    }
}
