<?php

namespace App\Services\Billing;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UnifiedInvoiceCatalog
{
    public function rows(?string $search = null, ?array $filters = null, ?string $sortColumn = null, ?string $sortDirection = null): Collection
    {
        $native = Invoice::with('client')->get()->map(fn ($i) => [
            'key' => 'nyleo-'.$i->id, 'id' => $i->id, 'source' => 'Nyleo', 'number' => $i->number ?: 'Brouillon',
            'client' => $i->client_snapshot['display_name'] ?? $i->client?->display_name ?? '—',
            'total' => (int) round($i->amount_due * 100) * ($i->isCreditNote() ? -1 : 1),
            'paid' => (int) round($i->amount_paid * 100), 'status' => $i->status->shortLabel(), 'color' => $i->status->color(),
            'date' => $i->issue_date?->format('Y-m-d'), 'draft' => $i->isDraft(),
            'url' => InvoiceResource::getUrl('edit', ['record' => $i]), 'pdf' => $i->isDraft() ? null : route('admin.invoices.pdf', $i),
            'currency' => 'EUR',
        ]);
        $external = ExternalInvoiceCatalog::issuedInvoices()->get()->map(fn ($i) => [
            'key' => 'permis-'.$i->source_id, 'id' => $i->source_id, 'source' => 'Permis-construire', 'number' => $i->number,
            'client' => $i->payload['client_name'], 'total' => $i->payload['total_minor'], 'paid' => $i->payload['paid_minor'],
            'status' => 'Payée', 'color' => 'success', 'date' => $i->payload['issue_date'], 'draft' => false,
            'url' => ExternalInvoiceCatalog::url($i), 'pdf' => ExternalInvoiceCatalog::url($i, true), 'currency' => $i->payload['currency'],
        ]);
        $rows = $native->concat($external);
        if (filled($search)) {
            $needle = Str::lower(Str::ascii(trim($search)));
            $rows = $rows->filter(fn ($r) => str_contains(Str::lower(Str::ascii($r['number'].' '.$r['client'].' '.$r['source'])), $needle));
        }
        if ($source = $filters['source']['value'] ?? null) {
            $rows = $rows->where('source', $source);
        }
        if ($status = $filters['state']['value'] ?? null) {
            $rows = $rows->where('status', $status);
        }
        $sort = in_array($sortColumn, ['number', 'source', 'client', 'total', 'date']) ? $sortColumn : 'date';

        return ($sortDirection === 'asc' ? $rows->sortBy($sort) : $rows->sortByDesc($sort))->keyBy('key');
    }
}
