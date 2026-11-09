<?php

namespace App\Filament\Widgets;

use App\Enums\DossierStatus;
use App\Enums\InvoiceStatus;
use App\Enums\QuoteStatus;
use App\Filament\Pages\Accounting;
use App\Models\Dossier;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\ReceiptEntry;
use App\Services\Billing\ReceiptLedger;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        app(ReceiptLedger::class)->sync();
        $month = ReceiptEntry::whereBetween('received_on', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('gross_minor') / 100;
        $year = ReceiptEntry::whereYear('received_on', now()->year)->sum('gross_minor') / 100;
        $unpaid = Invoice::whereIn('status', [InvoiceStatus::Emise->value, InvoiceStatus::PartiellementPayee->value])->get();
        $late = $unpaid->filter(fn ($i) => $i->due_date?->isPast())->count();
        $pendingQuotes = Quote::where('status', QuoteStatus::Envoye->value)->get();
        $open = Dossier::whereNotIn('status', [DossierStatus::Accorde->value, DossierStatus::Refuse->value, DossierStatus::Abandonne->value])->count();

        return [
            Stat::make('Encaissé ce mois · toutes origines', money($month))->description('Depuis janvier : '.money($year).' · avant frais')->color('success')->url(Accounting::getUrl()),
            Stat::make('À encaisser', money($unpaid->sum(fn ($i) => $i->remaining())))
                ->description($unpaid->count().' facture(s)'.($late ? ", dont {$late} en retard" : ''))
                ->color($late ? 'danger' : 'warning'),
            Stat::make('Devis en attente de signature', (string) $pendingQuotes->count())->description(money($pendingQuotes->sum('total_ttc')).' en jeu'),
            Stat::make('Dossiers en cours', (string) $open),
        ];
    }
}
