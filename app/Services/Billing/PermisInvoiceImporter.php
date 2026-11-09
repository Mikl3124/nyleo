<?php

namespace App\Services\Billing;

use App\Models\ExternalBillingAccount;
use App\Models\ExternalInvoice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** Imports accounting copies only. Never issues an invoice, creates a payment or sends mail. */
class PermisInvoiceImporter
{
    public function import(array $batch): int
    {
        Validator::make($batch, [
            'schema' => ['required', 'integer', Rule::in([1])],
            'source' => ['required', Rule::in(['permis-construire.com'])],
            'exported_at' => ['required', 'date'],
            'invoices' => ['present', 'array', 'max:50000'],
            'invoices.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'invoices.*.number' => ['required', 'string', 'max:255', 'distinct'],
            'invoices.*.client_name' => ['required', 'string', 'max:255'],
            'invoices.*.issue_date' => ['required', 'date_format:Y-m-d'],
            'invoices.*.currency' => ['required', 'regex:/^[A-Z]{3}$/'],
            'invoices.*.total_minor' => ['required', 'integer', 'min:0'],
            'invoices.*.paid_minor' => ['required', 'integer', 'min:0'],
            'invoices.*.vat_minor' => ['required', 'integer', 'min:0'],
            'invoices.*.status' => ['required', 'string', 'max:50'],
            'invoices.*.type' => ['required', 'string', 'max:50'],
            'invoices.*.deleted' => ['required', 'boolean'],
            'invoices.*.issuer' => ['required', 'array'],
            'invoices.*.issuer.company_siret' => ['required', 'regex:/^[0-9]{14}$/'],
            'invoices.*.issuer.company_name' => ['required', 'string'],
            'invoices.*.stripe_fees' => ['sometimes', 'array'],
            'invoices.*.stripe_fees.status' => ['required_with:invoices.*.stripe_fees', Rule::in(['verified', 'pending', 'none'])],
            'invoices.*.stripe_fees.fee_minor' => ['nullable', 'integer', 'min:0'],
            'invoices.*.stripe_fees.net_minor' => ['nullable', 'integer'],
            'stripe_account' => ['nullable', 'array'],
            'stripe_account.id' => ['required_with:stripe_account', 'regex:/^acct_[A-Za-z0-9]+$/'],
            'stripe_account.checked_at' => ['required_with:stripe_account', 'date'],
            'stripe_account.available' => ['present_with:stripe_account', 'array'],
            'stripe_account.pending' => ['present_with:stripe_account', 'array'],
            'stripe_account.transactions' => ['present_with:stripe_account', 'array', 'max:2000'],
            'stripe_account.payouts' => ['present_with:stripe_account', 'array', 'max:100'],
            'stripe_account.available.*.amount_minor' => ['required', 'integer'],
            'stripe_account.available.*.currency' => ['required', 'regex:/^[A-Z]{3}$/'],
            'stripe_account.pending.*.amount_minor' => ['required', 'integer'],
            'stripe_account.pending.*.currency' => ['required', 'regex:/^[A-Z]{3}$/'],
            'stripe_account.transactions.*.id' => ['required', 'string', 'distinct'],
            'stripe_account.transactions.*.amount_minor' => ['required', 'integer'],
            'stripe_account.transactions.*.fee_minor' => ['required', 'integer'],
            'stripe_account.transactions.*.net_minor' => ['required', 'integer'],
            'stripe_account.transactions.*.currency' => ['required', 'regex:/^[A-Z]{3}$/'],
            'stripe_account.transactions.*.type' => ['required', 'string'],
            'stripe_account.transactions.*.created' => ['required', 'integer'],
            'stripe_account.payouts.*.amount_minor' => ['required', 'integer'],
            'stripe_account.payouts.*.currency' => ['required', 'regex:/^[A-Z]{3}$/'],
            'stripe_account.payouts.*.status' => ['required', 'string'],
            'stripe_account.payouts.*.arrival_date' => ['required', 'integer'],
        ])->validate();
        $exported = CarbonImmutable::parse($batch['exported_at'])->utc();
        if ($exported->isAfter(now()->addMinutes(5))) {
            throw new \InvalidArgumentException('Export daté dans le futur.');
        }

        return DB::transaction(function () use ($batch, $exported) {
            $changed = 0;
            foreach ($batch['invoices'] as $invoice) {
                if ($invoice['vat_minor'] > $invoice['total_minor']) {
                    throw new \InvalidArgumentException('TVA supérieure au total.');
                }
                $record = ExternalInvoice::where('source', $batch['source'])->where('source_id', $invoice['id'])->lockForUpdate()->first();
                if ($record && CarbonImmutable::parse($record->source_exported_at)->gt($exported)) {
                    continue;
                }
                if ($record && $record->number !== $invoice['number']) {
                    throw new \LogicException('Le numéro de la facture source a changé.');
                }
                $fees = $invoice['stripe_fees'] ?? null;
                if ($fees && in_array($fees['status'], ['verified', 'none'])) {
                    if (! isset($fees['fee_minor'],$fees['net_minor']) || $fees['net_minor'] !== $invoice['paid_minor'] - $fees['fee_minor']) {
                        throw new \InvalidArgumentException('Frais Stripe incohérents.');
                    }
                }
                $hash = hash('sha256', json_encode($invoice, JSON_THROW_ON_ERROR));
                if ($record && CarbonImmutable::parse($record->source_exported_at)->eq($exported) && $record->content_hash !== $hash) {
                    throw new \LogicException('Deux exports différents portent la même date.');
                }
                if (! $record || $record->content_hash !== $hash) {
                    $changed++;
                }
                $record ??= new ExternalInvoice(['source' => $batch['source'], 'source_id' => $invoice['id']]);
                $record->fill(['number' => $invoice['number'], 'payload' => $invoice, 'content_hash' => $hash, 'source_exported_at' => $exported->toIso8601String(), 'synced_at' => now()])->save();
            }

            if (! empty($batch['stripe_account'])) {
                foreach ($batch['stripe_account']['transactions'] as $t) {
                    if ($t['net_minor'] !== $t['amount_minor'] - $t['fee_minor']) {
                        throw new \InvalidArgumentException('Mouvement Stripe incohérent.');
                    }
                }
                $account = ExternalBillingAccount::where('source', $batch['source'])->lockForUpdate()->first();
                if (! $account || CarbonImmutable::parse($account->source_exported_at)->lte($exported)) {
                    $account ??= new ExternalBillingAccount(['source' => $batch['source']]);
                    $account->fill(['payload' => $batch['stripe_account'], 'source_exported_at' => $exported->toIso8601String()])->save();
                }
            }

            return $changed;
        });
    }
}
