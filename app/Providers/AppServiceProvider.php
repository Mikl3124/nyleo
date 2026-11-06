<?php

namespace App\Providers;

use App\Support\MailCopy;
use App\Support\Typography;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mime\Email;

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
        // Tous les e-mails : copie cachée à Mickaël (App\Support\MailCopy) et typographie française
        // (la ponctuation ne part jamais seule à la ligne).
        Event::listen(MessageSending::class, function ($event) {
            $message = $event->message;
            if ($message instanceof Email) {
                MailCopy::apply($message, $event->data);
                if (is_string($html = $message->getHtmlBody())) {
                    $message->html(Typography::html($html));
                }
                if (is_string($text = $message->getTextBody())) {
                    $message->text(Typography::text($text));
                }
                if ($subject = $message->getSubject()) {
                    $message->subject(Typography::text($subject));
                }
            }
        });
    }
}
