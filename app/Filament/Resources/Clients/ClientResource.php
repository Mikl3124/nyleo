<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Partners\PartnerResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Client;
use App\Notifications\PortalLink;
use App\Services\Communes;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Activité';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'client';

    protected static ?string $recordTitleAttribute = 'last_name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['last_name', 'first_name', 'company_name', 'email', 'phone', 'city'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identité')->columns(2)->schema([
                ToggleButtons::make('type')->label('Type de client')->inline()->default('particulier')->live()
                    ->options(['particulier' => 'Particulier', 'professionnel' => 'Professionnel'])->columnSpanFull(),
                TextInput::make('company_name')->label('Raison sociale')->visible(fn (Get $get) => $get('type') === 'professionnel'),
                TextInput::make('siret')->label('SIRET')->visible(fn (Get $get) => $get('type') === 'professionnel'),
                Select::make('civility')->label('Civilité')->options(['M.' => 'M.', 'Mme' => 'Mme', 'M. et Mme' => 'M. et Mme']),
                TextInput::make('first_name')->label('Prénom'),
                TextInput::make('last_name')->label('Nom')->required(),
                TextInput::make('email')->label('E-mail')->email()->unique(ignoreRecord: true),
                TextInput::make('phone')->label('Téléphone')->tel(),
                Select::make('source')->label('Origine du contact')->options([
                    'telephone' => 'Téléphone', 'bouche_a_oreille' => 'Bouche-à-oreille', 'partenaire' => 'Partenaire / installateur',
                    'site' => 'Site nyleo.fr', 'autre' => 'Autre',
                ])->live(),
                PartnerResource::partnerSelect()->visible(fn (Get $get) => $get('source') === 'partenaire'),
            ]),
            Group::make([
                Section::make('Adresse')->columns(3)->schema([
                    TextInput::make('address')->label('Adresse')->columnSpanFull(),
                    TextInput::make('postal_code')->label('Code postal'),
                    TextInput::make('city')->label('Commune')->columnSpan(2),
                ]),
                self::projectAddressSection(),
            ]),
            Section::make('Pour le CERFA')->columns(2)->collapsed()->schema([
                DatePicker::make('birth_date')->label('Date de naissance'),
                Select::make('birth_place')->label('Lieu de naissance')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => collect(Communes::search($search))
                        ->mapWithKeys(fn ($c) => [$c['label'] => $c['label']])
                        ->put(trim($search), trim($search).' (saisie libre)')
                        ->all())
                    ->getOptionLabelUsing(fn ($value): ?string => $value)
                    ->searchPrompt('Tapez la commune : le département est ajouté automatiquement')
                    ->helperText('Commune introuvable ou né à l\'étranger : choisissez « saisie libre ».'),
            ]),
            Textarea::make('notes')->label('Notes internes')->rows(3)->columnSpanFull(),
        ]);
    }

    /**
     * Adresse du projet (le terrain) : elle appartient au dossier le plus récent du client.
     * Sans dossier, le remplir crée le dossier ; laissé vide, rien n'est créé.
     * Masquée dans la petite fenêtre « nouveau client » des dossiers et des devis, qui ont leur propre terrain.
     */
    public static function projectAddressSection(): Section
    {
        return Section::make('Adresse du projet')
            ->description(fn (?Client $record) => ($dossier = $record?->currentDossier)
                ? 'Dossier '.$dossier->reference.' : '.$dossier->title
                : 'Le terrain où se font les travaux.')
            ->visible(fn ($livewire) => $livewire instanceof CreateClient || $livewire instanceof EditClient)
            ->relationship('currentDossier', condition: fn (?Client $record, Section $component) => $record?->currentDossier()->exists()
                || ($state = $component->getChildSchema()->getRawState()) && (($state['site_same_as_client'] ?? false)
                    || collect($state)->only(['site_address', 'site_postal_code', 'site_city', 'cadastre_section', 'cadastre_number'])->filter(fn ($v) => filled($v))->isNotEmpty()))
            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, Client $record): array {
                $city = ($data['site_same_as_client'] ?? false) ? $record->city : ($data['site_city'] ?? null);

                return [...$data, 'title' => 'Projet'.($city ? ' — '.$city : ''), 'procedure' => 'DP'];
            })
            ->columns(3)
            ->schema(self::projectAddressFields());
    }

    /** Champs du terrain, partagés par la fiche client et la fiche dossier. */
    public static function projectAddressFields(): array
    {
        $hiddenIfSame = fn (Get $get) => (bool) $get('site_same_as_client');

        return [
            Checkbox::make('site_same_as_client')->label('Identique à l\'adresse du client')->live()->columnSpanFull(),
            TextInput::make('site_address')->label('Adresse du terrain')->columnSpanFull()->hidden($hiddenIfSame),
            TextInput::make('site_postal_code')->label('Code postal')->hidden($hiddenIfSame),
            TextInput::make('site_city')->label('Commune')->columnSpan(2)->hidden($hiddenIfSame),
            TextInput::make('cadastre_section')->label('Section cadastrale')->placeholder('Ex. AB'),
            TextInput::make('cadastre_number')->label('N° de parcelle')->placeholder('Ex. 756'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withSignedQuoteFlag())
            ->columns([
                TextColumn::make('display_name')->label('Client')
                    ->searchable(['last_name', 'first_name', 'company_name'])->sortable(['last_name'])
                    ->description(fn (Client $r) => $r->type === 'professionnel' ? 'Professionnel' : null),
                TextColumn::make('prospect')->label('')
                    ->state(fn (Client $r) => $r->isProspect() ? 'Prospect' : null)
                    ->badge()->color('warning')
                    ->tooltip('Demande de devis reçue par le site, pas encore de devis signé'),
                TextColumn::make('phone')->label('Téléphone')->searchable(),
                TextColumn::make('email')->label('E-mail')->searchable()->toggleable(),
                TextColumn::make('city')->label('Commune')->searchable(),
                TextColumn::make('dossiers_count')->label('Dossiers')->counts('dossiers')->badge(),
                TextColumn::make('partner.name')->label('Partenaire')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_login_at')->label('Dernière visite espace')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('prospect')->label('Prospects du site')
                    ->placeholder('Tous les clients')
                    ->trueLabel('Prospects seulement')
                    ->falseLabel('Sans les prospects')
                    ->queries(
                        true: fn (Builder $query) => $query->prospects(),
                        false: fn (Builder $query) => $query->whereNotIn('id', Client::query()->prospects()->select('id')),
                    ),
                SelectFilter::make('type')->options(['particulier' => 'Particulier', 'professionnel' => 'Professionnel']),
                SelectFilter::make('partner_id')->label('Partenaire')->relationship('partner', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                self::portalLinkAction(),
            ]);
    }

    /** Ouvre l'écran de création de devis avec ce client (et son dossier s'il n'en a qu'un) déjà choisi. */
    public static function newQuoteAction(): Action
    {
        return Action::make('newQuote')
            ->label('Nouveau devis')
            ->icon(Heroicon::OutlinedDocumentText)
            ->url(fn (Client $record) => QuoteResource::getUrl('create', static::documentPrefill($record)));
    }

    /** Ouvre l'écran de création de facture avec ce client (et son dossier s'il n'en a qu'un) déjà choisi. */
    public static function newInvoiceAction(): Action
    {
        return Action::make('newInvoice')
            ->label('Nouvelle facture')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('gray')
            ->url(fn (Client $record) => InvoiceResource::getUrl('create', static::documentPrefill($record)));
    }

    /** @return array{client: int, dossier?: int} */
    public static function documentPrefill(Client $client): array
    {
        $dossiers = $client->dossiers()->pluck('id');

        return array_filter([
            'client' => $client->id,
            'dossier' => $dossiers->count() === 1 ? $dossiers->first() : null,
        ]);
    }

    public static function portalLinkAction(): Action
    {
        return Action::make('portalLink')
            ->label('Envoyer le lien espace client')
            ->icon(Heroicon::OutlinedEnvelope)
            ->requiresConfirmation()
            ->visible(fn (Client $record) => filled($record->email))
            ->action(function (Client $record) {
                $record->notify(new PortalLink);
                Notification::make()->title('Lien envoyé à '.$record->email)->success()->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
