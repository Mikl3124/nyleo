<?php

namespace App\Filament\Support;

use App\Models\CatalogItem;
use App\Models\CompanySetting;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class LineItems
{
    public static function repeater(): Repeater
    {
        $vat = CompanySetting::current()->vat_enabled;

        return Repeater::make('lines')
            ->label('Prestations')
            ->relationship()
            ->orderColumn('position')
            ->addActionLabel('Ajouter une ligne')
            ->defaultItems(1)
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => ($state['label'] ?? null)
                ? $state['label'].' — '.money(((float) ($state['quantity'] ?? 1)) * ((float) ($state['unit_price'] ?? 0)))
                : null)
            ->schema([
                Select::make('catalog_item')
                    ->label('Prestation préconfigurée')
                    ->placeholder('Choisir dans le catalogue (facultatif)')
                    ->options(fn () => CatalogItem::where('active', true)->orderBy('position')->orderBy('label')
                        ->get()->mapWithKeys(fn ($i) => [$i->id => $i->label.' — '.money($i->unit_price)]))
                    ->searchable()
                    ->dehydrated(false)
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if ($item = CatalogItem::find($state)) {
                            $set('label', $item->label);
                            $set('description', $item->description);
                            $set('unit', $item->unit);
                            $set('unit_price', $item->unit_price);
                        }
                    })
                    ->columnSpanFull(),
                TextInput::make('label')->label('Intitulé')->required()->columnSpanFull(),
                Textarea::make('description')->label('Détail')->rows(2)->columnSpanFull(),
                Grid::make($vat ? 4 : 3)->schema(array_filter([
                    TextInput::make('quantity')->label('Quantité')->numeric()->default(1)->required()->live(onBlur: true),
                    TextInput::make('unit')->label('Unité')->default('forfait'),
                    TextInput::make('unit_price')->label($vat ? 'Prix unitaire HT' : 'Prix unitaire')->numeric()->suffix('€')->required()->live(onBlur: true),
                    $vat ? TextInput::make('vat_rate')->label('TVA %')->numeric()->default(CompanySetting::current()->default_vat_rate) : null,
                ])),
            ])
            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data) => $data + ['vat_rate' => $vat ? ($data['vat_rate'] ?? 0) : 0])
            ->columnSpanFull();
    }

    public static function total(Get $get): string
    {
        $total = collect($get('lines') ?? [])->sum(fn ($l) => (float) ($l['quantity'] ?? 0) * (float) ($l['unit_price'] ?? 0));

        return money($total);
    }
}
