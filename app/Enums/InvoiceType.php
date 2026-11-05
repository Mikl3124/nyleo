<?php

namespace App\Enums;

enum InvoiceType: string
{
    case Facture = 'facture';
    case Acompte = 'acompte';
    case Avoir = 'avoir';

    public function label(): string
    {
        return match ($this) {
            self::Facture => 'Facture',
            self::Acompte => "Facture d'acompte",
            self::Avoir => 'Avoir',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
