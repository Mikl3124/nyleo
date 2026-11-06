<?php

namespace App\Filament\Resources\Dossiers\Pages;

use App\Filament\Resources\Dossiers\DossierResource;
use App\Filament\Support\DeleteActions;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDossier extends EditRecord
{
    protected static string $resource = DossierResource::class;

    protected function afterSave(): void
    {
        if ($this->record->wasChanged('status')) {
            $this->record->log('statut', 'Statut changé : '.$this->record->status->label(), true);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteActions::record(DossierResource::getUrl())];
    }
}
