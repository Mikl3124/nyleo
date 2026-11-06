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

    /** Libellé court pour la liste des factures (le libellé complet reste au survol). */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Emise => 'En attente',
            self::PartiellementPayee => 'Payée en partie',
            self::Annulee => 'Annulée',
            default => $this->label(),
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
