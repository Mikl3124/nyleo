<?php

namespace App\Services\Billing;

use App\Models\ExternalInvoice;
use Illuminate\Database\Eloquent\Builder;

/** Read the existing accounting copies; never create native invoices or payments. */
class ExternalInvoiceCatalog
{
    public static function issuedInvoices(): Builder
    {
        return ExternalInvoice::query()
            ->where('source', 'permis-construire.com')
            ->where('payload->issuer->company_siret', '82503053900035')
            ->where('payload->deleted', false)
            ->whereIn('payload->type', ['invoice', 'deposit'])
            ->where('payload->status', 'paid')
            ->whereNull('payload->cancellation_credit_id')
            ->whereNull('payload->credited_invoice_id');
    }

    public static function url(ExternalInvoice $invoice, bool $pdf = false): string
    {
        return 'https://permis-construire.com/admin/invoices/'.(int) $invoice->source_id.($pdf ? '/pdf' : '');
    }
}
