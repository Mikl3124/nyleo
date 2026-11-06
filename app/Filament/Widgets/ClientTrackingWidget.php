<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\ClientActivity;
use App\Models\Dossier;
use App\Models\EmailMessage;
use App\Support\TrackingSummary;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

/**
 * « Suivi client » en bas d'un devis, d'une facture, d'un dossier ou d'une fiche client :
 * les e-mails envoyés (ouvert ? cliqué ?) et tout ce que le client a fait, avec la date et l'heure.
 */
class ClientTrackingWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.client-tracking';

    protected int|string|array $columnSpan = 'full';

    public ?Model $record = null;

    protected function getViewData(): array
    {
        $record = $this->record;
        if (! $record) {
            return ['summary' => null, 'emails' => collect(), 'activities' => collect(), 'showDocument' => false];
        }

        $key = $record instanceof Client ? 'client_id' : $record->getForeignKey();

        return [
            'summary' => TrackingSummary::of($record),
            'emails' => EmailMessage::where($key, $record->getKey())->latest('id')->limit(20)->get(),
            'activities' => ClientActivity::where($key, $record->getKey())->with(['quote', 'invoice', 'document'])->latest('id')->limit(50)->get(),
            // Sur un dossier ou un client, on précise de quel document il s'agit.
            'showDocument' => $record instanceof Client || $record instanceof Dossier,
        ];
    }
}
