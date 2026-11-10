<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Services\Billing\UnifiedInvoiceCatalog;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nouvelle facture')];
    }

    public function table(Table $table): Table
    {
        return $table->heading('Toutes les factures')->description('Une seule liste pour Nyleo et Permis-construire.')
            ->records(function (?string $search, ?array $filters, ?string $sortColumn, ?string $sortDirection, int $page, int|string $recordsPerPage) {
                $rows = app(UnifiedInvoiceCatalog::class)->rows($search, $filters, $sortColumn, $sortDirection);
                $per = $recordsPerPage === 'all' ? max(1, $rows->count()) : (int) $recordsPerPage;

                return new LengthAwarePaginator($rows->forPage($page, $per), $rows->count(), $per, $page);
            })->columns([
                TextColumn::make('number')->label('N°')->searchable()->sortable(),
                TextColumn::make('source')->label('Site')->badge()->sortable(),
                TextColumn::make('client')->label('Client')->searchable()->sortable(),
                TextColumn::make('total')->label('Montant')->formatStateUsing(fn ($state, array $record) => number_format($state / 100, 2, ',', ' ').' '.$record['currency'])->sortable(),
                TextColumn::make('status')->label('Statut')->badge()->color(fn (array $record) => $record['color']),
                TextColumn::make('date')->label('Émise le')->date('d/m/Y')->sortable(),
            ])->filters([
                SelectFilter::make('source')->label('Site')->options(['Nyleo' => 'Nyleo', 'Permis-construire' => 'Permis-construire']),
                SelectFilter::make('state')->label('Statut')->options(['Payée' => 'Payée', 'En attente' => 'En attente', 'Payée en partie' => 'Payée en partie', 'Brouillon' => 'Brouillon', 'Annulée' => 'Annulée']),
            ])->recordAction(null)->recordUrl(fn (array $record) => $record['url'])->recordActions([
                Action::make('open')->label('Ouvrir')->url(fn (array $record) => $record['url']),
                Action::make('pdf')->label('PDF')->visible(fn (array $record) => filled($record['pdf']))->url(fn (array $record) => $record['pdf'])->openUrlInNewTab(),
            ])->toolbarActions([])->defaultSort('date', 'desc')->paginated([10, 25, 50]);
    }
}
