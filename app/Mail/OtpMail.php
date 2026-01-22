<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $otp;
    public string $purpose;

    /**
     * Create a new message instance.
     */
    public function __construct(int $otp, string $purpose = 'Login')
    {
        $this->otp = $otp;
        $this->purpose = $purpose;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Your ' . $this->purpose . ' OTP')
            ->view('emails.otp')
            ->with([
                'otp' => $this->otp,
                'purpose' => $this->purpose,
            ]);
    }
}
