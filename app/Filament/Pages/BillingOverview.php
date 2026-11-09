<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\ExternalInvoice;
use App\Models\Invoice;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class BillingOverview extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Facturation';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Suivi des deux sites';

    protected static ?string $title = 'Facturation · Nyleo et Permis-construire';

    protected string $view = 'filament.pages.billing-overview';

    public function invoices(): Collection
    {
        $native = Invoice::with('client')->whereNotNull('number')->get()->map(fn ($i) => [
            'source' => 'Nyleo', 'number' => $i->number, 'client' => $i->client_snapshot['display_name'] ?? $i->client?->display_name ?? '—',
            'date' => $i->issue_date?->format('Y-m-d'), 'issuer' => $i->company_snapshot['siret'] ?? 'Non renseigné',
            'currency' => 'EUR', 'total' => (int) round((float) $i->amount_due * 100) * ($i->isCreditNote() ? -1 : 1),
            'paid' => (int) round((float) $i->amount_paid * 100) * ($i->isCreditNote() ? -1 : 1),
            'status' => $i->status->label(), 'counted' => ! $i->isDraft(),
            'url' => InvoiceResource::getUrl('edit', ['record' => $i]),
            'correction' => false,
        ]);
        $external = ExternalInvoice::all()->map(function ($r) {
            $p = $r->payload;

            return ['source' => 'Permis-construire', 'number' => $p['number'], 'client' => $p['client_name'], 'date' => $p['issue_date'],
                'issuer' => $p['issuer']['company_siret'], 'currency' => $p['currency'], 'total' => $p['total_minor'] * ($p['type'] === 'credit_note' ? -1 : 1),
                'paid' => $p['paid_minor'] * ($p['type'] === 'credit_note' ? -1 : 1),
                'status' => $p['deleted'] ? 'Archivée — à contrôler' : (['paid' => 'Payée', 'draft' => 'Brouillon', 'sent' => 'En attente', 'viewed' => 'Consultée', 'cancelled' => 'Annulée', 'pending' => 'En attente', 'partial' => 'Payée en partie'][$p['status']] ?? $p['status']),
                'counted' => ! $p['deleted'] && ! in_array($p['status'], ['draft', 'void']) && $p['type'] !== 'proforma',
                'url' => 'https://permis-construire.com/admin/invoices/'.(int) $p['id'],
                'correction' => ! empty($p['issuer_correction']),
            ];
        });

        return $native->concat($external)->sortByDesc('date')->values();
    }
}
