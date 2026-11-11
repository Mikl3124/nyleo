<?php

namespace App\Notifications;

use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Services\CardPayment;
use App\Services\PdfService;
use App\Support\MailTracking;
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

        $mail = $this->message($notifiable, "Rappel : facture {$i->number}", 'Petit rappel de règlement', 'Facture '.$i->number)
            ->line("Sauf erreur de ma part, la facture {$i->number}".($i->issue_date ? ' du '.$i->issue_date->format('d/m/Y') : '').' n\'est pas encore réglée. Je vous la remets en pièce jointe.')
            ->line('Si vous avez déjà fait le règlement, merci de ne pas tenir compte de ce message.')
            ->action('Consulter ma facture', self::portalUrl($notifiable, route('portal.invoices.show', $i, false)))
            ->attachData($pdf, PdfService::invoiceFilename($i), ['mime' => 'application/pdf']);

        $this->recap($mail, [
            'Projet' => self::project($i->dossier),
            'Reste à régler' => money($i->remaining()),
            'Échéance' => $i->due_date?->format('d/m/Y'),
        ]);
        $cardUrl = CardPayment::url($i);
        $this->panel($mail, 'Comment régler ?', self::paymentLines($s, $i->number, $cardUrl));

        $this->downloads($mail, [['label' => 'Télécharger la facture (PDF)', 'url' => MailTracking::downloadUrl($i)]]);
        $this->track($mail, $notifiable, ['invoice' => $i->id, 'dossier' => $i->dossier_id]);

        return $this->preheader($mail, 'Reste à régler : '.money($i->remaining()).'.');
    }
}
