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
    
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($to, $message, $from)
    {
        $this->to = $to;
        $this->message = $message;

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

        Mail::to($to->email)->queue(new NewMessage($to_id, $message));

    }
}
