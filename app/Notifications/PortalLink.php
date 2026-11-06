<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class PortalLink extends ClientNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        return $this->message($notifiable, 'Votre accès à votre espace Nyleo')
            ->line('Voici votre lien personnel pour suivre votre dossier, consulter vos documents et signer vos devis.')
            ->line('Il suffit de cliquer sur le bouton : aucun mot de passe n\'est nécessaire.')
            ->action('Ouvrir mon espace', self::portalUrl($notifiable))
            ->line('Ce lien est personnel, merci de ne pas le transférer.');
    }
}
