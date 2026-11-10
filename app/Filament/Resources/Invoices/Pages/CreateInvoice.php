<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Client;
use App\Services\InvoiceService;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function fillForm(): void
    {
        parent::fillForm();
        $clientId = request('client');
        $this->form->fill(array_filter([
            'client_id' => $clientId,
            'dossier_id' => request('dossier') ?: ($clientId ? Client::find($clientId)?->dossierForNewInvoice()?->id : null),
        ]) + $this->form->getRawState());
    }

    protected function afterCreate(): void
    {
        $this->record->recalculate();
        InvoiceService::ensureDossier($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
