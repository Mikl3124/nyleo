<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientActivity;
use App\Models\Document;
use App\Models\Dossier;
use App\Models\EmailMessage;
use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Http\Request;

/**
 * Enregistre ce que fait le client (ouverture d'e-mail, clic, page consultée, PDF téléchargé).
 * Rien n'est enregistré pour Mickaël (admin connecté, ou copie cachée ouverte) ni pour les robots
 * (antivirus et aperçus de liens qui ouvrent les e-mails à la place du client).
 */
class ClientTracking
{
    /** Clé de session posée quand Mickaël ouvre un lien de sa copie : tout ce qu'il fait ensuite est ignoré. */
    public const STAFF_SESSION = 'nyleo_copie';

    /** Paramètre ajouté aux liens de la copie envoyée à Mickaël. */
    public const COPY_PARAM = 'copie';

    /** Deux actions identiques à moins de 2 minutes d'intervalle n'en font qu'une (rechargement, double clic). */
    private const DEDUPE_SECONDS = 120;

    public static function isStaff(?Request $request): bool
    {
        if (! $request) {
            return false;
        }

        return $request->query(self::COPY_PARAM) !== null
            || auth('web')->check()
            || ($request->hasSession() && $request->session()->get(self::STAFF_SESSION));
    }

    public static function isBot(?Request $request): bool
    {
        $ua = (string) $request?->userAgent();

        return $ua === '' || (bool) preg_match('/bot\b|crawl|spider|slurp|preview|scan|curl|wget|python|headless|facebookexternalhit|safelinks|barracuda|proofpoint|mimecast|symantec|trendmicro|bitdefender/i', $ua);
    }

    /**
     * @param  array{client?: ?Client, dossier?: ?Dossier, quote?: ?Quote, invoice?: ?Invoice, document?: ?Document, email?: ?EmailMessage}  $ctx
     */
    public static function record(string $type, string $label, array $ctx, ?Request $request = null): ?ClientActivity
    {
        $request ??= request();
        if (self::isStaff($request) || self::isBot($request)) {
            return null;
        }

        $email = $ctx['email'] ?? null;
        $quote = $ctx['quote'] ?? $email?->quote;
        $invoice = $ctx['invoice'] ?? $email?->invoice;
        $document = $ctx['document'] ?? null;
        $dossier = $ctx['dossier'] ?? $quote?->dossier ?? $invoice?->dossier ?? $document?->dossier ?? $email?->dossier;
        $client = $ctx['client'] ?? $email?->client ?? $quote?->client ?? $invoice?->client ?? $dossier?->client;

        $attributes = [
            'client_id' => $client?->id,
            'email_message_id' => $email?->id,
            'dossier_id' => $dossier?->id,
            'quote_id' => $quote?->id,
            'invoice_id' => $invoice?->id,
            'document_id' => $document?->id,
            'type' => $type,
            'label' => mb_substr($label, 0, 250),
        ];

        $previous = ClientActivity::query()->where($attributes)->latest('id')->first();
        if ($previous && $previous->created_at->gt(now()->subSeconds(self::DEDUPE_SECONDS))) {
            return null;
        }

        $activity = ClientActivity::create($attributes + [
            'ip' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 500) ?: null,
        ]);

        // Le journal du dossier ne reçoit que la première fois de chaque action, pour rester lisible.
        if (! $previous && $dossier) {
            $dossier->log('suivi_client', 'Client : '.$label, false, ['activity_id' => $activity->id]);
        }

        return $activity;
    }

    public static function quoteLabel(Quote $quote): string
    {
        return 'Devis '.$quote->displayNumber();
    }

    public static function invoiceLabel(Invoice $invoice): string
    {
        return $invoice->title();
    }
}
