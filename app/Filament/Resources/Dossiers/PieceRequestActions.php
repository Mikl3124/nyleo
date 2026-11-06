<?php

namespace App\Filament\Resources\Dossiers;

use App\Enums\DossierStatus;
use App\Models\Dossier;
use App\Models\PieceRequest;
use App\Services\DossierWorkflow;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;

/** Actions « La mairie demande des pièces » et « Pièces envoyées », sur la fiche et dans l'onglet des demandes. */
class PieceRequestActions
{
    /** Statuts où la mairie peut encore demander des pièces. */
    public static function canRequest(Dossier $d): bool
    {
        return in_array($d->status, [DossierStatus::Depose, DossierStatus::EnInstruction, DossierStatus::Incomplet], true);
    }

    public static function fields(): array
    {
        return [
            Grid::make(2)->schema([
                DatePicker::make('requested_at')->label('Date du courrier de la mairie')->default(now())->required()->live()
                    ->afterStateUpdated(fn ($state, Set $set) => $state ? $set('deadline', Carbon::parse($state)->addMonthsNoOverflow(3)->toDateString()) : null),
                DatePicker::make('deadline')->label('À fournir avant le')->default(now()->addMonthsNoOverflow(3))->required()
                    ->helperText('En principe 3 mois après le courrier, sinon le dossier est rejeté.'),
            ]),
            Textarea::make('pieces')->label('Pièces demandées (visible par le client)')->rows(4)->required()
                ->placeholder("Ex. PCMI4 : notice complétée\nPlan de masse coté avec la distance aux limites"),
            ToggleButtons::make('waiting_on')->label('Qui doit fournir les pièces ?')->inline()->required()->default('moi')
                ->options(['moi' => 'Moi', 'client' => 'Le client'])
                ->colors(['moi' => 'danger', 'client' => 'warning']),
        ];
    }

    public static function add(Dossier $d): Action
    {
        return Action::make('incomplete')->label('La mairie demande des pièces')->icon(Heroicon::OutlinedExclamationTriangle)->color('warning')
            ->visible(fn () => self::canRequest($d))
            ->modalHeading('Ajouter une demande de pièces de la mairie')
            ->modalDescription('Le dossier passe à l\'étape « Pièces demandées par la mairie », qui s\'affiche dans la frise du client.')
            ->modalSubmitActionLabel('Ajouter la demande')
            ->schema([
                ...self::fields(),
                FileUpload::make('letter')->label('Courrier de la mairie (facultatif)')->disk('local')->directory('dossiers/'.$d->id)->preserveFilenames(),
                Toggle::make('notify')->label('Prévenir le client par e-mail')->default(true),
            ])
            ->action(function (array $data) use ($d) {
                DossierWorkflow::markIncomplete($d, Carbon::parse($data['requested_at']), $data['pieces'] ?? null,
                    Carbon::parse($data['deadline']), $data['waiting_on'], $data['letter'] ?? null, (bool) ($data['notify'] ?? true));
                Notification::make()->title('Demande de pièces ajoutée')->success()->send();
            });
    }

    /** Toutes les demandes ouvertes du dossier sont envoyées (bouton de la fiche). */
    public static function send(Dossier $d): Action
    {
        return Action::make('completed')->label('Pièces complémentaires envoyées')->icon(Heroicon::OutlinedCheckCircle)->color('success')
            ->visible(fn () => $d->openPieceRequests()->exists())
            ->schema([DatePicker::make('date')->label('Date d\'envoi à la mairie')->default(now())->required()])
            ->action(function (array $data) use ($d) {
                DossierWorkflow::markCompleted($d, Carbon::parse($data['date']));
                Notification::make()->title('Pièces marquées comme envoyées')->success()->send();
            });
    }

    /** Une seule demande envoyée (ligne de l'onglet des demandes). */
    public static function sendOne(Dossier $d): Action
    {
        return Action::make('sendPieces')->label('Pièces envoyées')->icon(Heroicon::OutlinedCheckCircle)->color('success')
            ->visible(fn (PieceRequest $record) => $record->isOpen())
            ->schema([DatePicker::make('date')->label('Date d\'envoi à la mairie')->default(now())->required()])
            ->action(function (array $data, PieceRequest $record) use ($d) {
                DossierWorkflow::markCompleted($d, Carbon::parse($data['date']), $record);
                Notification::make()->title('Pièces marquées comme envoyées')->success()->send();
            });
    }
}
