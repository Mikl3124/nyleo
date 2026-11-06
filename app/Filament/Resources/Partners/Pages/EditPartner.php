<?php

namespace App\Filament\Resources\Partners\Pages;

use App\Filament\Resources\Partners\PartnerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPartner extends EditRecord
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->modalDescription('Ses dossiers et clients sont conservés, simplement détachés du partenaire.')];
    }

    protected function getRedirectUrl(): string
    {
        return PartnerResource::getUrl('view', ['record' => $this->record]);
    }
}
