<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TwoFactorLoginCode extends Mailable
{
    use Queueable, SerializesModels;

    /** @var string */
    public $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function build(): self
    {
        return $this->subject(__('Your sign-in verification code'))
            ->view('emails.two-factor-login');
    }
}
