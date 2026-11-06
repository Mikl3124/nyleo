<?php

namespace App\Filament\Widgets;

use App\Enums\DossierStatus;
use App\Filament\Resources\Dossiers\DossierResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Dossier;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Tableau de bord : les dossiers qui attendent une action de Mickaël, avec le bouton pour la faire. */
class TodoDossiers extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'À faire';

    /** Statuts où la main est à Mickaël (voir Dossier::nextAction()). */
    public const STATUSES = [
        DossierStatus::Nouveau, DossierStatus::DevisSigne, DossierStatus::PiecesAttendues,
        DossierStatus::PlansEnCours, DossierStatus::PlansValides, DossierStatus::Incomplet,
    ];

    public static function todoQuery(): Builder
    {
        return Dossier::query()->with('client')->where(function (Builder $q) {
            $q->whereIn('status', array_map(fn ($s) => $s->value, self::STATUSES))
                ->orWhere(fn ($q) => $q->where('status', DossierStatus::EnInstruction->value)->whereDate('decision_due_date', '<=', now()->addDays(7)));
        });
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => self::todoQuery())
            // Les plus urgents d'abord : nouvelles demandes et dossiers à déposer, puis les plus anciens.
            ->modifyQueryUsing(fn (Builder $query) => $query->orderByRaw("CASE status WHEN 'nouveau' THEN 0 WHEN 'plans_valides' THEN 1 WHEN 'incomplet' THEN 2 ELSE 3 END")->orderBy('updated_at'))
            ->paginated([10, 25, 50])
            ->recordUrl(fn (Dossier $r) => DossierResource::getUrl('view', ['record' => $r]))
            ->emptyStateHeading('Rien à faire pour le moment')
            ->emptyStateDescription('Les dossiers qui attendent une action de votre part apparaîtront ici.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                TextColumn::make('title')->label('Projet')->weight('bold')
                    ->description(fn (Dossier $r) => $r->reference.' · '.$r->client?->display_name),
                TextColumn::make('todo')->label('Prochaine action')->state(fn (Dossier $r) => $r->nextAction())->wrap(),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (DossierStatus $state) => $state->label())->color(fn (DossierStatus $state) => $state->color()),
                TextColumn::make('updated_at')->label('Depuis')->since(),
            ])
            ->recordActions([
                Action::make('call')->label('Appeler')->icon(Heroicon::OutlinedPhone)->color('gray')
                    ->visible(fn (Dossier $r) => filled($r->client?->phone))
                    ->url(fn (Dossier $r) => 'tel:'.preg_replace('/[^\d+]/', '', $r->client->phone)),
                Action::make('quote')->label('Faire le devis')->icon(Heroicon::OutlinedDocumentPlus)
                    ->visible(fn (Dossier $r) => $r->status === DossierStatus::Nouveau)
                    ->url(fn (Dossier $r) => QuoteResource::getUrl('create', ['client' => $r->client_id, 'dossier' => $r->id])),
                Action::make('open')->label('Ouvrir')->icon(Heroicon::OutlinedArrowRight)
                    ->visible(fn (Dossier $r) => $r->status !== DossierStatus::Nouveau)
                    ->url(fn (Dossier $r) => DossierResource::getUrl('view', ['record' => $r])),
            ]);
    }
}
