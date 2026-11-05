<?php

namespace App\Notifications;

use App\Models\Client;
use App\Models\CompanySetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

abstract class ClientNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /** Lien de connexion directe (sans mot de passe) valable 30 jours. */
    public static function portalUrl(Client $client, ?string $to = null): string
    {
        return URL::temporarySignedRoute('portal.magic', now()->addDays(30), array_filter([
            'client' => $client->id,
            'to' => $to,
        ]));
    }

    protected function message(Client $client, string $subject): MailMessage
    {
        $settings = CompanySetting::current();

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Bonjour '.trim(($client->civility ? $client->civility.' ' : '').$client->last_name).',')
            ->salutation(new HtmlString(
                'Cordialement,<br>'.nl2br(e($settings->email_signature ?: $settings->displayName()))
            ));
    }
}
