<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Mail\MailStep1ToAdmin;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class JobStep1ToAdmin implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user = $this->user;
        $mailadmin = env('MAIL_ADMIN');
        dd($mailadmin);
        Mail::to(env("MAIL_ADMIN"))->queue(new MailStep1ToAdmin($user));
    }
}
