<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedCreditMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    public function build()
    {
        return $this->subject('ยืนยันการจองสำเร็จ - THATA HOMECOURT')
            ->view('emails.booking-confirmed-credit', ['booking' => $this->booking]);
    }
}
