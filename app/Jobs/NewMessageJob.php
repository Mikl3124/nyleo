<?php

namespace App\Jobs;

use App\Model\User;
use App\Mail\NewMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class NewMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $to;
    protected $message;
    protected $from;
    
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($to, $message, $from)
    {
        $this->to = $to;
        $this->message = $message;
        $this->from = $from;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $to = User::find($this->to);
        $to_id = $to->id;

        $message = $this->message;
        $from = User::find($this->from);
        $from_id = $from->id;

        Mail::to($to->email)->queue(new NewMessage($to_id, $message, $from_id));

    }
}
