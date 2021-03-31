<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SuccessPayToAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public $amount;
    public $customer;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($amount, $customer)
    {
        $this->amount = $amount;
        $this->customer = $customer;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('contact@nyleo.fr', 'Nyleo Conception')
            ->subject("Vous avez reçu un paiement !")
            ->view('emails.successpay-to-admin-mail');
    }
}
