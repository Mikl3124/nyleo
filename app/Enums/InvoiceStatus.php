<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Brouillon = 'brouillon';
    case Emise = 'emise';
    case PartiellementPayee = 'partiellement_payee';
    case Payee = 'payee';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Emise => 'Émise, en attente de paiement',
            self::PartiellementPayee => 'Partiellement payée',
            self::Payee => 'Payée',
            self::Annulee => 'Annulée par avoir',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Brouillon => 'gray',
            self::Emise => 'warning',
            self::PartiellementPayee => 'info',
            self::Payee => 'success',
            self::Annulee => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
