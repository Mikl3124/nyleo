<?php

namespace App\Notifications;

use App\Enums\InvoiceType;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Services\PdfService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Storage;

class InvoiceSent extends ClientNotification
{
    public function __construct(public Invoice $invoice) {}

    public function toMail(object $notifiable): MailMessage
    {
        $i = $this->invoice;
        $s = CompanySetting::current();
        $pdf = $i->pdf_path && Storage::disk('local')->exists($i->pdf_path)
            ? Storage::disk('local')->get($i->pdf_path) : PdfService::invoice($i)->output();

        $mail = $this->message($notifiable, "{$i->type->label()} {$i->number}");

        if ($i->type === InvoiceType::Avoir) {
            $mail->line("Vous trouverez ci-joint l'avoir {$i->number} d'un montant de ".money($i->total_ttc).'.');
        } else {
            $mail->line("Vous trouverez ci-joint la {$i->type->label()} {$i->number}, d'un montant de **".money($i->amount_due).'**'
                    .($i->due_date ? ', à régler avant le '.$i->due_date->format('d/m/Y') : '').'.');
            if ($i->type === InvoiceType::Facture && $i->dossier) {
                $mail->line('Comme convenu, je déposerai votre dossier en mairie dès réception du règlement.');
            }
            $mail->line('**Règlement possible :**');
            if ($s->iban) {
                $mail->line('• par virement : IBAN '.$s->iban.($s->bic ? ' — BIC '.$s->bic : '').", en indiquant la référence {$i->number} ;");
            }
            if ($s->cheque_order) {
                $mail->line('• par chèque à l\'ordre de '.$s->cheque_order.($s->cheque_address ? ', à envoyer à : '.$s->cheque_address : '').'.');
            }
        }

        return $mail
            ->action('Voir dans mon espace', self::portalUrl($notifiable, $i->dossier ? route('portal.dossiers.show', $i->dossier, false) : null))
            ->attachData($pdf, PdfService::invoiceFilename($i), ['mime' => 'application/pdf']);
    }
}
