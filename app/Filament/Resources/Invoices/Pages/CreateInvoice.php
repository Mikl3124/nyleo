<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

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
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
