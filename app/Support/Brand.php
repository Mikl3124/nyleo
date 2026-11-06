<?php

namespace App\Support;

/**
 * Charte graphique Nyleo : la seule source des couleurs côté PHP.
 *
 * Les mêmes valeurs sont reprises dans public/css/site.css (site vitrine),
 * public/css/nyleo.css (espace client), public/css/admin-nyleo.css (administration),
 * les e-mails (resources/views/vendor/mail) et les PDF (resources/views/pdf/_styles).
 */
final class Brand
{
    public const BLUE = '#1d3f91';     // bleu de Prusse (cyanotype) : couleur principale

    public const BLUE_DARK = '#142d6c';

    public const BLUE_LIGHT = '#e9eefa';

    public const OCHRE = '#e8a92b';    // ocre jaune : appels à l'action, accents

    public const OCHRE_DARK = '#9c6a06';

    public const OCHRE_LIGHT = '#fcf3df';

    public const INK = '#101c36';      // encre de nuit : texte

    public const INK_2 = '#3a4661';

    public const INK_3 = '#5b6580';

    public const LINE = '#dfe3eb';

    public const STONE = '#f2eee6';    // pierre calcaire du Périgord : fonds

    public const PAPER = '#ffffff';

    public const OK = '#2f7a4b';

    public const DANGER = '#a3271a';

    /**
     * Palettes de l'administration (Filament). Le bouton principal utilise la nuance 600,
     * d'où le bleu Nyleo exact en 600 ; le gris est celui du texte du site (encre en 950).
     *
     * @return array<string, array<int, string>>
     */
    public static function filamentColors(): array
    {
        return [
            'primary' => [
                50 => '#f2f5fc', 100 => self::BLUE_LIGHT, 200 => '#cdd8f1', 300 => '#a3b6e3', 400 => '#6f8bcf',
                500 => '#3a5cb0', 600 => self::BLUE, 700 => self::BLUE_DARK, 800 => '#112559', 900 => '#0f1d45', 950 => '#0b1530',
            ],
            'gray' => [
                50 => '#f7f8fa', 100 => '#eef1f5', 200 => self::LINE, 300 => '#c6cdda', 400 => '#949db2',
                500 => self::INK_3, 600 => '#4a5470', 700 => self::INK_2, 800 => '#262f47', 900 => '#182139', 950 => self::INK,
            ],
            'info' => [
                50 => '#edf6f9', 100 => '#d5ebf2', 200 => '#abd5e4', 300 => '#78b8d0', 400 => '#4497b6',
                500 => '#237c9e', 600 => '#176584', 700 => '#13536c', 800 => '#114459', 900 => '#0f3748', 950 => '#08222e',
            ],
            'success' => [
                50 => '#edf7f1', 100 => '#d3ecdc', 200 => '#a9d7ba', 300 => '#77bb91', 400 => '#4b9c6b',
                500 => '#388a59', 600 => self::OK, 700 => '#276340', 800 => '#205034', 900 => '#1a412b', 950 => '#0d2517',
            ],
            'warning' => [
                50 => self::OCHRE_LIGHT, 100 => '#f9e6bd', 200 => '#f4d18a', 300 => '#efbf5c', 400 => self::OCHRE,
                500 => '#d0920f', 600 => '#b07a0a', 700 => self::OCHRE_DARK, 800 => '#7c5408', 900 => '#5f410a', 950 => '#3a2705',
            ],
            'danger' => [
                50 => '#fdf2f0', 100 => '#fbe1dd', 200 => '#f5c0b8', 300 => '#ec9385', 400 => '#dd6352',
                500 => '#c4402e', 600 => self::DANGER, 700 => '#861f15', 800 => '#6d1b13', 900 => '#591912', 950 => '#330a06',
            ],
        ];
    }
}
