<?php

namespace App\Notifications;

use App\Models\Quote;
use App\Services\PdfService;
use Illuminate\Notifications\Messages\MailMessage;

class QuoteSent extends ClientNotification
{
    public function __construct(public Quote $quote) {}

    public function toMail(object $notifiable): MailMessage
    {
        $q = $this->quote;

        return $this->message($notifiable, "Votre devis {$q->number}".($q->subject ? " — {$q->subject}" : ''))
            ->line('Vous trouverez ci-joint le devis pour la réalisation de votre dossier'.($q->subject ? ' : '.$q->subject : '').'.')
            ->line('Montant : **'.money($q->total_ttc).'**'.($q->valid_until ? ', valable jusqu\'au '.$q->valid_until->format('d/m/Y') : '').'.')
            ->line('Pour l\'accepter, il suffit de cliquer sur le bouton ci-dessous, de vérifier le devis puis de cliquer sur « Je signe le devis ». Aucun mot de passe n\'est nécessaire.')
            ->action('Lire et signer mon devis', self::portalUrl($notifiable, route('portal.quotes.show', $q, false)))
            ->line('Dès votre accord, je commence la réalisation des plans. Vous pourrez aussi m\'envoyer vos photos et les informations utiles depuis votre espace.')
            ->attachData(PdfService::quote($q)->output(), PdfService::quoteFilename($q), ['mime' => 'application/pdf']);
    }
}
