<?php

namespace App\Filament\Resources\Partners;

use App\Models\Partner;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Partenaires qui apportent des dossiers : coordonnées, règle de commission et suivi. */
class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Activité';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'partenaire';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'company', 'email', 'phone'];
    }

    /** Champs du partenaire, aussi utilisés pour la création rapide depuis la fiche client ou dossier. */
    public static function fields(): array
    {
        return [
            TextInput::make('name')->label('Nom du contact')->required(),
            TextInput::make('company')->label('Entreprise')->placeholder('Ex. AKENA Périgueux'),
            TextInput::make('phone')->label('Téléphone')->tel(),
            TextInput::make('email')->label('E-mail')->email(),
            Select::make('commission_type')->label('Commission par défaut')->options(Partner::COMMISSION_TYPES)
                ->placeholder('Pas de commission')->live(),
            TextInput::make('commission_value')->label('Taux ou montant')->numeric()->minValue(0)
                ->suffix(fn (Get $get) => $get('commission_type') === 'percent' ? '%' : '€')
                ->visible(fn (Get $get) => filled($get('commission_type')))
                ->required(fn (Get $get) => filled($get('commission_type')))
                ->helperText('Proposé sur chaque dossier, modifiable dossier par dossier.'),
        ];
    }

    /** Liste des partenaires, avec création rapide, pour les fiches client et dossier. */
    public static function partnerSelect(): Select
    {
        return Select::make('partner_id')->label('Partenaire')
            ->relationship('partner', 'name', fn ($query) => $query->where('active', true))
            ->getOptionLabelFromRecordUsing(fn (Partner $record) => $record->display_name)
            ->searchable(['name', 'company'])->preload()
            ->createOptionForm(self::fields())
            ->createOptionModalHeading('Nouveau partenaire');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Partenaire')->columns(2)->schema([
                ...self::fields(),
                Textarea::make('notes')->label('Notes internes')->rows(3)->columnSpanFull(),
                Toggle::make('active')->label('Partenaire actif')->default(true)
                    ->helperText('Un partenaire inactif n\'est plus proposé sur les fiches client.'),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        $total = fn (string $key) => fn (Partner $r) => $r->totals()[$key];

        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make('Partenaire')->columnSpan(1)->schema([
                    TextEntry::make('company')->label('Entreprise')->placeholder('—'),
                    TextEntry::make('phone')->label('Téléphone')->placeholder('—')->color('primary')
                        ->url(fn (Partner $r) => $r->phone ? 'tel:'.preg_replace('/[^\d+]/', '', $r->phone) : null),
                    TextEntry::make('email')->label('E-mail')->placeholder('—')->copyable()->color('primary')
                        ->url(fn (Partner $r) => $r->email ? 'mailto:'.$r->email : null),
                    TextEntry::make('rule')->label('Commission par défaut')
                        ->state(fn (Partner $r) => $r->commissionRuleLabel() ?? 'Pas de commission'),
                    TextEntry::make('notes')->label('Notes')->placeholder('—'),
                ]),
                Section::make('Bilan')->columnSpan(2)->columns(3)->schema([
                    TextEntry::make('t_count')->label('Dossiers apportés')->state($total('count'))->size('lg')->weight('bold'),
                    TextEntry::make('t_commissioned')->label('Dont commissionnés')->state($total('commissioned_count'))->size('lg')->weight('bold'),
                    TextEntry::make('t_amount')->label('Montant des dossiers (HT)')->state(fn (Partner $r) => money($r->totals()['amount']))->size('lg')->weight('bold'),
                    TextEntry::make('t_commission')->label('Commissions totales')->state(fn (Partner $r) => money($r->totals()['commission']))->size('lg')->weight('bold'),
                    TextEntry::make('t_paid')->label('Déjà payées')->state(fn (Partner $r) => money($r->totals()['paid']))->size('lg')->weight('bold')->color('success'),
                    TextEntry::make('t_to_pay')->label('Reste à payer')->state(fn (Partner $r) => money($r->totals()['to_pay']))->size('lg')->weight('bold')
                        ->color(fn (Partner $r) => $r->totals()['to_pay'] > 0 ? 'warning' : null),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Partenaire')->searchable(['name', 'company'])->sortable()
                    ->description(fn (Partner $r) => $r->company),
                TextColumn::make('phone')->label('Téléphone')->searchable(),
                TextColumn::make('dossiers_count')->label('Dossiers')->counts('dossiers')->badge()->sortable(),
                TextColumn::make('amount')->label('Montant HT')->state(fn (Partner $r) => money($r->totals()['amount'])),
                TextColumn::make('to_pay')->label('Commission à payer')
                    ->state(fn (Partner $r) => $r->totals()['to_pay'])
                    ->formatStateUsing(fn ($state) => $state > 0 ? money($state) : '—')
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')->weight(fn ($state) => $state > 0 ? 'bold' : null),
                TextColumn::make('rule')->label('Commission par défaut')->state(fn (Partner $r) => $r->commissionRuleLabel() ?? '—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('active')->label('Actifs')->default(true),
            ])
            ->recordUrl(fn (Partner $r) => self::getUrl('view', ['record' => $r]))
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\DossiersRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'view' => Pages\ViewPartner::route('/{record}'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}
