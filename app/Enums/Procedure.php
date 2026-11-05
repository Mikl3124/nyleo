<?php

namespace App\Enums;

enum Procedure: string
{
    case DP = 'DP';
    case PC = 'PC';
    case PCArchi = 'PC_ARCHI';
    case PA = 'PA';
    case Autre = 'AUTRE';

    public function label(): string
    {
        return match ($this) {
            self::DP => 'Déclaration préalable',
            self::PC => 'Permis de construire',
            self::PCArchi => 'Permis de construire (avec architecte)',
            self::PA => "Permis d'aménager",
            self::Autre => 'Autre prestation',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
