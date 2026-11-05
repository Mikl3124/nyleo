<?php

namespace App\Filament\Widgets;

use App\Enums\DossierStatus;
use App\Enums\InvoiceStatus;
use App\Enums\QuoteStatus;
use App\Models\Dossier;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quote;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $month = Payment::whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount');
        $year = Payment::whereYear('paid_at', now()->year)->sum('amount');
        $unpaid = Invoice::whereIn('status', [InvoiceStatus::Emise->value, InvoiceStatus::PartiellementPayee->value])->get();
        $late = $unpaid->filter(fn ($i) => $i->due_date?->isPast())->count();
        $pendingQuotes = Quote::where('status', QuoteStatus::Envoye->value)->get();
        $open = Dossier::whereNotIn('status', [DossierStatus::Accorde->value, DossierStatus::Refuse->value, DossierStatus::Abandonne->value])->count();

        return [
            Stat::make('Encaissé ce mois', money($month))->description('Depuis janvier : '.money($year))->color('success'),
            Stat::make('À encaisser', money($unpaid->sum(fn ($i) => $i->remaining())))
                ->description($unpaid->count().' facture(s)'.($late ? ", dont {$late} en retard" : ''))
                ->color($late ? 'danger' : 'warning'),
            Stat::make('Devis en attente de signature', (string) $pendingQuotes->count())->description(money($pendingQuotes->sum('total_ttc')).' en jeu'),
            Stat::make('Dossiers en cours', (string) $open),
        ];
    }
}
