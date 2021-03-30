<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SuccessPay extends Mailable
{
    use Queueable, SerializesModels;

    public $amount;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($amount)
    {
        $this->amount = $amount;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('contact@nyleo.fr')
            ->subject("Confirmation de règlement")
            ->view('emails.successpay-mail');
    }
}
