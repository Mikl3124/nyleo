<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Dossiers\DossierResource;
use App\Filament\Resources\Quotes\QuoteResource;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

/** Tableau de bord : les deux gestes les plus fréquents sont à un clic dès l'arrivée. */
class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('newDossier')->label('Nouveau dossier')->icon(Heroicon::OutlinedFolderPlus)
                ->url(DossierResource::getUrl('create')),
            Action::make('newQuote')->label('Nouveau devis')->icon(Heroicon::OutlinedDocumentPlus)->color('gray')
                ->url(QuoteResource::getUrl('create')),
        ];
    }
}
