<?php

namespace App\Notifications;

use App\Models\Dossier;
use Illuminate\Notifications\Messages\MailMessage;

class DossierUpdate extends ClientNotification
{
    public function __construct(public Dossier $dossier, public string $subject, public array $lines, public string $button = 'Voir mon dossier') {}

    public function toMail(object $notifiable): MailMessage
    {
        $lines = array_values(array_filter($this->lines));
        $mail = $this->message($notifiable, $this->subject.' — '.$this->dossier->title, $this->subject, 'Votre dossier '.$this->dossier->reference);
        foreach ($lines as $line) {
            $mail->line($line);
        }
        $mail->action($this->button, self::portalUrl($notifiable, route('portal.dossiers.show', $this->dossier, false)));

        $this->recap($mail, ['Projet' => $this->dossier->title, 'Référence' => $this->dossier->reference]);

        return $lines ? $this->preheader($mail, strip_tags(str_replace('**', '', $lines[0]))) : $mail;
    }
}
