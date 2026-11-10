<?php

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Models\CompanySetting;
use App\Models\Quote;
use App\Notifications\AdminAlert;
use App\Notifications\QuoteSent;
use App\Support\Admin;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QuoteService
{
    /** Attribue un numéro, fige les infos client/entreprise et envoie le lien de signature. */
    public static function send(Quote $quote, bool $notify = true): Quote
    {
        if (! $quote->client->email && $notify) {
            throw new RuntimeException("Le client n'a pas d'adresse e-mail.");
        }

        DB::transaction(function () use ($quote) {
            $settings = CompanySetting::current();
            $quote->recalculate();
            if (! $quote->number) {
                $quote->number = Numbering::next('quote');
            }
            $quote->issue_date ??= now();
            $quote->valid_until ??= now()->addDays($settings->quote_validity_days);
            $quote->client_snapshot = $quote->client->snapshot();
            $quote->company_snapshot = $settings->snapshot();
            $quote->status = QuoteStatus::Envoye;
            $quote->sent_at = now();
            $quote->save();

            $quote->dossier?->log('devis_envoye', "Devis {$quote->number} envoyé", true);
        });

        if ($notify) {
            $quote->client->notify(new QuoteSent($quote));
        }

        return $quote;
    }

    /** Signature simple par le client depuis son espace (nom saisi + case « bon pour accord »). */
    public static function sign(Quote $quote, string $signerName, ?string $ip, ?string $userAgent, bool $earlyStart = false, bool $termsAccepted = false): Quote
    {
        if (! $quote->isSignable()) {
            throw new RuntimeException('Ce devis ne peut plus être signé.');
        }

        DB::transaction(function () use ($quote, $signerName, $ip, $userAgent, $earlyStart, $termsAccepted) {
            $signedAt = now();
            $quote->forceFill([
                'status' => QuoteStatus::Signe,
                'signed_at' => $signedAt,
                'signer_name' => $signerName,
                'early_start_requested' => $earlyStart,
                'terms_accepted_at' => $termsAccepted ? $signedAt : null,
                'signer_ip' => $ip,
                'signer_user_agent' => $userAgent,
                'signature_hash' => hash('sha256', implode('|', [
                    $quote->number, $quote->total_ttc, $signerName, $signedAt->toIso8601String(), $ip,
                    $quote->lines->map(fn ($l) => $l->label.':'.$l->quantity.':'.$l->unit_price)->implode(';'),
                ])),
            ])->save();

            PdfService::storeSignedQuote($quote);

            if ($dossier = $quote->dossier) {
                $dossier->log('devis_signe', "Devis {$quote->number} signé en ligne par {$signerName}", true);
                $dossier->log('demarrage_anticipe', $earlyStart
                    ? 'Le client demande de commencer sans attendre la fin du délai de rétractation de 14 jours'
                    : 'Le client n\'a pas demandé de commencer avant la fin du délai de rétractation de 14 jours');
            }

            if ((float) $quote->deposit_percent > 0) {
                $deposit = InvoiceService::createDepositFromQuote($quote);
                InvoiceService::issue($deposit, notify: true);
            }
        });

        Admin::notify(new AdminAlert(
            "Devis {$quote->number} signé",
            "{$signerName} vient de signer le devis {$quote->number} (".money($quote->total_ttc).'). '
                .($earlyStart ? 'Il demande de commencer tout de suite.' : 'Il n\'a pas demandé de commencer avant la fin du délai de rétractation de 14 jours.'),
            $quote->dossier ? route('filament.admin.resources.dossiers.view', $quote->dossier) : null,
        ));

        return $quote;
    }

    public static function refuse(Quote $quote, ?string $reason): void
    {
        $quote->forceFill(['status' => QuoteStatus::Refuse, 'refused_at' => now(), 'refusal_reason' => $reason])->save();
        $quote->dossier?->log('devis_refuse', "Devis {$quote->number} refusé".($reason ? " : {$reason}" : ''));
        Admin::notify(new AdminAlert("Devis {$quote->number} refusé", $reason ?: 'Sans motif.'));
    }

    public static function duplicate(Quote $quote): Quote
    {
        $copy = $quote->replicate([
            'number', 'status', 'issue_date', 'valid_until', 'sent_at', 'viewed_at', 'signed_at', 'signer_name', 'early_start_requested', 'terms_accepted_at',
            'signer_ip', 'signer_user_agent', 'signature_hash', 'signed_pdf_path', 'refused_at', 'refusal_reason',
            'reminder_count', 'last_reminder_at', 'client_snapshot', 'company_snapshot',
        ]);
        $copy->status = QuoteStatus::Brouillon;
        $copy->save();
        foreach ($quote->lines as $line) {
            $copy->lines()->create($line->only(['label', 'description', 'quantity', 'unit', 'unit_price', 'vat_rate', 'position']));
        }
        $copy->recalculate();

        return $copy;
    }
}
