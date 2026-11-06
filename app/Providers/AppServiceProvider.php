<?php

namespace App\Providers;

use App\Support\MailCopy;
use App\Support\MailTracking;
use App\Support\Typography;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
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
        // Tous les e-mails : typographie française (la ponctuation ne part jamais seule à la ligne),
        // puis suivi des e-mails clients (App\Support\MailTracking) ou, sinon, copie cachée à Mickaël (App\Support\MailCopy).
        Event::listen(MessageSending::class, function ($event) {
            $message = $event->message;
            if ($message instanceof Email) {
                if (is_string($html = $message->getHtmlBody())) {
                    $message->html(Typography::html($html));
                }
                if (is_string($text = $message->getTextBody())) {
                    $message->text(Typography::text($text));
                }
                if ($subject = $message->getSubject()) {
                    $message->subject(Typography::text($subject));
                }
                if (! MailTracking::process($message, $event->data)) {
                    MailCopy::apply($message, $event->data);
                }
            }
        });
        Event::listen(MessageSent::class, function (MessageSent $event) {
            if ($event->message instanceof Email) {
                MailTracking::sent($event->message, $event->sent->getMessageId());
            }
        });
    }
}
