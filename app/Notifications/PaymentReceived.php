<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentReceived extends ClientNotification
{
    public function __construct(public Invoice $invoice, public Payment $payment) {}

    public function toMail(object $notifiable): MailMessage
    {
        $i = $this->invoice;
        $mail = $this->message($notifiable, "Paiement bien reçu — facture {$i->number}")
            ->line('Je vous confirme la bonne réception de votre règlement de **'.money($this->payment->amount).'** ('
                .(Payment::METHODS[$this->payment->method] ?? $this->payment->method).') pour la facture '.$i->number.'. Merci !');
        if ($i->remaining() > 0) {
            $mail->line('Il reste '.money($i->remaining()).' à régler.');
        } elseif ($i->type->value === 'facture' && $i->dossier) {
            $mail->line('Je m\'occupe maintenant du dépôt de votre dossier en mairie et je vous tiens informé.');
        }

        return $mail->action('Suivre mon dossier', self::portalUrl($notifiable, $i->dossier ? route('portal.dossiers.show', $i->dossier, false) : null));
    }
}
