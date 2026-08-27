<?php

namespace App\Mail;

use App\Models\GroupRound;
use App\Models\GroupRoundSignup;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservePromoted extends Mailable
{
    use SerializesModels;

    public function __construct(
        public GroupRound $round,
        public GroupRoundSignup $signup,
    ) {}

    public function build()
    {
        return $this->subject('คุณได้เลื่อนขึ้นเป็นตัวจริงแล้ว! รอบ "'.$this->round->title.'"')
            ->view('emails.reserve-promoted');
    }
}