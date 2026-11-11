<?php

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Mail\DocumentMail;
use App\Models\CompanySetting;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\ClientNotification;
use App\Support\EmailTemplates;
use App\Support\MailTracking;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Envoi « en un clic » d'un devis ou d'une facture avec un texte modifiable et des pièces jointes.
 *
 * $data : to, cc[], subject, body, attach_pdf, include_link (bouton « Consulter »), include_downloads (liens « Télécharger le PDF »), documents[] (ids du dossier),
 * Les plans encore protégés (PlanAccess) ne sont jamais joints : ils deviennent un bouton « Consulter » ($online).
 * attachments[] (chemins sur le disque local) + attachment_names[chemin => nom], copy_to_me.
 */
class DocumentMailer
{
    public static function sendQuote(Quote $quote, array $data): void
    {
        $predicted = $quote->number ?: Numbering::peek('quote');
        if (in_array($quote->status, [QuoteStatus::Brouillon, QuoteStatus::Envoye], true)) {
            QuoteService::send($quote, notify: false);
            $quote->refresh();
        }

        $files = [];
        if ($data['attach_pdf'] ?? true) {
            $signed = $quote->signed_pdf_path && Storage::disk('local')->exists($quote->signed_pdf_path);
            $files[] = $signed
                ? ['disk' => 'local', 'path' => $quote->signed_pdf_path, 'name' => PdfService::quoteFilename($quote)]
                : ['data' => PdfService::quote($quote)->output(), 'name' => PdfService::quoteFilename($quote)];
        }

        $downloads = ($data['include_downloads'] ?? true)
            ? [['label' => 'Télécharger le devis (PDF)', 'url' => MailTracking::downloadUrl($quote)]] : [];

        self::deliver($quote->client, $quote->dossier, $data, $files, $predicted, $quote->number,
            ($data['include_link'] ?? true)
                ? [$quote->status === QuoteStatus::Envoye ? 'Lire et signer mon devis' : 'Consulter mon devis',
                    ClientNotification::portalUrl($quote->client, route('portal.quotes.show', $quote, false))]
                : null,
            $downloads, ['quote' => $quote->id],
        );
    }

    public static function sendInvoice(Invoice $invoice, array $data): void
    {
        // Case « Paiement par carte » de la fenêtre d'envoi : enregistrée sur la facture, texte ajusté.
        if (array_key_exists('card_payment', $data) && ! $invoice->isCreditNote()) {
            $invoice->forceFill(['card_payment' => (bool) $data['card_payment']])->saveQuietly();
            $data['body'] = EmailTemplates::withCardLine((string) ($data['body'] ?? ''), (bool) $data['card_payment'] && CardPayment::enabled());
        }

        $predicted = $invoice->number;
        if ($invoice->isDraft()) {
            $predicted = Numbering::peek($invoice->isCreditNote() ? 'credit_note' : 'invoice');
            InvoiceService::issue($invoice, notify: false);
            $invoice->refresh();
        }

        $data = self::storePlans($invoice, $data);

        $files = [];
        if ($data['attach_pdf'] ?? true) {
            $files[] = $invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)
                ? ['disk' => 'local', 'path' => $invoice->pdf_path, 'name' => PdfService::invoiceFilename($invoice)]
                : ['data' => PdfService::invoice($invoice)->output(), 'name' => PdfService::invoiceFilename($invoice)];
        }

        $downloads = ($data['include_downloads'] ?? true)
            ? [['label' => 'Télécharger '.($invoice->isCreditNote() ? "l'avoir" : 'la facture').' (PDF)', 'url' => MailTracking::downloadUrl($invoice)]] : [];

        self::deliver($invoice->client, $invoice->dossier, $data, $files, $predicted, $invoice->number,
            ($data['include_link'] ?? true)
                ? [$invoice->isCreditNote() ? 'Consulter mon avoir' : 'Consulter ma facture',
                    ClientNotification::portalUrl($invoice->client, route('portal.invoices.show', $invoice, false))]
                : null,
            // Pas de bouton « Payer par carte » dans l'e-mail (Mickaël, 2026-11-11) : la carte se règle depuis l'espace client.
            $downloads, ['invoice' => $invoice->id],
        );

