<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\ExternalBillingAccount;
use App\Models\ExternalInvoice;
use App\Models\Invoice;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class BillingOverview extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Facturation';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Suivi des deux sites';

    protected static ?string $title = 'Facturation · Nyleo et Permis-construire';

    protected string $view = 'filament.pages.billing-overview';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportInvoices')->label('Exporter les factures (CSV)')->action(fn () => $this->exportInvoices()),
            Action::make('exportStripe')->label('Exporter les mouvements Stripe')->color('gray')->action(fn () => $this->exportStripe()),
        ];
    }

    public function exportInvoices(): StreamedResponse
    {
        $rows = $this->invoices()->where('issuer', '82503053900035')->map(fn ($r) => [
            $r['date'], $r['source'], $r['number'], $r['client'], $r['currency'],
            number_format($r['total'] / 100, 2, '.', ''), number_format($r['paid'] / 100, 2, '.', ''),
            $r['fees'] === null ? '' : number_format($r['fees'] / 100, 2, '.', ''),
            $r['net'] === null ? '' : number_format($r['net'] / 100, 2, '.', ''), $r['status'],
        ])->all();

        return $this->csv('factures-deux-sites.csv', ['Date', 'Site', 'Facture', 'Client', 'Devise', 'Facturé', 'Réglé brut', 'Frais paiement Stripe', 'Net paiement', 'État'], $rows);
    }

    public function exportStripe(): StreamedResponse
    {
        $account = ExternalBillingAccount::where('source', 'permis-construire.com')->first()?->payload;
        $rows = collect($account['transactions'] ?? [])->map(fn ($r) => [
            gmdate('Y-m-d H:i:s', $r['created']), $r['id'], $r['type'], $r['description'] ?? '', $r['currency'],
            number_format($r['amount_minor'] / 100, 2, '.', ''), number_format($r['fee_minor'] / 100, 2, '.', ''), number_format($r['net_minor'] / 100, 2, '.', ''),
        ])->all();

        return $this->csv('mouvements-stripe.csv', ['Date UTC', 'Référence', 'Type', 'Description', 'Devise', 'Montant', 'Frais', 'Net'], $rows);
    }

    private function csv(string $name, array $header, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header, ';', '"', '');
            foreach ($rows as $row) {
                $safe = array_map(fn ($v) => is_string($v) && ! is_numeric($v) && preg_match('/^[\s]*[=+\-@]|^[\t\r]/u', $v) ? "'".$v : $v, $row);
                fputcsv($out, $safe, ';', '"', '');
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function invoices(): Collection
    {
        $native = Invoice::with(['client', 'payments'])->whereNotNull('number')->get()->map(fn ($i) => [
            'source' => 'Nyleo', 'number' => $i->number, 'client' => $i->client_snapshot['display_name'] ?? $i->client?->display_name ?? '—',
            'date' => $i->issue_date?->format('Y-m-d'), 'issuer' => $i->company_snapshot['siret'] ?? 'Non renseigné',
            'currency' => 'EUR', 'total' => (int) round((float) $i->amount_due * 100) * ($i->isCreditNote() ? -1 : 1),
            'paid' => (int) round((float) $i->amount_paid * 100) * ($i->isCreditNote() ? -1 : 1),
            'status' => $i->status->label(), 'counted' => ! $i->isDraft(),
            'url' => InvoiceResource::getUrl('edit', ['record' => $i]),
            'correction' => false, 'active' => ! $i->isDraft(),
            'fees' => $i->payments->contains('method', 'carte') ? null : 0,
            'net' => $i->payments->contains('method', 'carte') ? null : (int) round((float) $i->amount_paid * 100) * ($i->isCreditNote() ? -1 : 1),
        ]);
        $external = ExternalInvoice::all()->map(function ($r) {
            $p = $r->payload;

            return ['source' => 'Permis-construire', 'number' => $p['number'], 'client' => $p['client_name'], 'date' => $p['issue_date'],
                'issuer' => $p['issuer']['company_siret'], 'currency' => $p['currency'], 'total' => $p['total_minor'] * ($p['type'] === 'credit_note' ? -1 : 1),
                'paid' => $p['paid_minor'] * ($p['type'] === 'credit_note' ? -1 : 1),
                'status' => $p['deleted'] ? 'Archivée — à contrôler' : (['paid' => 'Payée', 'draft' => 'Brouillon', 'sent' => 'En attente', 'viewed' => 'Consultée', 'cancelled' => 'Annulée', 'issued' => 'Avoir d’annulation', 'pending' => 'En attente', 'partial' => 'Payée en partie'][$p['status']] ?? $p['status']),
                'counted' => ! $p['deleted'] && $p['type'] !== 'proforma' && (!empty($p['cancellation_credit_id']) || !empty($p['credited_invoice_id']) || !in_array($p['status'], ['draft', 'void', 'cancelled'])),
                'active' => !$p['deleted'] && $p['type'] !== 'proforma' && empty($p['cancellation_credit_id']) && empty($p['credited_invoice_id']) && !in_array($p['status'], ['draft','void','cancelled']),
                'url' => 'https://permis-construire.com/admin/invoices/'.(int) $p['id'],
                'correction' => ! empty($p['issuer_correction']),
                'fees' => $p['stripe_fees']['fee_minor'] ?? ($p['paid_minor'] === 0 ? 0 : null),
                'net' => $p['stripe_fees']['net_minor'] ?? ($p['paid_minor'] === 0 ? 0 : null),
            ];
        });

        return $native->concat($external)->sortByDesc('date')->values();
    }
}
