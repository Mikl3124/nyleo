<?php

namespace App\Filament\Widgets;

use App\Enums\DossierStatus;
use App\Filament\Resources\Dossiers\DossierResource;
use App\Models\Dossier;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TodoDossiers extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'À faire';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Dossier::query()->with('client')->where(function (Builder $q) {
                $q->whereIn('status', [
                    DossierStatus::PiecesAttendues->value, DossierStatus::PlansEnCours->value,
                    DossierStatus::PlansValides->value, DossierStatus::Incomplet->value, DossierStatus::DevisSigne->value,
                ])->orWhere(fn ($q) => $q->where('status', DossierStatus::EnInstruction->value)->whereDate('decision_due_date', '<=', now()->addDays(7)));
            }))
            ->defaultSort('updated_at')
            ->paginated([5, 10, 25])
            ->columns([
                TextColumn::make('reference')->label('Réf.'),
                TextColumn::make('title')->label('Projet')->description(fn (Dossier $r) => $r->client?->display_name),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (DossierStatus $state) => $state->label())->color(fn (DossierStatus $state) => $state->color()),
                TextColumn::make('todo')->label('Prochaine action')->state(fn (Dossier $r) => match ($r->status) {
                    DossierStatus::DevisSigne, DossierStatus::PiecesAttendues => 'Récupérer les pièces / démarrer les plans',
                    DossierStatus::PlansEnCours => 'Finir et envoyer les plans',
                    DossierStatus::PlansValides => 'Déposer en mairie (vérifier le paiement)',
                    DossierStatus::Incomplet => 'Fournir les pièces avant le '.$r->incomplete_deadline?->format('d/m/Y'),
                    DossierStatus::EnInstruction => 'Décision attendue le '.$r->decision_due_date?->format('d/m/Y'),
                    default => '',
                }),
                TextColumn::make('updated_at')->label('Depuis')->since(),
            ])
            ->recordActions([
                Action::make('open')->label('Ouvrir')->url(fn (Dossier $r) => DossierResource::getUrl('view', ['record' => $r])),
            ]);
    }
}
