<?php

namespace App\Notifications;

use App\Models\Quote;
use App\Support\MailTracking;
use Illuminate\Notifications\Messages\MailMessage;

class QuoteReminder extends ClientNotification
{
    public function __construct(public Quote $quote) {}

    public function toMail(object $notifiable): MailMessage
    {
        $q = $this->quote;

        $mail = $this->message($notifiable, "Votre devis {$q->number} est toujours disponible", 'Avez-vous pu lire votre devis ?', 'Devis '.$q->number)
            ->line('Je me permets de revenir vers vous au sujet du devis que je vous ai envoyé'.($q->sent_at ? ' le '.$q->sent_at->format('d/m/Y') : '').'.')
            ->line('S\'il vous convient, vous pouvez le signer en un clic avec le bouton ci-dessous. Si vous souhaitez en parler ou le modifier, n\'hésitez pas à m\'appeler.')
            ->action('Voir et signer mon devis', self::portalUrl($notifiable, route('portal.quotes.show', $q, false)))
            ->line('Si votre projet est en pause ou abandonné, un simple mot en réponse à cet e-mail me suffit.');

        $this->recap($mail, [
            'Projet' => $q->subject ?: self::project($q->dossier),
            'Montant' => money($q->total_ttc),
            'Valable jusqu\'au' => $q->valid_until?->format('d/m/Y'),
        ]);

        $this->downloads($mail, [['label' => 'Télécharger le devis (PDF)', 'url' => MailTracking::downloadUrl($q)]]);
        $this->track($mail, $notifiable, ['quote' => $q->id, 'dossier' => $q->dossier_id]);

        return $this->preheader($mail, 'Votre devis vous attend : vous pouvez le signer en un clic.');
    }
}
