<?php

namespace App\Filament\Resources\Quotes;

use App\Enums\QuoteStatus;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Support\LineItems;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Quote;
use App\Support\TrackingSummary;
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
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
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
        // Sur la fiche d'un devis : aperçu tel que le client le verra, formulaire seulement en mode « Modifier ».
        $editing = fn ($livewire) => ! $livewire instanceof Pages\EditQuote || $livewire->editing;

        return $schema->components([
            Section::make()->columns(['default' => 1, 'md' => 3])->schema([
                Select::make('client_id')->label('Client')->relationship('client', 'last_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable(['last_name', 'first_name', 'company_name', 'email'])->preload()->required()->live()
                    ->createOptionForm(fn (Schema $schema) => ClientResource::form($schema))
                    ->editOptionForm(fn (Schema $schema) => ClientResource::form($schema))
                    ->editOptionModalHeading('Modifier le client')
                    ->helperText(fn (Get $get) => static::clientSummary($get('client_id')))
                    ->afterStateUpdated(fn (Set $set) => $set('dossier_id', null)),
                Select::make('dossier_id')->label('Dossier')
                    ->options(fn (Get $get) => Dossier::where('client_id', $get('client_id'))->get()
                        ->mapWithKeys(fn ($d) => [$d->id => $d->reference.' — '.$d->title]))
                    ->placeholder('Nouveau dossier (créé automatiquement)')
                    ->helperText('Laisser vide pour créer le dossier automatiquement.'),
                Placeholder::make('total_preview')->label('Total')
                    ->content(fn (Get $get) => new HtmlString('<span style="font-size:1.75rem;font-weight:700;line-height:1.1;font-variant-numeric:tabular-nums">'.e(LineItems::total($get)).'</span>')),
                TextInput::make('subject')->label('Objet')->placeholder('Repris de la prestation choisie, modifiable')->columnSpan(['md' => 2]),
                TextInput::make('deposit_percent')->label('Acompte à la signature')->numeric()->suffix('%')->default(0)
                    ->helperText('0 = sans acompte. Sinon, la facture d\'acompte part toute seule à la signature.'),
            ])->columnSpanFull()->visible($editing),
            LineItems::repeater()->visible($editing),
            Textarea::make('notes')->label('Conditions particulières (affichées sur le devis)')->rows(3)->columnSpanFull()->visible($editing),
            View::make('filament.quote-preview')->columnSpanFull()
                ->visible(fn ($livewire) => ! $editing($livewire)),
        ])->disabled(fn (?Quote $record) => $record && ! $record->isEditable());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => TrackingSummary::withAggregates($query))
            ->columns([
                // Liste compacte : toutes les colonnes tiennent sur un écran d'ordinateur, sans défiler.
                // L'objet passe sous le client, la date de signature sous la date ; le texte complet s'affiche au survol.
                TextColumn::make('number')->label('N°')->placeholder('Brouillon')->searchable()->sortable(),
                TextColumn::make('client.last_name')->label('Client')->formatStateUsing(fn ($record) => $record->client?->display_name)
                    // La recherche porte sur le client et sur l'objet, puisque l'objet est affiché ici.
                    ->searchable(query: fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                        ->where('subject', 'like', "%{$search}%")
                        ->orWhereHas('client', fn (Builder $c) => $c->where('last_name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%"))))
                    ->description(fn (Quote $r) => $r->subject ? Str::limit($r->subject, 32) : null)
                    ->tooltip(fn (Quote $r) => mb_strlen((string) $r->subject) > 32 ? $r->subject : null),
                TextColumn::make('subject')->label('Objet')->limit(40)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_ttc')->label('Montant')->formatStateUsing(fn ($state) => money($state))->sortable(),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (QuoteStatus $state) => $state->label())->color(fn (QuoteStatus $state) => $state->color()),
                TextColumn::make('issue_date')->label('Date')->date('d/m/y')->sortable()->placeholder('—')
                    ->description(fn (Quote $r) => $r->signed_at ? 'Signé le '.$r->signed_at->timezone(config('app.timezone'))->format('d/m') : null),
                TextColumn::make('signed_at')->label('Signé le')->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TrackingSummary::column(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(QuoteStatus::options()),
            ])
            ->recordActions([EditAction::make()->label('Ouvrir')->iconButton()->tooltip('Ouvrir')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotes::route('/'),
            'create' => Pages\CreateQuote::route('/create'),
            'edit' => Pages\EditQuote::route('/{record}/edit'),
        ];
    }

    /** « e-mail · téléphone · commune » sous le client, pour vérifier d'un coup d'œil. */
    public static function clientSummary(mixed $clientId): ?string
    {
        $client = $clientId ? Client::find($clientId) : null;
        if (! $client) {
            return null;
        }
        $parts = array_filter([$client->email, $client->phone, trim($client->postal_code.' '.$client->city)]);

        return $parts ? implode(' · ', $parts) : 'Coordonnées à compléter (crayon à droite)';
    }

    /** Objet vide : on reprend l'intitulé de la première prestation (sans le prix). */
    public static function fillSubjectFromLines(Quote $quote): void
    {
        if (blank($quote->subject) && ($label = $quote->lines()->orderBy('position')->value('label'))) {
            $quote->forceFill(['subject' => $label])->saveQuietly();
        }
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
