<?php

namespace App\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class AdminAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /** Taille maximale des pièces jointes d'une alerte (au-delà, Mickael ouvre le dossier). */
    public const MAX_ATTACHMENTS_BYTES = 15 * 1024 * 1024;

    /**
     * @param  array<int, array{path: string, name: string}>  $files  fichiers du disque « local » à joindre à l'e-mail
     */
    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
        public bool $mail = true,
        public array $files = [],
        public string $actionLabel = 'Ouvrir dans l\'administration',
    ) {}

    public function via(object $notifiable): array
    {
        return $this->mail ? ['database', 'mail'] : ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $n = FilamentNotification::make()->title($this->title)->body($this->body);
        if ($this->url) {
            $n->actions([Action::make('open')->label('Ouvrir')->url($this->url)->markAsRead()]);
        }

        return $n->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('[Nyleo] '.$this->title)
            ->line($this->body)
            ->salutation('Nyleo · votre administration');
        $mail->viewData = [
            'eyebrow' => 'Notification Nyleo',
            'title' => $this->title,
            'preheader' => $this->body,
            'buttonColor' => 'secondary', // bleu : couleur des actions de l'administration
        ];

        if ($this->files) {
            $total = 0;
            $attached = 0;
            foreach ($this->files as $file) {
                $mail->line('• '.$file['name']);
                $disk = Storage::disk('local');
                if (! $disk->exists($file['path'])) {
                    continue;
                }
                $size = $disk->size($file['path']);
                if ($total + $size <= self::MAX_ATTACHMENTS_BYTES) {
                    $mail->attach($disk->path($file['path']), ['as' => $file['name']]);
                    $total += $size;
                    $attached++;
                }
            }
            $mail->line(match (true) {
                $attached === count($this->files) => $attached > 1 ? 'Les fichiers sont joints à cet e-mail.' : 'Le fichier est joint à cet e-mail.',
                $attached === 0 => 'Fichiers trop lourds pour l\'e-mail : ouvre le dossier pour les voir.',
                default => 'Une partie des fichiers est jointe ; les plus lourds sont à voir dans le dossier.',
            });
        }

        return $this->url ? $mail->action($this->actionLabel, $this->url) : $mail;
    }
}
