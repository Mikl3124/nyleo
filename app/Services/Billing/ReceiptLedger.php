<?php

namespace App\Services\Billing;

use App\Models\ExternalBillingAccount;
use App\Models\ExternalInvoice;
use App\Models\Payment;
use App\Models\ReceiptEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ReceiptLedger
{
    public function alert(string $key, string $message): void
    {
        DB::table('accounting_alerts')->updateOrInsert(['key' => $key], ['message' => $message, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function record(array $row): ReceiptEntry
    {
        $row['currency'] ??= 'EUR';
        $row['vat_minor'] ??= 0;
        $row['category'] ??= null;
        $row['reference'] ??= null;
        $hash = hash('sha256', json_encode(collect($row)->except('evidence')->sortKeys()->all(), JSON_THROW_ON_ERROR));
        $existing = ReceiptEntry::where('source_key', $row['source_key'])->first();
        if ($existing) {
            if ($existing->content_hash !== $hash) {
                $this->alert('changed:'.$row['source_key'], 'Une recette source a changé : '.$row['document'].'. Écriture d’origine conservée ; rapprochement nécessaire.');
            }

return $existing;
        }

        return ReceiptEntry::firstOrCreate(['source_key' => $row['source_key']], $row + ['content_hash' => $hash]);
    }

    public function external(array $documents): void
    {
        foreach ($documents as $p) {
            if (($p['issuer']['company_siret'] ?? null) !== '82503053900035' || ($p['type'] ?? '') === 'credit_note' || ($p['paid_minor'] ?? 0) <= 0) {
                continue;
            }
            if (($p['currency'] ?? 'EUR') !== 'EUR') {
                $this->alert('currency:'.$p['id'], 'Devise à convertir pour '.$p['number']);

                continue;
            }
            if (($p['vat_minor'] ?? 0) > 0) {
                $this->alert('vat:'.$p['id'], 'TVA à contrôler pour '.$p['number'].' avant déclaration.');
            }
            $payments = collect($p['payments'] ?? [])->unique('payment_key');
            $sum = $payments->sum('amount_minor');
            foreach ($payments as $payment) {
                $this->externalRow($p, 'permis:payment:'.hash('sha256', $payment['payment_key']), $payment['paid_at'], $payment['amount_minor'], $payment['method'] ?? 'Non renseigné', $payment['reference'] ?? null);
            }
            $residual = $p['paid_minor'] - $sum;
            if ($residual > 0) {
                if (empty($p['paid_date'])) {
                    $this->alert('date:'.$p['id'], 'Date d’encaissement manquante pour '.$p['number']);

                    continue;
                }
                $this->externalRow($p, 'permis:invoice:'.$p['id'].':legacy', $p['paid_date'], $residual, ! empty($p['stripe_payment_intent_id']) ? 'Stripe' : 'Non renseigné', $p['stripe_payment_intent_id'] ?? $p['payment_reference'] ?? null);
            } elseif ($residual < 0) {
                $this->alert('amount:'.$p['id'], 'Les règlements détaillés dépassent le total encaissé de '.$p['number']);
            }
        }
    }

    private function externalRow(array $p, string $key, string $date, int $amount, string $method, ?string $reference): void
    {
        if ($amount <= 0) {
            return;
        }
        $this->record(['source_key' => $key, 'source' => 'Permis-construire', 'received_on' => CarbonImmutable::parse($date, 'Europe/Paris')->format('Y-m-d'), 'customer' => $p['client_name'], 'document' => $p['payment_request_reference'] ?? $p['number'], 'method' => $method, 'reference' => $reference, 'gross_minor' => $amount, 'vat_minor' => ($p['vat_minor'] ?? 0) > 0 && ($p['total_minor'] ?? 0) > 0 ? (int) round($amount * $p['vat_minor'] / $p['total_minor']) : 0, 'evidence' => ['invoice_id' => $p['id'], 'invoice_number' => $p['number'], 'source' => 'permis-construire.com']]);
    }

    public function sync(): void
    {
        $this->external(ExternalInvoice::all()->pluck('payload')->all());
        foreach (ReceiptEntry::where('source', 'Nyleo')->get() as $record) {
            if (! Payment::whereKey($record->evidence['payment_id'] ?? 0)->exists()) {
                $this->alert('missing:'.$record->source_key, 'Règlement source introuvable : '.$record->document.'. Recette historique conservée.');
            }
        }
        foreach (Payment::with('invoice.client')->get() as $p) {
            $i = $p->invoice;
            if (! $i || $i->isCreditNote()) {
                continue;
            }
            if (($i->company_snapshot['siret'] ?? null) !== '82503053900035') {
                $this->alert('issuer:nyleo:'.$p->id, 'Émetteur à vérifier pour le règlement '.$p->id.' de '.$i->number);

                continue;
            }
            if ($i->total_vat > 0) {
                $this->alert('vat:nyleo:'.$p->id, 'TVA à contrôler pour '.$i->number.' avant déclaration.');
            }
            $this->record(['source_key' => 'nyleo:payment:'.$p->id, 'source' => 'Nyleo', 'received_on' => $p->paid_at->format('Y-m-d'), 'customer' => $i->client_snapshot['display_name'] ?? $i->client?->display_name ?? 'Client non renseigné', 'document' => $i->number, 'method' => Payment::METHODS[$p->method] ?? $p->method, 'reference' => $p->reference, 'gross_minor' => (int) round($p->amount * 100), 'vat_minor' => $i->total_ttc > 0 ? (int) round($p->amount * 100 * $i->total_vat / $i->total_ttc) : 0, 'evidence' => ['invoice_id' => $i->id, 'payment_id' => $p->id]]);
        }
    }

    public function period(string $month): array
    {
        $start = CarbonImmutable::createFromFormat('!Y-m', $month, 'Europe/Paris');
        $end = $start->endOfMonth();
        $rows = ReceiptEntry::whereBetween('received_on', [$start->toDateString(), $end->toDateString()])->orderBy('received_on')->orderBy('id')->get();
        $account = ExternalBillingAccount::where('source', 'permis-construire.com')->first()?->payload;
        $tx = collect($account['transactions'] ?? [])->filter(fn ($t) => $t['currency'] === 'EUR' && $t['created'] >= $start->timestamp && $t['created'] <= $end->timestamp);
        $fees = ! empty($account['transactions_complete']) ? $tx->whereIn('type', ['charge', 'payment'])->sum('fee_minor') - $tx->whereIn('type', ['stripe_fee', 'stripe_fx_fee', 'tax_fee'])->sum('net_minor') : null;
        $alerts = DB::table('accounting_alerts')->pluck('message')->all();
        if ($tx->contains(fn ($t) => in_array($t['type'], ['refund', 'payment_refund', 'adjustment', 'payment_failure_refund']))) {
            $alerts[] = 'Remboursement ou ajustement Stripe à rapprocher avant déclaration.';
        }
        if (! $account || CarbonImmutable::parse($account['checked_at'])->lt(now()->subMinutes(20))) {
            $alerts[] = 'Synchronisation Stripe à actualiser avant déclaration.';
        }

        return ['period' => $month, 'rows' => $rows, 'gross_minor' => $rows->sum('gross_minor'), 'vat_minor' => $rows->sum('vat_minor'), 'base_minor' => $rows->sum(fn ($r) => $r->gross_minor - $r->vat_minor), 'stripe_fees_minor' => $fees, 'alerts' => $alerts];
    }

    public function manual(array $data, string $token): ReceiptEntry
    {
        return $this->record(['source_key' => 'manual:'.$token, 'source' => 'Autres recettes', 'received_on' => $data['date'], 'customer' => $data['customer'], 'document' => $data['document'], 'method' => $data['method'], 'reference' => $data['reference'] ?? null, 'gross_minor' => (int) round((float) $data['amount'] * 100), 'category' => $data['category'] ?: null, 'evidence' => ['entered_by' => auth()->id(), 'note' => $data['note'] ?? null]]);
    }
}
