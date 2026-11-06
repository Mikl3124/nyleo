<?php

namespace App\Filament\Resources\Dossiers\RelationManagers;

use App\Enums\QuoteStatus;
use App\Filament\Resources\Quotes\QuoteResource;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuotesRelationManager extends RelationManager
{
    protected static string $relationship = 'quotes';

    protected static ?string $title = 'Devis';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label('N°')->placeholder('Brouillon'),
                TextColumn::make('subject')->label('Objet'),
                TextColumn::make('total_ttc')->label('Montant')->formatStateUsing(fn ($state) => money($state)),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (QuoteStatus $state) => $state->label())->color(fn (QuoteStatus $state) => $state->color()),
                TextColumn::make('signed_at')->label('Signé le')->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([
                Action::make('create')->label('Nouveau devis')
                    ->url(fn () => QuoteResource::getUrl('create', ['client' => $this->getOwnerRecord()->client_id, 'dossier' => $this->getOwnerRecord()->id])),
            ])
            ->recordActions([
                Action::make('open')->label('Ouvrir')->url(fn ($record) => QuoteResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
