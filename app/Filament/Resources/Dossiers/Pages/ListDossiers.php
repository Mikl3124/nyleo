<?php

namespace App\Filament\Resources\Dossiers\Pages;

use App\Enums\WaitingOn;
use App\Filament\Resources\Dossiers\DossierResource;
use App\Models\Dossier;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDossiers extends ListRecords
{
    protected static string $resource = DossierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nouveau dossier'),
        ];
    }

    public function getTabs(): array
    {
        $open = fn (Builder $query) => $query->whereNotIn('status', Dossier::closedStatuses());

        return [
            'tous' => Tab::make('Tous'),
            'relancer' => Tab::make('À relancer')
                ->modifyQueryUsing(fn (Builder $query) => $query->followUpDue())
                ->badge(fn () => Dossier::query()->followUpDue()->count() ?: null)->badgeColor('danger'),
            'moi' => Tab::make('À moi de jouer')
                ->modifyQueryUsing(fn (Builder $query) => $query->waitingOn(WaitingOn::Moi)),
            'client' => Tab::make('J\'attends le client')
                ->modifyQueryUsing(fn (Builder $query) => $open($query)->waitingOn(WaitingOn::Client)),
            'mairie' => Tab::make('J\'attends la mairie')
                ->modifyQueryUsing(fn (Builder $query) => $open($query)->where('waiting_on', WaitingOn::Mairie->value)),
        ];
    }
}
