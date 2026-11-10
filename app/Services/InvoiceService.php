<?php

namespace App\Services;

use App\Enums\DossierStatus;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\CompanySetting;
use App\Models\Dossier;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quote;
use App\Notifications\AdminAlert;
use App\Notifications\InvoiceReminder;
use App\Notifications\InvoiceSent;
use App\Notifications\PaymentReceived;
use App\Support\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class InvoiceService
{
    public static function createDepositFromQuote(Quote $quote, ?float $percent = null): Invoice
    {
        $percent ??= (float) $quote->deposit_percent;
        $amount = round((float) $quote->total_ttc * $percent / 100, 2);

        $invoice = Invoice::create([
            'type' => InvoiceType::Acompte,
            'status' => InvoiceStatus::Brouillon,
            'client_id' => $quote->client_id,
            'dossier_id' => $quote->dossier_id,
            'quote_id' => $quote->id,
            'subject' => "Acompte de {$percent} % sur le devis {$quote->number}",
        ]);
        $invoice->lines()->create([
            'label' => "Acompte {$percent} % — devis {$quote->number}",
            'description' => $quote->subject,
            'quantity' => 1,
            'unit_price' => $amount,
            'vat_rate' => 0,
            'position' => 1,
        ]);
        $invoice->recalculate();

        return $invoice;
    }

    /** Facture finale reprenant les lignes du devis, acomptes déjà facturés déduits. */
    public static function createFinalFromQuote(Quote $quote): Invoice
    {
        $existing = $quote->invoices()->where('type', InvoiceType::Facture->value)
            ->where('status', '!=', InvoiceStatus::Annulee->value)->first();
        if ($existing) {
            return $existing;
        }

        $deposits = (float) $quote->invoices()->where('type', InvoiceType::Acompte->value)
            ->whereNotIn('status', [InvoiceStatus::Brouillon->value, InvoiceStatus::Annulee->value])->sum('total_ttc');

        $invoice = Invoice::create([
            'type' => InvoiceType::Facture,
            'status' => InvoiceStatus::Brouillon,
            'client_id' => $quote->client_id,
            'dossier_id' => $quote->dossier_id,
            'quote_id' => $quote->id,
            'subject' => $quote->subject,
            'deposits_deducted' => $deposits,
        ]);
        foreach ($quote->lines as $line) {
            $invoice->lines()->create($line->only(['label', 'description', 'quantity', 'unit', 'unit_price', 'vat_rate', 'position']));
        }
        $invoice->recalculate();

        return $invoice;
    }

    /** Émet la facture : numéro définitif, données figées, PDF archivé. Elle n'est plus modifiable. */
    public static function issue(Invoice $invoice, bool $notify = true): Invoice
    {
        if (! $invoice->isDraft()) {
            throw new RuntimeException('Cette facture est déjà émise.');
        }
        if ($invoice->lines()->count() === 0) {
            throw new RuntimeException('La facture ne contient aucune ligne.');
        }

        DB::transaction(function () use ($invoice) {
            $settings = CompanySetting::current();
            $invoice->recalculate();
            $invoice->forceFill([
                'number' => Numbering::next($invoice->isCreditNote() ? 'credit_note' : 'invoice'),
                'issue_date' => $invoice->issue_date ?? now(),
                'due_date' => $invoice->due_date ?? now()->addDays($settings->payment_terms_days),
                'client_snapshot' => $invoice->client->snapshot(),
                'company_snapshot' => $settings->snapshot(),
                'status' => $invoice->isCreditNote() ? InvoiceStatus::Payee : InvoiceStatus::Emise,
                'issued_at' => now(),
            ])->save();

            if ($invoice->isCreditNote() && $invoice->parent) {
                $parent = $invoice->parent;
                $credited = (float) $parent->creditNotes()->whereNotNull('number')->sum('total_ttc');
                if ($credited >= (float) $parent->total_ttc) {
                    $parent->forceFill(['status' => InvoiceStatus::Annulee])->save();
                } else {
                    $parent->forceFill(['amount_due' => max(0, (float) $parent->total_ttc - (float) $parent->deposits_deducted - $credited)])->save();
                    self::refreshPaymentStatus($parent);
                }
            }

            PdfService::storeInvoice($invoice);
            $invoice->dossier?->log('facture_emise', "{$invoice->type->label()} {$invoice->number} émise (".money($invoice->total_ttc).')', true);
        });

        if ($notify && $invoice->client->email) {
            $invoice->client->notify(new InvoiceSent($invoice));
            $invoice->forceFill(['sent_at' => now()])->saveQuietly();
        }

        return $invoice;
    }

    public static function addPayment(Invoice $invoice, float $amount, string $method, $paidAt = null, ?string $reference = null, bool $notify = true): Payment
    {
        if ($invoice->isDraft() || $invoice->isCreditNote()) {
            throw new RuntimeException('Un paiement ne peut être enregistré que sur une facture émise.');
        }

        $payment = $invoice->payments()->create([
            'amount' => $amount,
            'method' => $method,
            'paid_at' => $paidAt ?? now(),
            'reference' => $reference,
        ]);

        self::refreshPaymentStatus($invoice);
        $invoice->dossier?->log('paiement', 'Paiement reçu : '.money($amount).' par '.(Payment::METHODS[$method] ?? $method), true);

        if ($notify && $invoice->client->email) {
            $invoice->client->notify(new PaymentReceived($invoice, $payment));
        }

        return $payment;
    }

    public static function refreshPaymentStatus(Invoice $invoice): void
    {
        $paid = (float) $invoice->payments()->sum('amount');
        $status = $invoice->status;
        if ($status !== InvoiceStatus::Annulee) {
            $status = $paid <= 0 ? InvoiceStatus::Emise
                : ($paid + 0.009 >= (float) $invoice->amount_due ? InvoiceStatus::Payee : InvoiceStatus::PartiellementPayee);
        }
        $invoice->forceFill([
            'amount_paid' => $paid,
            'status' => $status,
            'paid_at' => $status === InvoiceStatus::Payee ? ($invoice->paid_at ?? now()) : null,
        ])->save();

        if ($status === InvoiceStatus::Payee && $invoice->type === InvoiceType::Facture && $invoice->dossier?->status === DossierStatus::PlansValides) {
            Admin::notify(new AdminAlert(
                "Facture {$invoice->number} réglée",
                "Le dossier {$invoice->dossier->reference} est payé : tu peux le déposer en mairie.",
                route('filament.admin.resources.dossiers.view', $invoice->dossier),
            ));
        }
    }

    /** Avoir total (ou partiel si $amount) annulant une facture émise. */
    public static function createCreditNote(Invoice $invoice, ?float $amount = null, ?string $reason = null): Invoice
    {
        if ($invoice->isDraft() || $invoice->isCreditNote()) {
            throw new RuntimeException('Un avoir ne peut être créé que sur une facture émise.');
        }

        $credit = Invoice::create([
            'type' => InvoiceType::Avoir,
            'status' => InvoiceStatus::Brouillon,
            'client_id' => $invoice->client_id,
            'dossier_id' => $invoice->dossier_id,
            'quote_id' => $invoice->quote_id,
            'parent_invoice_id' => $invoice->id,
            'subject' => "Avoir sur la facture {$invoice->number}".($reason ? " — {$reason}" : ''),
        ]);

        if ($amount === null) {
            foreach ($invoice->lines as $line) {
                $credit->lines()->create($line->only(['label', 'description', 'quantity', 'unit', 'unit_price', 'vat_rate', 'position']));
            }
        } else {
            $credit->lines()->create([
                'label' => "Avoir sur la facture {$invoice->number}",
                'description' => $reason,
                'quantity' => 1,
                'unit_price' => $amount,
                'vat_rate' => 0,
                'position' => 1,
            ]);
        }
        $credit->recalculate();

        return $credit;
    }

    /**
     * Supprime définitivement une facture (même émise), avec ses paiements, ses lignes,
     * ses avoirs et son PDF archivé. Réservé à la phase de test : en production, une
     * facture émise s'annule par un avoir. Si c'était le dernier numéro attribué, le
     * compteur recule pour que la numérotation reprenne sans trou.
     */
    public static function delete(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            foreach ($invoice->creditNotes()->withTrashed()->get() as $credit) {
                self::delete($credit);
            }

            $parent = $invoice->parent;
            $label = $invoice->title();
            $number = $invoice->number;
            $kind = $invoice->isCreditNote() ? 'credit_note' : 'invoice';

            $invoice->payments()->delete();
            $invoice->lines()->delete();
            if ($invoice->pdf_path) {
                Storage::disk('local')->delete($invoice->pdf_path);
            }
            // Contourne le garde-fou du modèle (facture émise non supprimable) : suppression voulue.
            Invoice::withoutEvents(fn () => $invoice->forceDelete());

            if ($number) {
                Numbering::release($kind, $number);
            }

            // Un avoir supprimé : la facture d'origine redevient due.
            if ($parent && $number) {
                $credited = (float) $parent->creditNotes()->whereNotNull('number')->sum('total_ttc');
                $parent->forceFill([
                    'status' => InvoiceStatus::Emise,
                    'amount_due' => max(0, (float) $parent->total_ttc - (float) $parent->deposits_deducted - $credited),
                ])->save();
                if ($credited >= (float) $parent->total_ttc) {
                    $parent->forceFill(['status' => InvoiceStatus::Annulee])->save();
                } else {
                    self::refreshPaymentStatus($parent);
                }
            }

            $invoice->dossier?->log('facture_supprimee', "{$label} supprimée");
            DossierWorkflow::syncFromDocuments($invoice->dossier);
        });
    }

    /** Relance automatique ou manuelle d'une facture impayée. */
    public static function remind(Invoice $invoice): void
    {
        $invoice->client->notify(new InvoiceReminder($invoice));
        $invoice->forceFill(['reminder_count' => $invoice->reminder_count + 1, 'last_reminder_at' => now()])->saveQuietly();
        $invoice->dossier?->log('relance', "Relance n°{$invoice->reminder_count} envoyée pour la facture {$invoice->number}");
    }

    /** Rattache la facture au dossier en cours du client, ou crée le dossier s'il n'y en a pas. */
    public static function ensureDossier(Invoice $invoice): void
    {
        if ($invoice->dossier_id || ! $invoice->client) {
            return;
        }
        $dossier = $invoice->client->dossierForNewInvoice();
        if (! $dossier) {
            $subject = $invoice->subject ?: 'Projet de '.$invoice->client->display_name;
            $dossier = Dossier::create([
                'client_id' => $invoice->client_id,
                'title' => $subject,
                'procedure' => str($subject)->lower()->contains(['permis', ' pc']) ? 'PC' : 'DP',
                'site_address' => $invoice->client->address,
                'site_postal_code' => $invoice->client->postal_code,
                'site_city' => $invoice->client->city,
            ]);
            $dossier->log('creation', 'Dossier créé avec la facture');
        }
        $invoice->forceFill(['dossier_id' => $dossier->id])->saveQuietly();
        $invoice->setRelation('dossier', $dossier);
        DossierWorkflow::syncFromDocuments($dossier);
    }
}
