<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Tableau de bord : factures à encaisser, avec le bouton « Encaisser » pour pointer un virement ou un chèque en un clic. */
class UnpaidInvoices extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'À encaisser';

    public static function canView(): bool
    {
        return Invoice::whereIn('status', [InvoiceStatus::Emise->value, InvoiceStatus::PartiellementPayee->value])->where('type', '!=', 'avoir')->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Invoice::query()->with('client', 'dossier')
                ->whereIn('status', [InvoiceStatus::Emise->value, InvoiceStatus::PartiellementPayee->value])
                ->where('type', '!=', 'avoir'))
            ->defaultSort('due_date')
            ->paginated([5, 10, 25])
            ->recordUrl(fn (Invoice $r) => InvoiceResource::getUrl('edit', ['record' => $r]))
            ->emptyStateHeading('Tout est encaissé')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                TextColumn::make('number')->label('Facture')->weight('bold')
                    ->description(fn (Invoice $r) => $r->client?->display_name),
                TextColumn::make('remaining')->label('Reste à payer')->state(fn (Invoice $r) => money($r->remaining())),
                TextColumn::make('due_date')->label('Échéance')->date('d/m/Y')
                    ->color(fn (Invoice $r) => $r->due_date?->isPast() ? 'danger' : null)
                    ->description(fn (Invoice $r) => $r->due_date?->isPast() ? 'En retard' : null),
            ])
            ->recordActions([
                InvoiceResource::paymentAction(),
                Action::make('open')->label('Ouvrir')->icon(Heroicon::OutlinedArrowRight)->color('gray')
                    ->url(fn (Invoice $r) => InvoiceResource::getUrl('edit', ['record' => $r])),
            ]);
    }
}
