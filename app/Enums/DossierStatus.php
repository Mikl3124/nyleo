<?php

namespace App\Enums;

enum DossierStatus: string
{
    case Nouveau = 'nouveau';
    case DevisEnvoye = 'devis_envoye';
    case DevisSigne = 'devis_signe';
    case PiecesAttendues = 'pieces_attendues';
    case PlansEnCours = 'plans_en_cours';
    case PlansAValider = 'plans_a_valider';
    case PlansValides = 'plans_valides';
    case Depose = 'depose';
    case Incomplet = 'incomplet';
    case EnInstruction = 'en_instruction';
    case Accorde = 'accorde';
    case Refuse = 'refuse';
    case Abandonne = 'abandonne';

    public function label(): string
    {
        return match ($this) {
            self::Nouveau => 'Nouvelle demande',
            self::DevisEnvoye => 'Devis envoyé',
            self::DevisSigne => 'Devis signé',
            self::PiecesAttendues => 'Pièces attendues',
            self::PlansEnCours => 'Plans en cours',
            self::PlansAValider => 'Plans à valider',
            self::PlansValides => 'Plans validés',
            self::Depose => 'Déposé en mairie',
            self::Incomplet => 'Pièces demandées par la mairie',
            self::EnInstruction => 'En instruction',
            self::Accorde => 'Accordé',
            self::Refuse => 'Refusé',
            self::Abandonne => 'Abandonné',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Nouveau, self::DevisEnvoye => 'gray',
            self::DevisSigne, self::PiecesAttendues, self::PlansEnCours => 'info',
            self::PlansAValider, self::Incomplet => 'warning',
            self::PlansValides, self::Depose, self::EnInstruction => 'primary',
            self::Accorde => 'success',
            self::Refuse, self::Abandonne => 'danger',
        };
    }

    /** Étapes simplifiées affichées au client (frise). */
    public static function clientSteps(): array
    {
        return [
            'devis' => 'Devis',
            'pieces' => 'Vos documents',
            'plans' => 'Réalisation des plans',
            'validation' => 'Validation des plans',
            'depot' => 'Dépôt en mairie',
            'instruction' => 'Instruction',
            'decision' => 'Décision',
        ];
    }

    public function clientStep(): string
    {
        return match ($this) {
            self::Nouveau, self::DevisEnvoye => 'devis',
            self::DevisSigne, self::PiecesAttendues => 'pieces',
            self::PlansEnCours => 'plans',
            self::PlansAValider, self::PlansValides => 'validation',
            self::Depose => 'depot',
            self::Incomplet, self::EnInstruction => 'instruction',
            self::Accorde, self::Refuse, self::Abandonne => 'decision',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
