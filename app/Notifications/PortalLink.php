<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class PortalLink extends ClientNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        $mail = $this->message($notifiable, 'Votre lien pour ouvrir votre espace client Nyleo', 'Votre espace client', 'Connexion')
            ->line('Voici votre lien personnel. Il ouvre directement votre espace client : **aucun mot de passe n\'est nécessaire**.')
            ->line('Vous y retrouvez l\'avancement de votre dossier, vos devis et factures, et les plans à valider.')
            ->action('Ouvrir mon espace client', self::portalUrl($notifiable))
            ->line('Ce lien vous est réservé et reste valable 30 jours : merci de ne pas le transférer. Ensuite, il suffit d\'en demander un nouveau sur la page de connexion.');

        return $this->preheader($mail, 'Un clic suffit pour ouvrir votre espace, sans mot de passe.');
    }
}
