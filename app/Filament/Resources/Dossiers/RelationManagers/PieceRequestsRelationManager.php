<?php

namespace App\Filament\Resources\Dossiers\RelationManagers;

use App\Enums\WaitingOn;
use App\Filament\Resources\Dossiers\PieceRequestActions;
use App\Models\PieceRequest;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PieceRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'pieceRequests';

    protected static ?string $title = 'Pièces demandées par la mairie';

    protected static ?string $modelLabel = 'demande de pièces';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $n = $ownerRecord->openPieceRequests()->count();

        return $n ? (string) $n : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            ...PieceRequestActions::fields(),
            DatePicker::make('sent_at')->label('Envoyées à la mairie le'),
            Textarea::make('notes')->label('Notes internes')->rows(2),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('Aucune demande de la mairie')
            ->columns([
                TextColumn::make('requested_at')->label('Demandé le')->date('d/m/Y')->placeholder('—'),
                TextColumn::make('pieces')->label('Pièces')->wrap()->limit(120),
                TextColumn::make('deadline')->label('Avant le')->date('d/m/Y')->placeholder('—')
                    ->color(fn (PieceRequest $r) => $r->isLate() ? 'danger' : null),
                TextColumn::make('waiting_on')->label('Qui fournit')->badge()
                    ->formatStateUsing(fn (WaitingOn $state) => $state === WaitingOn::Moi ? 'Moi' : 'Le client')
                    ->color(fn (WaitingOn $state) => $state->color()),
                TextColumn::make('sent_at')->label('Envoyées le')->date('d/m/Y')->placeholder('À envoyer')
                    ->color(fn (PieceRequest $r) => $r->isOpen() ? 'warning' : 'success'),
            ])
            ->headerActions([PieceRequestActions::add($this->getOwnerRecord())])
            ->recordActions([
                PieceRequestActions::sendOne($this->getOwnerRecord()),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