        $invoice->forceFill(['sent_at' => now()])->saveQuietly();
    }

    /**
     * Plans déposés dans la fenêtre d'envoi : rangés dans le dossier (visibles par le client) et ajoutés aux documents
     * de l'e-mail, où ils deviennent un bouton « Voir les plans » tant qu'ils sont protégés.
     */
    private static function storePlans(Invoice $invoice, array $data): array
    {
        $paths = array_values(array_filter((array) ($data['plans'] ?? [])));
        if ($paths === []) {
            return $data;
        }
        InvoiceService::ensureDossier($invoice);
        $dossier = $invoice->refresh()->dossier;
        $names = (array) ($data['plan_names'] ?? []);
        foreach ($paths as $path) {
            if (! $dossier || ! Storage::disk('local')->exists($path)) {
                continue;
            }
            $original = (string) ($names[$path] ?? basename($path));
            $target = 'dossiers/'.$dossier->id.'/'.Str::random(6).'-'.Str::slug(pathinfo($original, PATHINFO_FILENAME)).'.'.(pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf');
            Storage::disk('local')->move($path, $target);
            $doc = Document::create([
                'dossier_id' => $dossier->id, 'category' => 'plan', 'name' => 'Plans '.$dossier->procedure?->value,
                'path' => $target, 'original_name' => $original, 'visible_to_client' => true, 'uploaded_by' => 'admin',
            ]);
            $dossier->log('document', "Plans ajoutés : {$doc->displayName()}", true);
            $data['documents'] = [...(array) ($data['documents'] ?? []), $doc->id];
        }

        return $data;
    }

    /** Adresse où arrivent les réponses des clients : celle des paramètres (mickael@nyleo.fr), sinon le premier admin. */
    public static function replyAddress(): ?string
    {
        $email = trim((string) CompanySetting::current()->email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : User::query()->orderBy('id')->value('email');
    }

    private static function deliver($client, $dossier, array $data, array $files, ?string $predicted, ?string $number, ?array $button, array $downloads = [], array $tracking = [], ?array $pay = null): void
    {
        $to = trim((string) ($data['to'] ?? $client->email));
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Adresse e-mail du destinataire invalide : {$to}");
        }

        // Le texte a été préparé avec le numéro prévu ; on remet le vrai s'il a changé entre-temps.
        $subject = (string) $data['subject'];
        $body = (string) $data['body'];
        if ($predicted && $number && $predicted !== $number) {
            $subject = str_replace($predicted, $number, $subject);
            $body = str_replace($predicted, $number, $body);
        }

        $online = $consulted = [];
        if ($dossier && ! empty($data['documents'])) {
            foreach (Document::where('dossier_id', $dossier->id)->whereIn('id', $data['documents'])->get() as $doc) {
                // Plans pas encore téléchargeables : ni pièce jointe ni PDF, un bouton « Consulter » en ligne.
                if (PlanAccess::isProtected($doc)) {
                    $online[] = ['label' => 'Voir les plans', 'name' => $doc->displayName(), 'url' => PlanAccess::viewerUrl($doc)];
                    $consulted[] = $doc->displayName().' (consultation en ligne)';

                    continue;
                }
                if (Storage::disk('local')->exists($doc->path)) {
                    $files[] = ['disk' => 'local', 'path' => $doc->path, 'name' => self::filename($doc->name, $doc->path)];
                    if ($data['include_downloads'] ?? true) {
                        $downloads[] = ['label' => 'Télécharger « '.$doc->displayName().' »', 'url' => MailTracking::downloadUrl($doc)];
                    }
                }
            }
        }

        if (count($online) > 1) {
            $online = array_map(fn ($o) => ['label' => 'Voir « '.$o['name'].' »'] + $o, $online);
        }
        if ($online) {
            // Ancien texte (modèle personnalisé) : les plans ne sont plus joints mais consultables en ligne.
            $body = str_replace('Veuillez trouver en pièce jointe les plans établis', 'Vous pouvez consulter en ligne, grâce au bouton « Voir les plans », les plans établis', $body);
        }

        $uploads = array_values(array_filter((array) ($data['attachments'] ?? [])));
        $names = (array) ($data['attachment_names'] ?? []);
        foreach ($uploads as $path) {
            if (Storage::disk('local')->exists($path)) {
                $files[] = ['disk' => 'local', 'path' => $path, 'name' => $names[$path] ?? basename($path)];
            }
        }

        $reply = self::replyAddress();
        $mail = Mail::to($to);
        $cc = array_values(array_filter((array) ($data['cc'] ?? []), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
        if ($cc) {
            $mail->cc($cc);
        }
        if (($data['copy_to_me'] ?? false) && $reply) {
            $mail->bcc($reply);
        }

        try {
            $mail->send(new DocumentMail($subject, $body, $files, $button[0] ?? null, $button[1] ?? null, $reply, $downloads,
                $tracking + ['client' => $client->id, 'dossier' => $dossier?->id], $pay[0] ?? null, $pay[1] ?? null, $online));
        } finally {
            Storage::disk('local')->delete($uploads);
        }

        $dossier?->log('email', "E-mail « {$subject} » envoyé à {$to}", false, [
            'to' => $to, 'cc' => $cc, 'subject' => $subject, 'body' => $body,
            'attachments' => [...array_column($files, 'name'), ...$consulted],
        ]);
    }

    private static function filename(string $name, string $path): string
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        return $ext && ! str_ends_with(mb_strtolower($name), '.'.mb_strtolower($ext)) ? $name.'.'.$ext : $name;
    }
}
