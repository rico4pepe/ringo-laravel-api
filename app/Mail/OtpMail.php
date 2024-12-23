<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $expiry;

    /**
     * Create a new message instance.
     * 
      * @param string $otp
     * @param \DateTime $expiry
     * @return void
     */
    public function __construct($otp, $expiry)
    {
        $this->otp = $otp;
        $this->expiry = $expiry;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your OTP Code')
                    ->view('emails.otp');
    }
}
