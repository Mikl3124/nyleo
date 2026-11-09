<?php

namespace App\Filament\Widgets;

use App\Models\ExternalInvoice;
use App\Services\Billing\ExternalInvoiceCatalog;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PermisInvoices extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Factures · Permis-construire')
            ->description('Factures originales synchronisées toutes les 5 minutes. Ouvrez-les ou téléchargez leur PDF directement ici.')
            ->query(ExternalInvoiceCatalog::issuedInvoices())
            ->defaultSort('payload->issue_date', 'desc')
            ->columns([
                TextColumn::make('number')->label('N°')->searchable(),
                TextColumn::make('payload.client_name')->label('Client')->searchable(query: fn (Builder $query, string $search) => $query->where('payload->client_name', 'like', '%'.$search.'%')),
                TextColumn::make('payload.total_minor')->label('Montant')->formatStateUsing(fn ($state, ExternalInvoice $record) => number_format($state / 100, 2, ',', ' ').' '.$record->payload['currency']),
                TextColumn::make('payload.status')->label('Statut')->badge()->formatStateUsing(fn () => 'Payée')->color('success'),
                TextColumn::make('payload.issue_date')->label('Émise le')->date('d/m/Y'),
            ])
            ->recordUrl(fn (ExternalInvoice $record) => ExternalInvoiceCatalog::url($record))
            ->openRecordUrlInNewTab()
            ->recordActions([
                Action::make('open')->label('Ouvrir')->url(fn (ExternalInvoice $record) => ExternalInvoiceCatalog::url($record))->openUrlInNewTab(),
                Action::make('pdf')->label('PDF')->url(fn (ExternalInvoice $record) => ExternalInvoiceCatalog::url($record, true))->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Aucune facture Permis-construire réglée')
            ->emptyStateDescription('Les factures apparaissent ici après paiement et synchronisation.')
            ->paginated([10, 25, 50]);
    }
}
