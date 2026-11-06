<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Typographie française dans tous les e-mails : la ponctuation ne part jamais seule à la ligne.
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Mail\Events\MessageSending::class, function ($event) {
            $message = $event->message;
            if ($message instanceof \Symfony\Component\Mime\Email) {
                if (is_string($html = $message->getHtmlBody())) {
                    $message->html(\App\Support\Typography::html($html));
                }
                if (is_string($text = $message->getTextBody())) {
                    $message->text(\App\Support\Typography::text($text));
                }
                if ($subject = $message->getSubject()) {
                    $message->subject(\App\Support\Typography::text($subject));
                }
            }
        });
    }
}
