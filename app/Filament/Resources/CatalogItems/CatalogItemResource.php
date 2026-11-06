<?php

namespace App\Filament\Resources\CatalogItems;

use App\Models\CatalogItem;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class CatalogItemResource extends Resource
{
    protected static ?string $model = CatalogItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Réglages';

    protected static ?string $modelLabel = 'prestation';

    protected static ?string $pluralModelLabel = 'catalogue de prestations';

    protected static ?string $navigationLabel = 'Catalogue';

    public const CATEGORIES = [
        'DP' => 'Déclaration préalable', 'PC' => 'Permis de construire', 'option' => 'Option', 'autre' => 'Autre',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('label')->label('Intitulé')->required()->columnSpanFull(),
            Textarea::make('description')->label('Détail affiché sur le devis')->rows(3)->columnSpanFull(),
            Select::make('category')->label('Catégorie')->options(self::CATEGORIES),
            TextInput::make('unit')->label('Unité')->default('forfait')->required(),
            TextInput::make('unit_price')->label('Prix')->numeric()->suffix('€')->required(),
            TextInput::make('position')->label('Ordre')->numeric()->default(0),
            Toggle::make('active')->label('Proposé dans les devis')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('position')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('label')->label('Intitulé')->searchable()->description(fn ($record) => str($record->description)->limit(80)),
                TextColumn::make('category')->label('Catégorie')->formatStateUsing(fn ($state) => self::CATEGORIES[$state] ?? $state)->badge(),
                TextColumn::make('unit_price')->label('Prix')->formatStateUsing(fn ($state) => money($state)),
                ToggleColumn::make('active')->label('Actif'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCatalogItems::route('/'),
        ];
    }
}
