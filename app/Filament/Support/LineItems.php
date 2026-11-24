<?php

namespace App\Filament\Support;

use App\Models\CatalogItem;
use App\Models\CompanySetting;
use App\Support\Totals;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                ? $state['label'].' — '.money(Totals::lineNet($state)).(Totals::isOption($state) ? ' (option)' : '')
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
                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                        if ($item = CatalogItem::find($state)) {
                            static::fillSubject($get, $set, $get('label'), $item->label);
                            $set('label', $item->label);
                            $set('description', $item->description);
                            $set('unit', $item->unit);
                            $set('unit_price', $item->unit_price);
                        }
                    })
                    ->columnSpanFull(),
                TextInput::make('label')->label('Intitulé')->required()->columnSpanFull()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $old, Get $get, Set $set) => static::fillSubject($get, $set, $old, $state)),
                Textarea::make('description')->label('Détail')->rows(2)->columnSpanFull(),
                Grid::make($vat ? 4 : 3)->schema(array_filter([
                    TextInput::make('quantity')->label('Quantité')->numeric()->default(1)->required()->live(onBlur: true),
                    TextInput::make('unit')->label('Unité')->default('forfait'),
                    TextInput::make('unit_price')->label($vat ? 'Prix unitaire HT' : 'Prix unitaire')->numeric()->suffix('€')->required()->live(onBlur: true),
                    $vat ? TextInput::make('vat_rate')->label('TVA %')->numeric()->default(CompanySetting::current()->default_vat_rate) : null,
                ])),
                Grid::make(['default' => 1, 'md' => 3])->schema([
                    ...static::discountFields('Remise sur cette ligne'),
                    Toggle::make('is_option')->label('Option')->inline(false)->live()
                        ->helperText('Affichée « option » avec son prix, non comprise dans le total.'),
                ]),
            ])
            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data) => $data + ['vat_rate' => $vat ? ($data['vat_rate'] ?? 0) : 0])
            ->columnSpanFull();
    }

    /**
     * Reprend l'intitulé de la prestation dans l'« Objet » du devis / de la facture,
     * sans le prix. L'objet reste modifiable : on ne l'écrase que s'il est vide
     * ou s'il reprenait déjà l'ancien intitulé de cette ligne.
     */
    public static function fillSubject(Get $get, Set $set, ?string $oldLabel, ?string $newLabel): void
    {
        $newLabel = trim((string) $newLabel);
        if ($newLabel === '') {
            return;
        }

        $subject = trim((string) $get('../../subject'));
        if ($subject === '' || $subject === trim((string) $oldLabel)) {
            $set('../../subject', $newLabel);
        }
    }

    /** Type de remise (aucune, %, €) et valeur : sur une ligne ou sur tout le document. */
    public static function discountFields(string $label): array
    {
        return [
            Select::make('discount_type')->label($label)
                ->options([Totals::PERCENT => 'En pourcentage (%)', Totals::AMOUNT => 'En euros (€)'])
                ->placeholder('Aucune')->live(),
            TextInput::make('discount_value')->label('Montant de la remise')->numeric()->minValue(0)
                ->suffix(fn (Get $get) => $get('discount_type') === Totals::PERCENT ? '%' : '€')
                ->visible(fn (Get $get) => filled($get('discount_type')))
                ->required(fn (Get $get) => filled($get('discount_type')))
                ->live(onBlur: true),
        ];
    }

    /** Remise sur le total du devis / de la facture, avec le détail du calcul. */
    public static function globalDiscount(): Grid
    {
        return Grid::make(['default' => 1, 'md' => 3])->schema([
            ...static::discountFields('Remise sur le total'),
            Placeholder::make('totals_detail')->label('Détail')
                ->content(fn (Get $get) => static::detail($get)),
        ])->columnSpanFull();
    }

    public static function totals(Get $get): array
    {
        return Totals::compute($get('lines') ?? [], $get('discount_type'), $get('discount_value'));
    }

    public static function total(Get $get): string
    {
        return money(static::totals($get)['ttc']);
    }

    public static function detail(Get $get): string
    {
        $t = static::totals($get);
        $parts = [];
        if ($t['discount'] > 0) {
            $parts[] = 'Sous-total '.money($t['subtotal']).', remise − '.money($t['discount']);
        }
        $parts[] = 'Total '.money($t['ttc']);
        if ($t['options'] > 0) {
            $parts[] = 'options proposées en plus : '.money($t['options']);
        }

        return implode(' · ', $parts);
    }
}
