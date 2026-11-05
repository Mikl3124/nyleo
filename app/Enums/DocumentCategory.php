<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case Photo = 'photo';
    case Plan = 'plan';
    case Cerfa = 'cerfa';
    case Recepisse = 'recepisse';
    case CourrierMairie = 'courrier_mairie';
    case Arrete = 'arrete';
    case Cadastre = 'cadastre';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Photo => 'Photo',
            self::Plan => 'Plans',
            self::Cerfa => 'Formulaire CERFA',
            self::Recepisse => 'Récépissé de dépôt',
            self::CourrierMairie => 'Courrier de la mairie',
            self::Arrete => 'Arrêté / décision',
            self::Cadastre => 'Plan cadastral',
            self::Autre => 'Autre document',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
