<?php

namespace App\Filament\Resources\Dossiers;

use App\Enums\DossierStatus;
use App\Enums\ExternalPaymentStatus;
use App\Enums\Priority;
use App\Enums\Procedure;
use App\Enums\WaitingOn;
use App\Filament\Resources\Clients\ClientResource;
use App\Models\Dossier;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DossierResource extends Resource
{
    protected static ?string $model = Dossier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Activité';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'dossier';

    protected static ?string $recordTitleAttribute = 'reference';

    public const NATURES = [
        'veranda' => 'Véranda', 'pergola' => 'Pergola', 'carport' => 'Carport', 'abri' => 'Abri de jardin / garage',
        'piscine' => 'Piscine', 'extension' => 'Extension', 'maison' => 'Maison individuelle', 'cloture' => 'Clôture / portail',
        'facade' => 'Modification de façade / ouvertures', 'photovoltaique' => 'Panneaux photovoltaïques', 'changement_destination' => 'Changement de destination', 'autre' => 'Autre',
    ];

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'legacy_ref', 'title', 'site_city', 'client.last_name', 'client.company_name'];
    }

    public static function getNavigationBadge(): ?string
    {
        $n = Dossier::whereIn('status', [DossierStatus::Incomplet->value, DossierStatus::PlansValides->value])->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Dossiers à déposer ou à compléter';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Projet')->columns(2)->schema([
                Select::make('client_id')->label('Client')->relationship('client', 'last_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable(['last_name', 'first_name', 'company_name', 'email'])->preload()->required()
                    ->createOptionForm(fn (Schema $schema) => ClientResource::form($schema)),
                TextInput::make('title')->label('Intitulé')->placeholder('Ex. Véranda 20 m² — Le Bouscat')->required(),
                Select::make('procedure')->label('Procédure')->options(Procedure::options())->default('DP')->required(),
                Select::make('nature')->label('Nature des travaux')->options(self::NATURES),
                Select::make('status')->label('Statut')->options(DossierStatus::options())->default('nouveau')->required()
                    ->visibleOn('edit'),
                Textarea::make('description')->label('Description du projet')->rows(3)->columnSpanFull(),
            ]),
            Section::make('Terrain')->columns(3)->schema([
                TextInput::make('site_address')->label('Adresse du terrain')->columnSpanFull(),
                TextInput::make('site_postal_code')->label('Code postal'),
                TextInput::make('site_city')->label('Commune')->columnSpan(2),
                TextInput::make('cadastre_section')->label('Section cadastrale'),
                TextInput::make('cadastre_number')->label('N° de parcelle'),
                TextInput::make('land_area')->label('Surface du terrain')->numeric()->suffix('m²'),
                Toggle::make('abf')->label('Secteur protégé (ABF)')->helperText('Ajoute 1 mois au délai d\'instruction.'),
                Toggle::make('is_individual_house')->label('Maison individuelle ou annexe')->default(true),
            ]),
            Section::make('Suivi')->description('Ce que contenait le fichier Excel : prochaine action, relance, attente, prescripteur.')
                ->columns(3)->collapsible()->schema(self::followUpFields()),
            Section::make('Instruction et liens')->columns(3)->collapsible()->collapsed()->schema([
                DatePicker::make('received_at')->label('Demande reçue le'),
                DatePicker::make('deposit_date')->label('Déposé le'),
                TextInput::make('deposit_number')->label('N° de dossier mairie'),
                DatePicker::make('instruction_started_at')->label('Début d\'instruction'),
                DatePicker::make('decision_due_date')->label('Fin d\'instruction notifiée')->helperText('Calculée au dépôt, modifiable si la mairie a notifié un autre délai.'),
                DatePicker::make('decision_date')->label('Date de la décision'),
                DatePicker::make('incomplete_notified_at')->label('Pièces demandées le'),
                DatePicker::make('incomplete_deadline')->label('Pièces à fournir avant le'),
                DatePicker::make('incomplete_answered_at')->label('Pièces envoyées le'),
                TextInput::make('portal_url')->label('Portail de dépôt (lien)')->url()->columnSpan(2),
                TextInput::make('legacy_ref')->label('Ancien n° (Excel)')->placeholder('URB-0001'),
                TextInput::make('drive_url')->label('Dossier Google Drive (lien)')->url()->columnSpanFull(),
            ]),
            Section::make('Prescripteur et règlement')->columns(3)->collapsible()->collapsed()->schema([
                TextInput::make('prescriber')->label('Prescripteur')->datalist(Dossier::PRESCRIBERS)->placeholder('Direct'),
                TextInput::make('commission_amount')->label('Commission')->numeric()->suffix('€'),
                Toggle::make('commission_paid')->label('Commission payée')->inline(false),
                TextInput::make('agreed_price')->label('Prix convenu')->numeric()->suffix('€')
                    ->helperText('Pour les dossiers sans devis dans Nyleo.'),
                Select::make('external_payment_status')->label('Règlement (hors Nyleo)')->options(ExternalPaymentStatus::options())
                    ->helperText('Uniquement si la facture n\'est pas dans Nyleo.'),
            ]),
            Section::make('Notes')->columns(1)->schema([
                Textarea::make('client_message')->label('Message affiché au client dans son espace')->rows(2),
                Textarea::make('internal_notes')->label('Notes internes (jamais visibles par le client)')->rows(3),
            ]),
        ]);
    }

    /** Champs de suivi, partagés par le formulaire et l'action « Mettre à jour le suivi ». */
    public static function followUpFields(): array
    {
        return [
            Textarea::make('follow_up_action')->label('Prochaine action (pour moi)')->rows(2)->columnSpanFull()
                ->placeholder('Ex. Relancer le client pour l\'état civil, puis déposer'),
            Select::make('waiting_on')->label('En attente de')->options(WaitingOn::options())->placeholder('Personne'),
            DatePicker::make('follow_up_at')->label('À relancer le'),
            Select::make('priority')->label('Priorité')->options(Priority::options())->default('normale')->selectablePlaceholder(false),
            Textarea::make('waiting_on_details')->label('Ce que j\'attends précisément')->rows(2)->columnSpanFull(),
            Textarea::make('missing_pieces')->label('Pièces manquantes / à vérifier')->rows(2)->columnSpanFull(),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make('Projet')->columnSpan(2)->columns(2)->schema([
                    TextEntry::make('client.full_name')->label('Client')
                        ->url(fn (Dossier $r) => ClientResource::getUrl('edit', ['record' => $r->client_id])),
                    TextEntry::make('client.phone')->label('Téléphone')->placeholder('—'),
                    TextEntry::make('procedure')->label('Procédure')->formatStateUsing(fn (Procedure $state) => $state->label()),
                    TextEntry::make('nature')->label('Nature')->formatStateUsing(fn ($state) => self::NATURES[$state] ?? $state)->placeholder('—'),
                    TextEntry::make('site')->label('Terrain')->state(fn (Dossier $r) => $r->siteAddress() ?: '—'),
                    TextEntry::make('cadastre')->label('Cadastre')
                        ->state(fn (Dossier $r) => trim($r->cadastre_section.' '.$r->cadastre_number) ?: '—'),
                    TextEntry::make('description')->label('Description')->columnSpanFull()->placeholder('—'),
                    TextEntry::make('internal_notes')->label('Notes internes')->columnSpanFull()->placeholder('—'),
                ]),
                Section::make('Suivi')->columnSpan(1)->schema([
                    TextEntry::make('status')->label('Statut')->badge()
                        ->formatStateUsing(fn (DossierStatus $state) => $state->label())->color(fn (DossierStatus $state) => $state->color()),
                    TextEntry::make('deposit_date')->label('Déposé le')->date('d/m/Y')->placeholder('Pas encore déposé'),
                    TextEntry::make('deposit_number')->label('N° de dossier mairie')->placeholder('—'),
                    TextEntry::make('decision_due_date')->label('Décision attendue au plus tard')->date('d/m/Y')->placeholder('—')
                        ->visible(fn (Dossier $r) => $r->status !== DossierStatus::Incomplet)
                        ->color(fn (Dossier $r) => $r->decision_due_date?->isPast() && $r->status === DossierStatus::EnInstruction ? 'danger' : null)
                        ->helperText(fn (Dossier $r) => $r->deposit_date ? "Délai de droit commun : {$r->instructionMonths()} mois. Sans réponse à cette date, l'autorisation est tacite (sauf exceptions)." : null),
                    TextEntry::make('incomplete_deadline')->label('Pièces à fournir avant le')->date('d/m/Y')
                        ->helperText('Le délai d\'instruction est suspendu jusqu\'à l\'envoi des pièces.')
                        ->visible(fn (Dossier $r) => $r->status === DossierStatus::Incomplet)->color('warning'),
                    IconEntry::make('abf')->label('Secteur ABF')->boolean(),
                ]),
                Section::make('À faire')->columnSpan(2)->columns(3)->schema([
                    TextEntry::make('follow_up_action')->label('Prochaine action')->columnSpanFull()->placeholder('Rien de noté')
                        ->weight('bold'),
                    TextEntry::make('waiting_on')->label('En attente de')->badge()->placeholder('Personne')
                        ->formatStateUsing(fn (WaitingOn $state) => $state->label())->color(fn (WaitingOn $state) => $state->color()),
                    TextEntry::make('follow_up_at')->label('À relancer le')->date('d/m/Y')->placeholder('—')
                        ->color(fn (Dossier $r) => $r->isFollowUpDue() ? 'danger' : null)
                        ->helperText(fn (Dossier $r) => $r->isFollowUpDue() ? 'Relance à faire' : null),
                    TextEntry::make('priority')->label('Priorité')->badge()
                        ->formatStateUsing(fn (Priority $state) => $state->label())->color(fn (Priority $state) => $state->color()),
                    TextEntry::make('waiting_on_details')->label('Ce que j\'attends')->columnSpanFull()->placeholder('—')
                        ->visible(fn (Dossier $r) => filled($r->waiting_on_details)),
                    TextEntry::make('missing_pieces')->label('Pièces manquantes / à vérifier')->columnSpanFull()
                        ->visible(fn (Dossier $r) => filled($r->missing_pieces)),
                ]),
                Section::make('Repères')->columnSpan(1)->schema([
                    TextEntry::make('prescriber')->label('Prescripteur')->placeholder('Direct'),
                    TextEntry::make('commission_amount')->label('Commission')->money('EUR')
                        ->helperText(fn (Dossier $r) => $r->commission_paid ? 'Payée' : 'À payer')
                        ->visible(fn (Dossier $r) => $r->commission_amount > 0 || $r->commission_paid),
                    TextEntry::make('received_at')->label('Demande reçue le')->date('d/m/Y')->placeholder('—'),
                    TextEntry::make('instruction_started_at')->label('Début d\'instruction')->date('d/m/Y')
                        ->visible(fn (Dossier $r) => $r->instruction_started_at !== null),
                    TextEntry::make('external_payment_status')->label('Règlement (hors Nyleo)')->badge()
                        ->formatStateUsing(fn (ExternalPaymentStatus $state) => $state->label())->color(fn (ExternalPaymentStatus $state) => $state->color())
                        ->helperText(fn (Dossier $r) => $r->agreed_price ? 'Prix convenu : '.number_format((float) $r->agreed_price, 0, ',', ' ').' €' : null)
                        ->visible(fn (Dossier $r) => $r->external_payment_status !== null),
                    TextEntry::make('drive_url')->label('Liens')->state(fn (Dossier $r) => $r->drive_url ? 'Ouvrir le Drive' : null)
                        ->url(fn (Dossier $r) => $r->drive_url, true)->color('primary')->placeholder('—')
                        ->helperText(fn (Dossier $r) => $r->legacy_ref ? 'Ancien n° '.$r->legacy_ref : null),
                    TextEntry::make('portal_url')->hiddenLabel()->state(fn (Dossier $r) => $r->portal_url ? 'Ouvrir le portail de dépôt' : null)
                        ->url(fn (Dossier $r) => $r->portal_url, true)->color('primary')
                        ->visible(fn (Dossier $r) => filled($r->portal_url)),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('reference')->label('Réf.')->searchable()->sortable(),
                TextColumn::make('title')->label('Projet')->searchable()->limit(40)
                    ->description(fn (Dossier $r) => $r->client?->display_name),
                TextColumn::make('procedure')->label('Proc.')->badge()->formatStateUsing(fn (Procedure $state) => $state->value === 'PC_ARCHI' ? 'PC + archi' : $state->value),
                TextColumn::make('site_city')->label('Commune')->searchable()->description(fn (Dossier $r) => $r->department ? "({$r->department})" : null),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (DossierStatus $state) => $state->label())->color(fn (DossierStatus $state) => $state->color()),
                TextColumn::make('follow_up_action')->label('Prochaine action')->limit(50)->wrap()->placeholder('—')
                    ->tooltip(fn (Dossier $r) => $r->follow_up_action)
                    ->description(fn (Dossier $r) => $r->waiting_on ? 'En attente : '.mb_strtolower($r->waiting_on->label()) : null),
                TextColumn::make('follow_up_at')->label('Relance')->date('d/m')->sortable()->placeholder('—')
                    ->color(fn (Dossier $r) => $r->isFollowUpDue() ? 'danger' : null)
                    ->weight(fn (Dossier $r) => $r->isFollowUpDue() ? 'bold' : null),
                TextColumn::make('priority')->label('Priorité')->badge()->sortable()->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn (Priority $state) => $state->label())->color(fn (Priority $state) => $state->color()),
                TextColumn::make('prescriber')->label('Prescripteur')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('legacy_ref')->label('Ancien n°')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('decision_due_date')->label('Décision au plus tard')->date('d/m/Y')->sortable()->toggleable(),
                TextColumn::make('updated_at')->label('Mis à jour')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(DossierStatus::options())->multiple(),
                Filter::make('a_relancer')->label('À relancer (date passée)')->query(fn (Builder $query) => $query->followUpDue()),
                SelectFilter::make('waiting_on')->label('En attente de')->options(WaitingOn::options()),
                SelectFilter::make('priority')->label('Priorité')->options(Priority::options()),
                SelectFilter::make('prescriber')->label('Prescripteur')
                    ->options(fn () => Dossier::query()->whereNotNull('prescriber')->distinct()->orderBy('prescriber')->pluck('prescriber', 'prescriber')->all()),
                Filter::make('commission_due')->label('Commission à payer')
                    ->query(fn (Builder $query) => $query->where('commission_amount', '>', 0)->where('commission_paid', false)),
                SelectFilter::make('procedure')->label('Procédure')->options(Procedure::options()),
                SelectFilter::make('department')->label('Département')->options(['24' => 'Dordogne (24)', '33' => 'Gironde (33)', '16' => 'Charente (16)', '17' => 'Charente-Maritime (17)', '47' => 'Lot-et-Garonne (47)']),
                SelectFilter::make('ouverts')->label('Afficher')->default('ouverts')
                    ->options(['ouverts' => 'Dossiers en cours', 'clos' => 'Dossiers clos'])
                    // Le paramètre doit s'appeler $query : Filament l'injecte par son nom.
                    ->query(function (Builder $query, array $data) {
                        $closed = Dossier::closedStatuses();

                        return match ($data['value'] ?? null) {
                            // Un dossier accordé ou refusé avec une relance prévue reste « en cours ».
                            'ouverts' => $query->where(fn (Builder $sub) => $sub->whereNotIn('status', $closed)->orWhereNotNull('follow_up_at')),
                            'clos' => $query->whereIn('status', $closed)->whereNull('follow_up_at'),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PieceRequestsRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
            RelationManagers\QuotesRelationManager::class,
            RelationManagers\InvoicesRelationManager::class,
            RelationManagers\EventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDossiers::route('/'),
            'create' => Pages\CreateDossier::route('/create'),
            'view' => Pages\ViewDossier::route('/{record}'),
            'edit' => Pages\EditDossier::route('/{record}/edit'),
        ];
    }
}
