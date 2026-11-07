<?php

namespace App\Filament\Support;

use App\Models\Client;
use App\Models\Dossier;
use App\Models\Invoice;
use App\Models\Quote;
use App\Services\Deletion;
use App\Services\InvoiceService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Boutons « Supprimer » (un enregistrement) et « Supprimer la sélection » (actions groupées)
 * pour les clients, dossiers, devis et factures (le bouton unitaire des factures reste
 * InvoiceResource::deleteAction). La modale liste tout ce qui part avec (devis, factures,
 * paiements, documents) et met en garde contre les devis signés et les factures émises.
 * Suppression définitive : voir App\Services\Deletion.
 */
class DeleteActions
{
    public static function record(?string $redirect = null): Action
    {
        return Action::make('deleteForever')
            ->label('Supprimer')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedExclamationTriangle)
            ->modalHeading(fn (Model $record) => 'Supprimer '.self::name($record).' ?')
            ->modalDescription(fn (Model $record) => Deletion::warning([$record]))
            ->modalSubmitActionLabel('Oui, supprimer définitivement')
            ->action(function (Model $record, Action $action) use ($redirect) {
                $name = self::name($record);
                self::delete($record);
                Notification::make()->title('Suppression terminée')->body(ucfirst($name).' a été effacé, avec tout ce qui en dépendait.')->success()->send();
                if ($redirect) {
                    $action->redirect($redirect);
                }
            });
    }

    public static function bulk(): BulkAction
    {
        return BulkAction::make('deleteForever')
            ->label('Supprimer la sélection')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedExclamationTriangle)
            ->modalHeading(fn (Collection $records) => 'Supprimer '.self::count($records).' ?')
            ->modalDescription(fn (Collection $records) => Deletion::warning($records))
            ->schema([
                Checkbox::make('confirm')->label('Je comprends que tout sera effacé définitivement')->accepted()
                    ->validationMessages(['accepted' => 'Cochez la case pour confirmer.']),
            ])
            ->modalSubmitActionLabel('Oui, tout supprimer')
            ->action(function (Collection $records) {
                $label = self::count($records);
                $records->sortByDesc('id')->each(fn (Model $r) => self::delete($r));
                Notification::make()->title('Suppression terminée')->body('Sélection effacée : '.$label.'.')->success()->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    private static function delete(Model $record): void
    {
        // Rechargé : un dossier a pu partir avec son client dans la même sélection.
        $fresh = $record::withTrashed()->find($record->getKey());
        match (true) {
            $fresh === null => null,
            $fresh instanceof Client => Deletion::client($fresh),
            $fresh instanceof Dossier => Deletion::dossier($fresh),
            $fresh instanceof Quote => Deletion::quote($fresh),
            $fresh instanceof Invoice => InvoiceService::delete($fresh),
        };
    }

    private static function name(Model $record): string
    {
        return match (true) {
            $record instanceof Client => 'le client '.$record->display_name,
            $record instanceof Dossier => 'le dossier '.$record->reference,
            $record instanceof Quote => 'le devis '.($record->number ?: 'brouillon'),
        };
    }

    private static function count(Collection $records): string
    {
        $n = $records->count();
        $first = $records->first();
        [$one, $many] = match (true) {
            $first instanceof Client => ['client', 'clients'],
            $first instanceof Dossier => ['dossier', 'dossiers'],
            $first instanceof Invoice => ['facture', 'factures'],
            default => ['devis', 'devis'],
        };

        return $n.' '.($n > 1 ? $many : $one);
    }
}
