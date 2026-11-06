<?php

namespace App\Filament\Resources\Quotes;

use App\Enums\QuoteStatus;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Support\LineItems;
use App\Models\Dossier;
use App\Models\Quote;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Facturation';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'devis';

    protected static ?string $pluralModelLabel = 'devis';

    protected static ?string $recordTitleAttribute = 'number';

    public static function getNavigationBadge(): ?string
    {
        $n = Quote::where('status', QuoteStatus::Envoye->value)->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Devis envoyés en attente de signature';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Select::make('client_id')->label('Client')->relationship('client', 'last_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable(['last_name', 'first_name', 'company_name', 'email'])->preload()->required()->live()
                    ->createOptionForm(fn (Schema $s) => ClientResource::form($s))
                    ->afterStateUpdated(fn (Set $set) => $set('dossier_id', null)),
                Select::make('dossier_id')->label('Dossier')
                    ->options(fn (Get $get) => Dossier::where('client_id', $get('client_id'))->get()
                        ->mapWithKeys(fn ($d) => [$d->id => $d->reference.' — '.$d->title]))
                    ->helperText('Laisser vide pour créer le dossier automatiquement.'),
                TextInput::make('subject')->label('Objet')->placeholder('Ex. Déclaration préalable — véranda')->columnSpanFull(),
                TextInput::make('deposit_percent')->label('Acompte demandé à la signature')->numeric()->suffix('%')->default(0)
                    ->helperText('0 = pas d\'acompte. Sinon, une facture d\'acompte part automatiquement à la signature.'),
                Placeholder::make('total_preview')->label('Total')->content(fn (Get $get) => LineItems::total($get)),
            ]),
            LineItems::repeater(),
            Textarea::make('notes')->label('Conditions particulières (affichées sur le devis)')->rows(3)->columnSpanFull(),
        ])->disabled(fn (?Quote $record) => $record && ! $record->isEditable());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')->label('N°')->placeholder('Brouillon')->searchable()->sortable(),
                TextColumn::make('client.last_name')->label('Client')->formatStateUsing(fn ($record) => $record->client?->display_name)->searchable(),
                TextColumn::make('subject')->label('Objet')->limit(40)->toggleable(),
                TextColumn::make('total_ttc')->label('Montant')->formatStateUsing(fn ($state) => money($state))->sortable(),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (QuoteStatus $state) => $state->label())->color(fn (QuoteStatus $state) => $state->color()),
                TextColumn::make('issue_date')->label('Date')->date('d/m/Y')->sortable(),
                TextColumn::make('signed_at')->label('Signé le')->dateTime('d/m/Y H:i')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(QuoteStatus::options()),
            ])
            ->recordActions([EditAction::make()->label('Ouvrir')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotes::route('/'),
            'create' => Pages\CreateQuote::route('/create'),
            'edit' => Pages\EditQuote::route('/{record}/edit'),
        ];
    }

    /** Crée le dossier à la volée si le devis n'y est pas rattaché. */
    public static function ensureDossier(Quote $quote): void
    {
        if ($quote->dossier_id) {
            return;
        }
        $subject = $quote->subject ?: 'Projet de '.$quote->client->display_name;
        $procedure = str($subject)->lower()->contains(['permis', ' pc']) ? 'PC' : 'DP';
        $dossier = Dossier::create([
            'client_id' => $quote->client_id,
            'title' => $subject,
            'procedure' => $procedure,
            'site_address' => $quote->client->address,
            'site_postal_code' => $quote->client->postal_code,
            'site_city' => $quote->client->city,
        ]);
        $dossier->log('creation', 'Dossier créé avec le devis');
        $quote->forceFill(['dossier_id' => $dossier->id])->saveQuietly();
    }
}
