<?php

namespace App\Mail;

use App\Model\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewMessage extends Mailable
{
    use Queueable, SerializesModels;

    public $to_id;
    public $messageField;
    public $from_id;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($to_id, $messageField, $from_id)
    {

        $this->to_id = $to_id;
        $this->messageField = $messageField;
        $this->from_id = User::find($from_id);

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->from($this->from_id->email)
            ->subject("Nouveau message reçu")
            ->view('emails.new-message');
    }
}
