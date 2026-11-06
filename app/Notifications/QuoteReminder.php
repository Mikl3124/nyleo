<?php

namespace App\Notifications;

use App\Models\Quote;
use Illuminate\Notifications\Messages\MailMessage;

class QuoteReminder extends ClientNotification
{
    public function __construct(public Quote $quote) {}

    public function toMail(object $notifiable): MailMessage
    {
        $q = $this->quote;

        return $this->message($notifiable, "Rappel : votre devis {$q->number}")
            ->line("Je me permets de revenir vers vous au sujet du devis {$q->number} envoyé le {$q->sent_at?->format('d/m/Y')}.")
            ->line('Si le devis vous convient, vous pouvez le signer en un clic depuis le lien ci-dessous. Si vous avez une question, n\'hésitez pas à m\'appeler.')
            ->action('Voir mon devis', self::portalUrl($notifiable, route('portal.quotes.show', $q, false)));
    }
}
