<?php

namespace App\Filament\Resources\Clients;

use App\Models\Client;
use App\Notifications\PortalLink;
use App\Services\Communes;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
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
                ]),
            ]),
            Section::make('Adresse')->columns(3)->schema([
                TextInput::make('address')->label('Adresse')->columnSpanFull(),
                TextInput::make('postal_code')->label('Code postal'),
                TextInput::make('city')->label('Commune')->columnSpan(2),
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

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('display_name')->label('Client')
                    ->searchable(['last_name', 'first_name', 'company_name'])->sortable(['last_name'])
                    ->description(fn (Client $r) => $r->type === 'professionnel' ? 'Professionnel' : null),
                TextColumn::make('phone')->label('Téléphone')->searchable(),
                TextColumn::make('email')->label('E-mail')->searchable()->toggleable(),
                TextColumn::make('city')->label('Commune')->searchable(),
                TextColumn::make('dossiers_count')->label('Dossiers')->counts('dossiers')->badge(),
                TextColumn::make('last_login_at')->label('Dernière visite espace')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')->options(['particulier' => 'Particulier', 'professionnel' => 'Professionnel']),
            ])
            ->recordActions([
                EditAction::make(),
                self::portalLinkAction(),
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
