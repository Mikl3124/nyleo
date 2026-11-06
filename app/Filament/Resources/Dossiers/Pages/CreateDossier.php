<?php

namespace App\Filament\Resources\Dossiers\Pages;

use App\Filament\Resources\Dossiers\DossierResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDossier extends CreateRecord
{
    protected static string $resource = DossierResource::class;

    protected function afterCreate(): void
    {
        $this->record->log('creation', 'Dossier créé');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
