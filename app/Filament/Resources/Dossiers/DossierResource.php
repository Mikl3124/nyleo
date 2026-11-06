<?php

namespace App\Filament\Resources\Dossiers;

use App\Enums\DossierStatus;
use App\Enums\Procedure;
use App\Filament\Resources\Clients\ClientResource;
use App\Models\Dossier;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
        return ['reference', 'title', 'site_city', 'client.last_name', 'client.company_name'];
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
            Section::make('Notes')->columns(1)->schema([
                Textarea::make('client_message')->label('Message affiché au client dans son espace')->rows(2),
                Textarea::make('internal_notes')->label('Notes internes (jamais visibles par le client)')->rows(3),
            ]),
        ]);
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
                        ->color(fn (Dossier $r) => $r->decision_due_date?->isPast() && $r->status === DossierStatus::EnInstruction ? 'danger' : null)
                        ->helperText(fn (Dossier $r) => $r->deposit_date ? "Délai de droit commun : {$r->instructionMonths()} mois. Sans réponse à cette date, l'autorisation est tacite (sauf exceptions)." : null),
                    TextEntry::make('incomplete_deadline')->label('Pièces à fournir avant le')->date('d/m/Y')
                        ->visible(fn (Dossier $r) => $r->status === DossierStatus::Incomplet)->color('warning'),
                    IconEntry::make('abf')->label('Secteur ABF')->boolean(),
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
                TextColumn::make('decision_due_date')->label('Décision au plus tard')->date('d/m/Y')->sortable()->toggleable(),
                TextColumn::make('updated_at')->label('Mis à jour')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(DossierStatus::options())->multiple(),
                SelectFilter::make('procedure')->label('Procédure')->options(Procedure::options()),
                SelectFilter::make('department')->label('Département')->options(['24' => 'Dordogne (24)', '33' => 'Gironde (33)', '16' => 'Charente (16)', '17' => 'Charente-Maritime (17)', '47' => 'Lot-et-Garonne (47)']),
                SelectFilter::make('ouverts')->label('Afficher')->default('ouverts')
                    ->options(['ouverts' => 'Dossiers en cours', 'clos' => 'Dossiers clos'])
                    ->query(function (Builder $q, array $data) {
                        $closed = [DossierStatus::Accorde->value, DossierStatus::Refuse->value, DossierStatus::Abandonne->value];

                        return match ($data['value'] ?? null) {
                            'ouverts' => $q->whereNotIn('status', $closed),
                            'clos' => $q->whereIn('status', $closed),
                            default => $q,
                        };
                    }),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
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
