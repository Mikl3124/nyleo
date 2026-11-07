<?php

namespace App\Filament\Resources\Dossiers\Pages;

use App\Enums\DocumentCategory;
use App\Enums\DossierStatus;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Dossiers\DossierResource;
use App\Filament\Resources\Dossiers\PieceRequestActions;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Filament\Support\DeleteActions;
use App\Filament\Widgets\ClientTrackingWidget;
use App\Models\Dossier;
use App\Notifications\PortalLink;
use App\Services\DossierWorkflow;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;

class ViewDossier extends ViewRecord
{
    protected static string $resource = DossierResource::class;

    public function getTitle(): string
    {
        return $this->record->reference.' — '.$this->record->title;
    }

    protected function getHeaderActions(): array
    {
        /** @var Dossier $d */
        $d = $this->record;
        $dir = 'dossiers/'.$d->id;

        return [
            Action::make('followUp')->label('Mettre à jour le suivi')->icon(Heroicon::OutlinedClipboardDocumentCheck)->color('gray')
                ->modalHeading('Suivi du dossier')->modalSubmitActionLabel('Enregistrer')
                ->modalDescription('Prochaine action, relance et attente. La note éventuelle est ajoutée au journal avec la date du jour.')
                ->fillForm(fn () => $d->only(['follow_up_action', 'waiting_on', 'waiting_on_details', 'follow_up_at', 'priority', 'missing_pieces']))
                ->schema([
                    Grid::make(3)->schema(DossierResource::followUpFields()),
                    Textarea::make('note')->label('Note datée au journal (facultatif)')->rows(2)
                        ->placeholder('Ex. Appelé le client, il envoie les photos demain'),
                    ToggleButtons::make('note_visible')->label('Note visible par le client')->boolean()->inline()->default(false),
                ])
                ->action(function (array $data) use ($d) {
                    DossierWorkflow::updateFollowUp($d, $data);
                    Notification::make()->title('Suivi mis à jour')->success()->send();
                }),

            Action::make('newQuote')->label('Faire le devis')->icon(Heroicon::OutlinedDocumentPlus)
                ->color(fn () => $d->status === DossierStatus::Nouveau ? 'primary' : 'gray')
                ->visible(fn () => in_array($d->status, [DossierStatus::Nouveau, DossierStatus::DevisEnvoye], true))
                ->url(fn () => QuoteResource::getUrl('create', ['client' => $d->client_id, 'dossier' => $d->id])),

            Action::make('plansStarted')->label('Plans en cours')->icon(Heroicon::OutlinedPencilSquare)->color('gray')
                ->visible(fn () => in_array($d->status, [DossierStatus::DevisSigne, DossierStatus::PiecesAttendues], true))
                ->action(function () use ($d) {
                    $d->update(['status' => DossierStatus::PlansEnCours]);
                    $d->log('plans_en_cours', 'Réalisation des plans en cours', true);
                }),

            // Mettre les plans en ligne et demander la validation du client en une seule étape.
            Action::make('sendPlans')->label('Envoyer les plans')->icon(Heroicon::OutlinedPaperAirplane)->color('primary')
                ->visible(fn () => in_array($d->status, [DossierStatus::DevisSigne, DossierStatus::PiecesAttendues, DossierStatus::PlansEnCours], true))
                ->modalHeading('Envoyer les plans au client')
                ->modalDescription('Le client reçoit un e-mail et valide les plans ou demande une modification en un clic. À la validation, la facture part automatiquement.')
                ->modalSubmitActionLabel('Envoyer au client')
                ->schema([
                    FileUpload::make('file')->label('Plans (PDF)')->disk('local')->directory($dir)->preserveFilenames()
                        ->acceptedFileTypes(['application/pdf'])->maxSize(51200)->required()->storeFileNamesIn('original_name'),
                    TextInput::make('name')->label('Nom affiché')->default(fn () => 'Plans '.$d->procedure->value.' '.mb_strtolower(DossierResource::NATURES[$d->nature] ?? ''))->required(),
                    Textarea::make('message')->label('Message au client (facultatif)')->rows(2),
                ])
                ->action(function (array $data) use ($d) {
                    $plan = $d->documents()->create([
                        'category' => DocumentCategory::Plan,
                        'name' => trim($data['name']),
                        'path' => $data['file'],
                        'original_name' => $data['original_name'] ?? null,
                        'uploaded_by' => 'admin',
                    ]);
                    DossierWorkflow::submitPlans($plan, $data['message'] ?? null);
                    Notification::make()->title('Plans envoyés au client')->success()->send();
                }),

            Action::make('deposit')->label('Déposé en mairie')->icon(Heroicon::OutlinedBuildingLibrary)->color('primary')
                ->visible(fn () => in_array($d->status, [DossierStatus::PlansValides, DossierStatus::PlansEnCours, DossierStatus::PlansAValider], true))
                ->modalDescription('Le client est prévenu et voit la date limite de décision dans son espace.')
                ->schema([
                    DatePicker::make('date')->label('Date de dépôt')->default(now())->required(),
                    TextInput::make('number')->label('N° de dossier (ex. DP 024 256 26 00032)'),
                    Select::make('channel')->label('Mode de dépôt')->options([
                        'portail' => 'Guichet numérique (GNAU)', 'mail' => 'Par e-mail à la mairie', 'papier' => 'Papier en mairie', 'courrier' => 'Courrier recommandé',
                    ])->default('portail'),
                    FileUpload::make('receipt')->label('Récépissé / accusé d\'enregistrement')->disk('local')->directory($dir)->preserveFilenames(),
                ])
                ->action(function (array $data) use ($d) {
                    DossierWorkflow::markDeposited($d, Carbon::parse($data['date']), $data['number'] ?? null, $data['channel'] ?? null, $data['receipt'] ?? null);
                    Notification::make()->title('Dossier passé en instruction, client prévenu')->success()->send();
                }),

            PieceRequestActions::add($d),
            PieceRequestActions::send($d),

            ActionGroup::make([
                Action::make('decision')->label('Décision de la mairie')->icon(Heroicon::OutlinedScale)
                    ->visible(fn () => in_array($d->status, [DossierStatus::EnInstruction, DossierStatus::Incomplet, DossierStatus::Depose], true))
                    ->schema([
                        ToggleButtons::make('granted')->label('Décision')->boolean('Accordé', 'Refusé')->inline()->default(true)->required(),
                        DatePicker::make('date')->label('Date de l\'arrêté')->default(now())->required(),
                        FileUpload::make('arrete')->label('Arrêté')->disk('local')->directory($dir)->preserveFilenames(),
                    ])
                    ->action(fn (array $data) => DossierWorkflow::markDecision($d, (bool) $data['granted'], Carbon::parse($data['date']), $data['arrete'] ?? null)),
                Action::make('status')->label('Changer le statut manuellement')->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->schema([Select::make('status')->label('Statut')->options(DossierStatus::options())->default($d->status->value)->required()])
                    ->action(function (array $data) use ($d) {
                        $d->update(['status' => $data['status']]);
                        $d->log('statut', 'Statut changé : '.$d->status->label(), true);
                    }),
                Action::make('note')->label('Ajouter une note au journal')->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                    ->schema([
                        Textarea::make('message')->label('Note')->required(),
                        ToggleButtons::make('visible')->label('Visible par le client')->boolean()->inline()->default(false),
                    ])
                    ->action(fn (array $data) => $d->log('note', $data['message'], (bool) $data['visible'])),
                Action::make('portal')->label('Envoyer le lien espace client')->icon(Heroicon::OutlinedEnvelope)
                    ->visible(fn () => filled($d->client->email))
                    ->requiresConfirmation()
                    ->action(function () use ($d) {
                        $d->client->notify(new PortalLink);
                        Notification::make()->title('Lien envoyé')->success()->send();
                    }),
                Action::make('merge')->label('Fusionner avec un autre dossier')->icon(Heroicon::OutlinedSquare2Stack)
                    ->visible(fn () => Dossier::where('client_id', $d->client_id)->whereKeyNot($d->id)->exists())
                    ->modalHeading('Fusionner deux dossiers du même projet')
                    ->modalDescription('Devis, factures, pièces et journal de l\'autre dossier passent dans celui-ci. L\'autre dossier est ensuite supprimé.')
                    ->modalSubmitActionLabel('Fusionner')
                    ->schema([
                        Select::make('duplicate_id')->label('Dossier en double à fusionner dans celui-ci')->required()
                            ->options(fn () => Dossier::where('client_id', $d->client_id)->whereKeyNot($d->id)->get()
                                ->mapWithKeys(fn (Dossier $o) => [$o->id => $o->reference.' — '.$o->title.' ('.$o->created_at->format('d/m/Y').')'])),
                    ])
                    ->action(function (array $data) use ($d) {
                        DossierWorkflow::merge($d, Dossier::where('client_id', $d->client_id)->findOrFail($data['duplicate_id']));
                        Notification::make()->title('Dossiers fusionnés')->success()->send();
                        $this->refreshFormData([]);
                    }),
                Action::make('client')->label('Fiche client')->icon(Heroicon::OutlinedUser)
                    ->url(fn () => ClientResource::getUrl('edit', ['record' => $d->client_id])),
                DeleteActions::record(DossierResource::getUrl()),
            ])->label('Plus')->button()->color('gray'),
            EditAction::make()->color('gray'),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [ClientTrackingWidget::class];
    }
}
