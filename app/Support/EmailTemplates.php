<?php

namespace App\Support;

use App\Enums\InvoiceType;
use App\Enums\Procedure;
use App\Filament\Resources\Dossiers\DossierResource;
use App\Models\CompanySetting;
use App\Models\Dossier;
use App\Models\Invoice;
use App\Models\Quote;
use App\Services\Numbering;

/**
 * Textes d'envoi des devis et factures, repris des e-mails que Mickaël envoyait à la main
 * (« Déclaration préalable de travaux - Véranda - Envoi du devis », « Plans DP Véranda + facture »).
 */
class EmailTemplates
{
    public const QUOTE_SUBJECT = '{Procedure} - {nature} - Envoi du devis';

    public const QUOTE_BODY = <<<'TXT'
Bonjour,

Suite à notre conversation téléphonique, veuillez trouver ci-joint le devis {numero} correspondant à la prestation relative à la {procedure} pour votre projet.

Dès réception de votre bon pour accord, je pourrai lancer la production des plans. Vous pouvez accepter le devis en ligne grâce au bouton « Lire et signer mon devis » ci-dessous : aucun mot de passe n'est nécessaire.

Par ailleurs, si vous disposez de plans de l'existant, je suis preneur de les recevoir en retour de mail. Ces éléments m'aident à redessiner la maison et permettent ainsi de produire les plans plus rapidement.

Je reste bien entendu à votre disposition pour toute question ou précision complémentaire.
TXT;

    public const INVOICE_SUBJECT = 'Plans {sigle} {nature} + facture';

    public const INVOICE_BODY = <<<'TXT'
Bonjour,

Veuillez trouver en pièce jointe les plans établis pour votre {procedure}. Si ces documents correspondent à vos attentes, je procéderai, comme convenu, au dépôt de la demande à réception du règlement de la facture {numero} relative à ma prestation ({montant}), également jointe à ce message.

{reglement}

{infos_cerfa}

Je vous remercie par avance pour votre retour.
TXT;

    public const DEPOSIT_SUBJECT = "Facture d'acompte {numero}";

    public const DEPOSIT_BODY = <<<'TXT'
Bonjour,

Veuillez trouver ci-joint la facture d'acompte {numero}, d'un montant de {montant}, relative au devis que vous avez accepté.

{reglement}

Je reste à votre disposition pour toute question.
TXT;

    public const CREDIT_SUBJECT = 'Avoir {numero}';

    public const CREDIT_BODY = <<<'TXT'
Bonjour,

Veuillez trouver ci-joint l'avoir {numero}, d'un montant de {montant}.

Je reste à votre disposition pour toute question.
TXT;

    /** Variables utilisables dans les modèles (affichées dans les Paramètres). */
    public const PLACEHOLDERS = [
        '{numero}' => 'numéro du devis ou de la facture',
        '{montant}' => 'montant TTC (reste à payer pour une facture)',
        '{procedure}' => 'déclaration préalable de travaux, permis de construire…',
        '{Procedure}' => 'idem avec une majuscule',
        '{sigle}' => 'DP, PC, PA',
        '{nature}' => 'Véranda, Pergola, Carport…',
        '{projet}' => 'intitulé du dossier',
        '{client}' => 'nom du client',
        '{validite}' => 'date de fin de validité du devis',
        '{echeance}' => "date d'échéance de la facture",
        '{reglement}' => 'IBAN et ordre du chèque (factures)',
        '{infos_cerfa}' => 'demande de date et lieu de naissance, seulement si elles manquent',
    ];

    /** @return array{subject: string, body: string} */
    public static function forQuote(Quote $quote): array
    {
        $s = CompanySetting::current();
        $vars = self::common($quote->dossier, $quote->client) + [
            '{numero}' => $quote->number ?: Numbering::peek('quote'),
            '{montant}' => money($quote->total_ttc),
            '{validite}' => ($quote->valid_until ?? now()->addDays((int) $s->quote_validity_days))->format('d/m/Y'),
            '{echeance}' => '',
            '{reglement}' => '',
        ];

        return [
            'subject' => self::subject(filled($s->quote_email_subject) ? $s->quote_email_subject : self::QUOTE_SUBJECT, $vars, 'Devis '.$vars['{numero}']),
            'body' => self::body(filled($s->quote_email_body) ? $s->quote_email_body : self::QUOTE_BODY, $vars),
        ];
    }

