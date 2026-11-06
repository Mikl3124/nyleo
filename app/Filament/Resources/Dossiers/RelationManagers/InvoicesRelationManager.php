<?php

namespace App\Filament\Resources\Dossiers\RelationManagers;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Factures';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label('N°')->placeholder('Brouillon'),
                TextColumn::make('type')->label('Type')->formatStateUsing(fn (InvoiceType $state) => $state->label()),
                TextColumn::make('total_ttc')->label('Montant')->formatStateUsing(fn ($state) => money($state)),
                TextColumn::make('amount_paid')->label('Réglé')->formatStateUsing(fn ($state) => money($state)),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (InvoiceStatus $state) => $state->label())->color(fn (InvoiceStatus $state) => $state->color()),
            ])
            ->headerActions([
                Action::make('create')->label('Nouvelle facture')
                    ->url(fn () => InvoiceResource::getUrl('create', ['client' => $this->getOwnerRecord()->client_id, 'dossier' => $this->getOwnerRecord()->id])),
            ])
            ->recordActions([
                InvoiceResource::paymentAction(),
                Action::make('open')->label('Ouvrir')->url(fn ($record) => InvoiceResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
