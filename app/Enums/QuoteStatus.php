<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Brouillon = 'brouillon';
    case Envoye = 'envoye';
    case Signe = 'signe';
    case Refuse = 'refuse';
    case Expire = 'expire';

    public function label(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Envoye => 'Envoyé',
            self::Signe => 'Signé',
            self::Refuse => 'Refusé',
            self::Expire => 'Expiré',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Brouillon => 'gray',
            self::Envoye => 'info',
            self::Signe => 'success',
            self::Refuse, self::Expire => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
