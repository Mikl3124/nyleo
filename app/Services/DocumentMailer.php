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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Envoi « en un clic » d'un devis ou d'une facture avec un texte modifiable et des pièces jointes.
 *
 * $data : to, cc[], subject, body, attach_pdf, include_link, documents[] (ids du dossier),
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

        self::deliver($quote->client, $quote->dossier, $data, $files, $predicted, $quote->number,
            ($data['include_link'] ?? true) && $quote->status === QuoteStatus::Envoye
                ? ['Lire et signer mon devis', ClientNotification::portalUrl($quote->client, route('portal.quotes.show', $quote, false))]
                : (($data['include_link'] ?? false) ? ['Voir mon espace client', ClientNotification::portalUrl($quote->client)] : null),
        );
    }

    public static function sendInvoice(Invoice $invoice, array $data): void
    {
        $predicted = $invoice->number;
        if ($invoice->isDraft()) {
            $predicted = Numbering::peek($invoice->isCreditNote() ? 'credit_note' : 'invoice');
            InvoiceService::issue($invoice, notify: false);
            $invoice->refresh();
        }

        $files = [];
        if ($data['attach_pdf'] ?? true) {
            $files[] = $invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)
                ? ['disk' => 'local', 'path' => $invoice->pdf_path, 'name' => PdfService::invoiceFilename($invoice)]
                : ['data' => PdfService::invoice($invoice)->output(), 'name' => PdfService::invoiceFilename($invoice)];
        }

        self::deliver($invoice->client, $invoice->dossier, $data, $files, $predicted, $invoice->number,
            ($data['include_link'] ?? false)
                ? ['Voir mon espace client', ClientNotification::portalUrl($invoice->client, $invoice->dossier ? route('portal.dossiers.show', $invoice->dossier, false) : null)]
                : null,
        );

        $invoice->forceFill(['sent_at' => now()])->saveQuietly();
    }

    /** Adresse où arrivent les réponses des clients : celle des paramètres (mickael@nyleo.fr), sinon le premier admin. */
    public static function replyAddress(): ?string
    {
        $email = trim((string) CompanySetting::current()->email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : User::query()->orderBy('id')->value('email');
    }

    private static function deliver($client, $dossier, array $data, array $files, ?string $predicted, ?string $number, ?array $button): void
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

        if ($dossier && ! empty($data['documents'])) {
            foreach (Document::where('dossier_id', $dossier->id)->whereIn('id', $data['documents'])->get() as $doc) {
                if (Storage::disk('local')->exists($doc->path)) {
                    $files[] = ['disk' => 'local', 'path' => $doc->path, 'name' => self::filename($doc->name, $doc->path)];
                }
            }
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
            $mail->send(new DocumentMail($subject, $body, $files, $button[0] ?? null, $button[1] ?? null, $reply));
        } finally {
            Storage::disk('local')->delete($uploads);
        }

        $dossier?->log('email', "E-mail « {$subject} » envoyé à {$to}", false, [
            'to' => $to, 'cc' => $cc, 'subject' => $subject, 'body' => $body,
            'attachments' => array_column($files, 'name'),
        ]);
    }

    private static function filename(string $name, string $path): string
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        return $ext && ! str_ends_with(mb_strtolower($name), '.'.mb_strtolower($ext)) ? $name.'.'.$ext : $name;
    }
}
