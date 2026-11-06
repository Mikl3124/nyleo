<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\QuoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected function fillForm(): void
    {
        parent::fillForm();
        $this->form->fill(array_filter([
            'client_id' => request('client'),
            'dossier_id' => request('dossier'),
        ]) + $this->form->getRawState());
    }

    protected function afterCreate(): void
    {
        $this->record->recalculate();
        QuoteResource::fillSubjectFromLines($this->record);
        QuoteResource::ensureDossier($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
