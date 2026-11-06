<?php

namespace App\Enums;

/** Qui doit faire quelque chose pour que le dossier avance. */
enum WaitingOn: string
{
    case Moi = 'moi';
    case Client = 'client';
    case Mairie = 'mairie';
    case Prescripteur = 'prescripteur';
    case Tiers = 'tiers';

    public function label(): string
    {
        return match ($this) {
            self::Moi => 'Moi',
            self::Client => 'Le client',
            self::Mairie => 'La mairie',
            self::Prescripteur => 'Le prescripteur',
            self::Tiers => 'Un tiers (SPANC, ABF, géomètre…)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Moi => 'danger',
            self::Client => 'warning',
            self::Mairie => 'info',
            self::Prescripteur, self::Tiers => 'gray',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
