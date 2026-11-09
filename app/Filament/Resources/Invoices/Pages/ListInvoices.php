<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderWidgets(): array
    {
        return [\App\Filament\Widgets\PermisInvoices::class];
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nouvelle facture')];
    }
}
