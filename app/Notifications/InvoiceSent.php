<?php

namespace App\Notifications;

use App\Enums\InvoiceType;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Services\CardPayment;
use App\Services\PdfService;
use App\Support\MailTracking;
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
        $url = self::portalUrl($notifiable, $i->dossier ? route('portal.dossiers.show', $i->dossier, false) : null);

        if ($i->type === InvoiceType::Avoir) {
            $mail = $this->message($notifiable, "Avoir {$i->number}", 'Votre avoir', 'Avoir '.$i->number)
                ->line("Vous trouverez ci-joint l'avoir {$i->number} (fichier PDF). Il vient en déduction de la facture concernée.")
                ->action('Consulter mon avoir', $url);
            $this->downloads($mail, [['label' => "Télécharger l'avoir (PDF)", 'url' => MailTracking::downloadUrl($i)]]);
            $this->track($mail, $notifiable, ['invoice' => $i->id, 'dossier' => $i->dossier_id]);
            $this->recap($mail, ['Projet' => self::project($i->dossier), 'Montant' => money($i->total_ttc)]);

            return $this->preheader($mail, 'Avoir de '.money($i->total_ttc).'.')
                ->attachData($pdf, PdfService::invoiceFilename($i), ['mime' => 'application/pdf']);
        }

        $isFinal = $i->type === InvoiceType::Facture && $i->dossier;
        $mail = $this->message($notifiable, "{$i->type->label()} {$i->number}",
            $i->type === InvoiceType::Acompte ? 'Votre facture d\'acompte' : 'Votre facture', $i->type->label().' '.$i->number)
            ->line("Vous trouverez ci-joint la {$this->lower($i->type->label())} {$i->number} (fichier PDF).");
        if ($isFinal) {
            $mail->line('**Comme convenu, je dépose votre dossier en mairie dès réception du règlement.**');
        }
        $cardUrl = CardPayment::url($i);
        $mail->action('Consulter ma facture', $url);
        $this->payButton($mail, $cardUrl, 'Payer '.money($i->remaining()).' par carte');
        $this->downloads($mail, [['label' => 'Télécharger la facture (PDF)', 'url' => MailTracking::downloadUrl($i)]]);
        $this->track($mail, $notifiable, ['invoice' => $i->id, 'dossier' => $i->dossier_id]);
        if ($s->iban && $i->dossier) {
            $mail->line('Dans votre espace client, un QR code permet aussi de payer par virement depuis l\'application de votre banque.');
        }

        $this->recap($mail, [
            'Projet' => self::project($i->dossier),
            'Montant à régler' => money($i->amount_due),
            'À régler avant le' => $i->due_date?->format('d/m/Y'),
        ]);
        $this->panel($mail, 'Comment régler ?', self::paymentLines($s, $i->number, $cardUrl));

        return $this->preheader($mail, 'Montant à régler : '.money($i->amount_due).($isFinal ? '. Dépôt en mairie dès réception.' : '.'))
            ->attachData($pdf, PdfService::invoiceFilename($i), ['mime' => 'application/pdf']);
    }

    private function lower(string $label): string
    {
        return mb_strtolower(mb_substr($label, 0, 1)).mb_substr($label, 1);
    }
}