    /** @return array{subject: string, body: string} */
    public static function forInvoice(Invoice $invoice): array
    {
        $s = CompanySetting::current();
        $number = $invoice->number ?: Numbering::peek($invoice->isCreditNote() ? 'credit_note' : 'invoice');
        $vars = self::common($invoice->dossier, $invoice->client) + [
            '{numero}' => $number,
            '{montant}' => money($invoice->isCreditNote() ? $invoice->total_ttc : ($invoice->isDraft() ? $invoice->amount_due : $invoice->remaining())),
            '{validite}' => '',
            '{echeance}' => $invoice->due_date?->format('d/m/Y') ?? 'à réception',
            '{reglement}' => self::paymentBlock($s, $number),
        ];

        [$subject, $body] = match ($invoice->type) {
            InvoiceType::Acompte => [self::DEPOSIT_SUBJECT, self::DEPOSIT_BODY],
            InvoiceType::Avoir => [self::CREDIT_SUBJECT, self::CREDIT_BODY],
            default => [
                filled($s->invoice_email_subject) ? $s->invoice_email_subject : self::INVOICE_SUBJECT,
                filled($s->invoice_email_body) ? $s->invoice_email_body : self::INVOICE_BODY,
            ],
        };

        return [
            'subject' => self::subject($subject, $vars, $invoice->type->label().' '.$number),
            'body' => self::body($body, $vars),
        ];
    }

    private static function common(?Dossier $dossier, $client): array
    {
        $procedure = $dossier?->procedure;
        $label = match ($procedure) {
            Procedure::DP => 'déclaration préalable de travaux',
            Procedure::PC, Procedure::PCArchi => 'permis de construire',
            Procedure::PA => "permis d'aménager",
            default => 'demande d\'autorisation d\'urbanisme',
        };
        $missingBirth = $client && (! $client->birth_date || ! $client->birth_place);

        return [
            '{procedure}' => $label,
            '{Procedure}' => mb_strtoupper(mb_substr($label, 0, 1)).mb_substr($label, 1),
            '{sigle}' => match ($procedure) {
                Procedure::DP => 'DP', Procedure::PC, Procedure::PCArchi => 'PC', Procedure::PA => 'PA', default => '',
            },
            '{nature}' => $dossier?->nature && $dossier->nature !== 'autre'
                ? (DossierResource::NATURES[$dossier->nature] ?? $dossier->nature) : '',
            '{projet}' => $dossier?->title ?? '',
            '{client}' => $client?->full_name ?? '',
            '{infos_cerfa}' => $missingBirth && $dossier
                ? "Par ailleurs, afin de compléter le formulaire CERFA pour l'administration, pourriez-vous, s'il vous plaît, me communiquer les informations suivantes :\n- Votre date et lieu de naissance"
                : '',
        ];
    }

    private static function paymentBlock(CompanySetting $s, string $number): string
    {
        $lines = [];
        if ($s->iban) {
            $lines[] = '- par virement : IBAN '.$s->iban.($s->bic ? ' (BIC '.$s->bic.')' : '').", en indiquant la référence {$number} ;";
        }
        if ($s->cheque_order) {
            $lines[] = "- par chèque à l'ordre de ".$s->cheque_order.($s->cheque_address ? ', à envoyer à : '.str_replace("\n", ', ', $s->cheque_address) : '').'.';
        }

        return $lines ? "Règlement possible :\n".implode("\n", $lines) : '';
    }

    /** Remplace les variables ; les segments vides (« - - », espaces doubles) disparaissent. */
    private static function subject(string $template, array $vars, string $fallback): string
    {
        $text = strtr($template, $vars);
        $parts = array_filter(array_map('trim', explode(' - ', $text)), fn ($p) => $p !== '');
        $text = trim(preg_replace('/\s{2,}/u', ' ', implode(' - ', $parts)));

        return $text !== '' ? $text : $fallback;
    }

    private static function body(string $template, array $vars): string
    {
        $text = strtr(str_replace("\r\n", "\n", $template), $vars);

        return trim(preg_replace("/\n{3,}/", "\n\n", preg_replace('/[ \t]+\n/', "\n", $text)));
    }
}
