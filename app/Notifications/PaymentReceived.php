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
        $left = $i->remaining();
        $mail = $this->message($notifiable, "Paiement bien reçu — facture {$i->number}", 'Merci, votre paiement est bien reçu', 'Facture '.$i->number)
            ->line('Je vous confirme la bonne réception de votre règlement. Merci !');
        if ($left > 0) {
            $mail->line('Il reste **'.money($left).'** à régler sur cette facture.');
        } elseif ($i->type->value === 'facture' && $i->dossier) {
            $mail->line('Je m\'occupe maintenant du **dépôt de votre dossier en mairie** et je vous préviens dès que c\'est fait.');
        }
        $mail->action('Suivre mon dossier', self::portalUrl($notifiable, $i->dossier ? route('portal.dossiers.show', $i->dossier, false) : null));

        $this->recap($mail, [
            'Projet' => self::project($i->dossier),
            'Montant reçu' => money($this->payment->amount),
            'Moyen de paiement' => Payment::METHODS[$this->payment->method] ?? $this->payment->method,
            'Reste à régler' => $left > 0 ? money($left) : null,
        ]);

        return $this->preheader($mail, 'Règlement de '.money($this->payment->amount).' bien reçu, merci.');
    }
}
