<?php

namespace App\Support;

use App\Models\CatalogItem;

/**
 * Catalogue par défaut, identique à celui en ligne (06/11/2026).
 * Descriptions sur le modèle des devis de Mickaël : un paragraphe, « Contenu du dossier : »,
 * puis une pièce par ligne commençant par « - » (affichée en puce sur les PDF).
 */
class DefaultCatalog
{
    /** @return list<array{category:string,label:string,description:string,unit:string,unit_price:float}> */
    public static function items(): array
    {
        return [
            [
                'category' => 'DP',
                'label' => 'DÉCLARATION PRÉALABLE – VÉRANDA : CONSTITUTION ET DÉPÔT DU DOSSIER',
                'description' => 'Dossier complet fourni au format PDF, comprenant l\'ensemble des pièces réglementaires requises pour l\'instruction de la déclaration préalable, et déposé en votre nom sur la plateforme administrative dédiée (ou en mairie). Suivi de l\'instruction jusqu\'à la décision.' . "\n" .
                'Contenu du dossier :' . "\n" .
                '- Formulaire CERFA n° 13703 complété' . "\n" .
                '- DP1 : Plan de situation' . "\n" .
                '- DP2 : Plan de masse' . "\n" .
                '- DP3 : Plan en coupe' . "\n" .
                '- DP4 : Plans des façades et des toitures' . "\n" .
                '- DP6 : Document graphique (insertion paysagère ou photomontage)' . "\n" .
                '- DP7 : Photographie de l\'environnement proche' . "\n" .
                '- DP8 : Photographie du paysage lointain',
                'unit' => 'forfait',
                'unit_price' => 400.00,
            ],
            [
                'category' => 'DP',
                'label' => 'DÉCLARATION PRÉALABLE – PERGOLA : CONSTITUTION ET DÉPÔT DU DOSSIER',
                'description' => 'Dossier complet fourni au format PDF, comprenant l\'ensemble des pièces réglementaires requises pour l\'instruction de la déclaration préalable, et déposé en votre nom sur la plateforme administrative dédiée (ou en mairie). Suivi de l\'instruction jusqu\'à la décision.' . "\n" .
                'Contenu du dossier :' . "\n" .
                '- Formulaire CERFA n° 13703 complété' . "\n" .
                '- DP1 : Plan de situation' . "\n" .
                '- DP2 : Plan de masse' . "\n" .
                '- DP4 : Plans des façades et des toitures' . "\n" .
                '- DP6 : Document graphique (insertion paysagère ou photomontage)' . "\n" .
                '- DP7 : Photographie de l\'environnement proche' . "\n" .
                '- DP8 : Photographie du paysage lointain',
                'unit' => 'forfait',
                'unit_price' => 400.00,
            ],
            [
                'category' => 'DP',
                'label' => 'DÉCLARATION PRÉALABLE – CARPORT / ABRI : CONSTITUTION ET DÉPÔT DU DOSSIER',
                'description' => 'Dossier complet fourni au format PDF, comprenant l\'ensemble des pièces réglementaires requises pour l\'instruction de la déclaration préalable, et déposé en votre nom sur la plateforme administrative dédiée (ou en mairie). Suivi de l\'instruction jusqu\'à la décision.' . "\n" .
                'Contenu du dossier :' . "\n" .
                '- Formulaire CERFA n° 13703 complété' . "\n" .
                '- DP1 : Plan de situation' . "\n" .
                '- DP2 : Plan de masse' . "\n" .
                '- DP3 : Plan en coupe' . "\n" .
                '- DP4 : Plans des façades et des toitures' . "\n" .
                '- DP6 : Document graphique (insertion paysagère ou photomontage)' . "\n" .
                '- DP7 : Photographie de l\'environnement proche' . "\n" .
                '- DP8 : Photographie du paysage lointain',
                'unit' => 'forfait',
                'unit_price' => 400.00,
            ],
            [
                'category' => 'DP',
                'label' => 'DÉCLARATION PRÉALABLE – PISCINE : CONSTITUTION ET DÉPÔT DU DOSSIER',
                'description' => 'Dossier complet fourni au format PDF, comprenant l\'ensemble des pièces réglementaires requises pour l\'instruction de la déclaration préalable, et déposé en votre nom sur la plateforme administrative dédiée (ou en mairie). Suivi de l\'instruction jusqu\'à la décision.' . "\n" .
                'Contenu du dossier :' . "\n" .
                '- Formulaire CERFA n° 13703 complété' . "\n" .
                '- DP1 : Plan de situation' . "\n" .
                '- DP2 : Plan de masse' . "\n" .
                '- DP3 : Plan en coupe' . "\n" .
                '- DP6 : Document graphique (insertion paysagère ou photomontage)' . "\n" .
                '- DP7 : Photographie de l\'environnement proche' . "\n" .
                '- DP8 : Photographie du paysage lointain',
                'unit' => 'forfait',
                'unit_price' => 400.00,
            ],
            [
                'category' => 'PC',
                'label' => 'PERMIS DE CONSTRUIRE – MAISON INDIVIDUELLE : CONSTITUTION ET DÉPÔT DU DOSSIER',
                'description' => 'Dossier complet fourni au format PDF, comprenant l\'ensemble des pièces réglementaires requises pour l\'instruction du permis de construire, et déposé en votre nom sur la plateforme administrative dédiée (ou en mairie). Suivi de l\'instruction jusqu\'à la décision.' . "\n" .
                'Contenu du dossier :' . "\n" .
                '- Formulaire CERFA n° 13406 complété' . "\n" .
                '- PCMI1 : Plan de situation' . "\n" .
                '- PCMI2 : Plan de masse' . "\n" .
                '- PCMI3 : Plan en coupe' . "\n" .
                '- PCMI4 : Notice descriptive' . "\n" .
                '- PCMI5 : Plans des façades et des toitures' . "\n" .
                '- PCMI6 : Document graphique (insertion paysagère ou photomontage)' . "\n" .
                '- PCMI7 : Photographie de l\'environnement proche' . "\n" .
                '- PCMI8 : Photographie du paysage lointain',
                'unit' => 'forfait',
                'unit_price' => 500.00,
            ],
            [
                'category' => 'option',
                'label' => 'IMPRESSION ET ENVOI DU DOSSIER PAPIER',
                'description' => 'Impression couleur des pièces du dossier, assemblage du nombre d\'exemplaires demandé par la mairie et envoi ou dépôt sur place.',
                'unit' => 'forfait',
                'unit_price' => 0.00,
            ],
            [
                'category' => 'option',
                'label' => 'MODIFICATION SUPPLÉMENTAIRE DES PLANS',
                'description' => 'Deux versions des plans sont incluses dans le dossier. Ce forfait s\'applique à chaque nouvelle série de modifications demandée au-delà.',
                'unit' => 'forfait',
                'unit_price' => 50.00,
            ],
            [
                'category' => 'option',
                'label' => 'COMPLÉMENT DE DOSSIER DEMANDÉ PAR LA MAIRIE',
                'description' => 'Reprise ou ajout des pièces demandées par le service instructeur, puis dépôt des compléments dans le délai imparti.' . "\n" .
                'Non facturé si la demande porte sur une erreur de notre part.',
                'unit' => 'forfait',
                'unit_price' => 0.00,
            ],
        ];
    }

    /** Crée le catalogue s'il est vide. Ne modifie jamais un catalogue existant. */
    public static function seed(): void
    {
        if (CatalogItem::query()->exists()) {
            return;
        }
        foreach (self::items() as $i => $item) {
            CatalogItem::create($item + ['position' => $i + 1]);
        }
    }
}
