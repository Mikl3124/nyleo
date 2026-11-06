<?php

namespace App\Enums;

enum Priority: string
{
    case Normale = 'normale';
    case Importante = 'importante';
    case Urgente = 'urgente';

    public function label(): string
    {
        return match ($this) {
            self::Normale => 'Normale',
            self::Importante => 'Importante',
            self::Urgente => 'Urgente',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Normale => 'gray',
            self::Importante => 'warning',
            self::Urgente => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
