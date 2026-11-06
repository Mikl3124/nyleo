<?php

namespace App\Filament\Resources\Dossiers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Historique';

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('Aucun événement pour le moment')
            ->emptyStateDescription(null)
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i'),
                TextColumn::make('message')->label('Événement')->wrap(),
                IconColumn::make('visible_to_client')->label('Vu par le client')->boolean(),
            ]);
    }
}
