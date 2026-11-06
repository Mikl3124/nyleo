<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Widgets\ClientTrackingWidget;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        if (! $this->record->isProspect()) {
            return null;
        }

        return new HtmlString(Blade::render(
            '<span style="display:inline-flex;align-items:center;gap:.6rem;flex-wrap:wrap"><x-filament::badge color="warning">Prospect</x-filament::badge> Demande de devis reçue par le site le {{ $date }}. Pas encore de devis signé.</span>',
            ['date' => $this->record->created_at?->translatedFormat('j F Y')],
        ));
    }

    protected function getHeaderActions(): array
    {
        return [ClientResource::portalLinkAction(), DeleteAction::make()];
    }

    protected function getFooterWidgets(): array
    {
        return [ClientTrackingWidget::class];
    }
}
