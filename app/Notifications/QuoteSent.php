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

        $mail = $this->message($notifiable, "Votre devis {$q->number}".($q->subject ? " — {$q->subject}" : ''), 'Votre devis est prêt', 'Devis '.$q->number)
            ->line('Merci pour votre demande. Vous trouverez votre devis en pièce jointe (fichier PDF).')
            ->line('**Pour l\'accepter**, cliquez sur le bouton ci-dessous, relisez le devis, puis cliquez sur « Je signe le devis ». Aucun mot de passe n\'est nécessaire.')
            ->action('Lire et signer mon devis', self::portalUrl($notifiable, route('portal.quotes.show', $q, false)))
            ->line('Dès votre accord, je commence les plans. Si vous avez des plans ou des photos de la maison, vous pouvez me les envoyer depuis votre espace ou en réponse à cet e-mail : cela me fait gagner du temps.')
            ->attachData(PdfService::quote($q)->output(), PdfService::quoteFilename($q), ['mime' => 'application/pdf']);

        $this->recap($mail, [
            'Projet' => $q->subject ?: self::project($q->dossier),
            'Montant' => money($q->total_ttc),
            'Valable jusqu\'au' => $q->valid_until?->format('d/m/Y'),
        ]);

        return $this->preheader($mail, 'Devis de '.money($q->total_ttc).' à signer en ligne, sans mot de passe.');
    }
}
