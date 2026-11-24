<?php

namespace App\Support;

/**
 * Calcul des totaux d'un devis ou d'une facture (Mickaël, 2026-11-24) :
 * - remise par ligne, en % ou en € (le total de la ligne est net de remise) ;
 * - lignes « option » : affichées avec leur prix, jamais comprises dans le total ;
 * - remise globale, en % ou en €, appliquée au sous-total des lignes (TVA répartie au prorata).
 *
 * Les lignes peuvent être des modèles ou les tableaux du formulaire d'administration.
 */
class Totals
{
    public const PERCENT = 'percent';

    public const AMOUNT = 'amount';

    public static function lineGross(mixed $line): float
    {
        return round((float) data_get($line, 'quantity', 0) * (float) data_get($line, 'unit_price', 0), 2);
    }

    public static function discount(float $base, ?string $type, mixed $value): float
    {
        $value = max(0, (float) $value);
        if ($base <= 0 || $value <= 0) {
            return 0.0;
        }

        return match ($type) {
            self::PERCENT => round($base * min(100, $value) / 100, 2),
            self::AMOUNT => round(min($base, $value), 2),
            default => 0.0,
        };
    }

    public static function lineNet(mixed $line): float
    {
        $gross = self::lineGross($line);

        return round($gross - self::discount($gross, data_get($line, 'discount_type'), data_get($line, 'discount_value')), 2);
    }

    public static function isOption(mixed $line): bool
    {
        return (bool) data_get($line, 'is_option', false);
    }

    /**
     * @param  iterable<mixed>  $lines
     * @return array{subtotal: float, discount: float, ht: float, vat: float, ttc: float, options: float}
     */
    public static function compute(iterable $lines, ?string $type = null, mixed $value = null): array
    {
        $subtotal = 0.0;
        $options = 0.0;
        $byRate = [];
        foreach ($lines as $line) {
            $net = self::lineNet($line);
            if (self::isOption($line)) {
                $options += $net;

                continue;
            }
            $subtotal += $net;
            $rate = (string) (float) data_get($line, 'vat_rate', 0);
            $byRate[$rate] = ($byRate[$rate] ?? 0) + $net;
        }
        $subtotal = round($subtotal, 2);
        $discount = self::discount($subtotal, $type, $value);
        $ratio = $subtotal > 0 ? ($subtotal - $discount) / $subtotal : 0;
        $vat = 0.0;
        foreach ($byRate as $rate => $base) {
            $vat += round($base * $ratio * (float) $rate / 100, 2);
        }
        $ht = round($subtotal - $discount, 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'ht' => $ht,
            'vat' => round($vat, 2),
            'ttc' => round($ht + $vat, 2),
            'options' => round($options, 2),
        ];
    }

    /** « 10 % » ou « 50,00 € », pour les libellés « Remise ». */
    public static function describe(?string $type, mixed $value): ?string
    {
        if ((float) $value <= 0) {
            return null;
        }

        return match ($type) {
            self::PERCENT => pdf_number($value).' %',
            self::AMOUNT => money($value),
            default => null,
        };
    }
}
