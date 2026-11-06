<?php

namespace App\Filament\Resources\Dossiers\Pages;

use App\Enums\WaitingOn;
use App\Filament\Resources\Dossiers\DossierResource;
use App\Models\Dossier;
use App\Services\SuiviImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ListDossiers extends ListRecords
{
    protected static string $resource = DossierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importSuivi')->label('Importer l\'ancien suivi')->icon(Heroicon::OutlinedArrowUpTray)->color('gray')
                ->modalHeading('Reprendre le fichier Excel « Suivi Plans »')
                ->modalDescription('Dans Google Sheets, ouvrez l\'onglet « Feuille 1 », puis Fichier › Télécharger › CSV. Une ligne déjà importée est mise à jour, sans doublon. Aucun e-mail n\'est envoyé aux clients.')
                ->schema([
                    FileUpload::make('file')->label('Fichier CSV')->disk('local')->directory('imports')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])->required(),
                    Toggle::make('dry_run')->label('Simuler d\'abord (rien n\'est enregistré)')->default(true),
                ])
                ->action(function (array $data) {
                    $path = Storage::disk('local')->path($data['file']);
                    try {
                        $r = SuiviImporter::import($path, (bool) $data['dry_run']);
                    } catch (\Throwable $e) {
                        Notification::make()->title('Import impossible')->body($e->getMessage())->danger()->persistent()->send();

                        return;
                    } finally {
                        Storage::disk('local')->delete($data['file']);
                    }
                    Notification::make()
                        ->title(($data['dry_run'] ? 'Simulation : ' : '')."{$r['created']} dossier(s) créé(s), {$r['updated']} mis à jour")
                        ->body("{$r['clients']} client(s) créé(s)".($r['errors'] ? '. Erreurs : '.implode(' — ', array_slice($r['errors'], 0, 5)) : '.'))
                        ->color($r['errors'] ? 'warning' : 'success')->persistent()->send();
                }),
            CreateAction::make()->label('Nouveau dossier'),
        ];
    }

    public function getTabs(): array
    {
        $open = fn (Builder $query) => $query->whereNotIn('status', Dossier::closedStatuses());

        return [
            'tous' => Tab::make('Tous'),
            'relancer' => Tab::make('À relancer')
                ->modifyQueryUsing(fn (Builder $query) => $query->followUpDue())
                ->badge(fn () => Dossier::query()->followUpDue()->count() ?: null)->badgeColor('danger'),
            'moi' => Tab::make('À moi de jouer')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('waiting_on', WaitingOn::Moi->value)),
            'client' => Tab::make('J\'attends le client')
                ->modifyQueryUsing(fn (Builder $query) => $open($query)->where('waiting_on', WaitingOn::Client->value)),
            'mairie' => Tab::make('J\'attends la mairie')
                ->modifyQueryUsing(fn (Builder $query) => $open($query)->where('waiting_on', WaitingOn::Mairie->value)),
        ];
    }
}
