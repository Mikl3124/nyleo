<?php

namespace App\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $title, public string $body, public ?string $url = null, public bool $mail = true) {}

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

        return $this->url ? $mail->action('Ouvrir dans l\'administration', $this->url) : $mail;
    }
}
