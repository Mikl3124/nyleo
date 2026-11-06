<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\Dossier;
use App\Support\MailTracking;
use Illuminate\Notifications\Messages\MailMessage;

class DossierUpdate extends ClientNotification
{
    /** $document : plans (ou autre PDF) proposés en téléchargement direct sous le bouton. */
    public function __construct(public Dossier $dossier, public string $subject, public array $lines, public string $button = 'Voir mon dossier', public ?Document $document = null) {}

    public function toMail(object $notifiable): MailMessage
    {
        $lines = array_values(array_filter($this->lines));
        $mail = $this->message($notifiable, $this->subject.' — '.$this->dossier->title, $this->subject, 'Votre dossier '.$this->dossier->reference);
        foreach ($lines as $line) {
            $mail->line($line);
        }
        $mail->action($this->button, self::portalUrl($notifiable, route('portal.dossiers.show', $this->dossier, false)));

        if ($this->document) {
            $this->downloads($mail, [['label' => 'Télécharger « '.$this->document->displayName().' » (PDF)', 'url' => MailTracking::downloadUrl($this->document)]]);
        }
        $this->track($mail, $notifiable, ['dossier' => $this->dossier->id, 'document' => $this->document?->id]);

        $this->recap($mail, ['Projet' => $this->dossier->title, 'Référence' => $this->dossier->reference]);

        return $lines ? $this->preheader($mail, strip_tags(str_replace('**', '', $lines[0]))) : $mail;
    }
}
