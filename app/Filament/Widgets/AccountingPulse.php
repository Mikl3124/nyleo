<?php

namespace App\Filament\Widgets;

use App\Services\Billing\ReceiptLedger;
use App\Services\Billing\UnifiedInvoiceCatalog;
use Filament\Widgets\Widget;

class AccountingPulse extends Widget
{
    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.accounting-pulse';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $ledger = app(ReceiptLedger::class);
        $ledger->sync();
        $period = now('Europe/Paris')->subMonthNoOverflow()->format('Y-m');
        $rows = app(UnifiedInvoiceCatalog::class)->rows()->filter(fn ($r) => ! $r['draft'] && $r['status'] !== 'Annulée');

        return ['period' => $period, 'preparation' => $ledger->period($period), 'factured' => $rows->sum('total'), 'invoiceCount' => $rows->count()];
    }
}
