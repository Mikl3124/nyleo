<?php

namespace App\Notifications;

use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Services\PdfService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Storage;

class InvoiceReminder extends ClientNotification
{
    public function __construct(public Invoice $invoice) {}

    public function toMail(object $notifiable): MailMessage
    {
        $i = $this->invoice;
        $s = CompanySetting::current();
        $pdf = $i->pdf_path && Storage::disk('local')->exists($i->pdf_path)
            ? Storage::disk('local')->get($i->pdf_path) : PdfService::invoice($i)->output();

        $mail = $this->message($notifiable, "Rappel : facture {$i->number}")
            ->line("Sauf erreur de ma part, la facture {$i->number} du {$i->issue_date?->format('d/m/Y')} reste à régler, pour un montant de **".money($i->remaining()).'**.')
            ->line('Si vous avez déjà effectué le règlement, merci de ne pas tenir compte de ce message.');
        if ($s->iban) {
            $mail->line('Virement : IBAN '.$s->iban.", référence {$i->number}.");
        }
        if ($s->cheque_order) {
            $mail->line('Chèque à l\'ordre de '.$s->cheque_order.'.');
        }

        return $mail
            ->action('Voir ma facture', self::portalUrl($notifiable, $i->dossier ? route('portal.dossiers.show', $i->dossier, false) : null))
            ->attachData($pdf, PdfService::invoiceFilename($i), ['mime' => 'application/pdf']);
    }
}
