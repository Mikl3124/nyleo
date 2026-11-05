<?php

namespace App\Notifications;

use App\Models\Dossier;
use Illuminate\Notifications\Messages\MailMessage;

class DossierUpdate extends ClientNotification
{
    public function __construct(public Dossier $dossier, public string $subject, public array $lines, public string $button = 'Voir mon dossier') {}

    public function toMail(object $notifiable): MailMessage
    {
        $mail = $this->message($notifiable, $this->subject.' — '.$this->dossier->title);
        foreach (array_filter($this->lines) as $line) {
            $mail->line($line);
        }

        return $mail->action($this->button, self::portalUrl($notifiable, route('portal.dossiers.show', $this->dossier, false)));
    }
}
