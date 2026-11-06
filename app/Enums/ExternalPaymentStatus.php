<?php

namespace App\Enums;

/** Règlement suivi hors Nyleo (dossiers repris de l'ancien fichier, sans facture dans Nyleo). */
enum ExternalPaymentStatus: string
{
    case Devis = 'devis';
    case Accepte = 'accepte';
    case Facture = 'facture';
    case Paye = 'paye';
    case Refuse = 'refuse';

    public function label(): string
    {
        return match ($this) {
            self::Devis => 'Devis envoyé',
            self::Accepte => 'Devis accepté',
            self::Facture => 'Facturé, pas encore payé',
            self::Paye => 'Payé',
            self::Refuse => 'Devis refusé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Devis, self::Accepte => 'gray',
            self::Facture => 'warning',
            self::Paye => 'success',
            self::Refuse => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
