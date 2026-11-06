<?php

namespace App\Support;

/** Textes du site vitrine nyleo.fr (prestations, secteurs, FAQ). */
class SiteContent
{
    public const DEPARTMENTS = [
        'dordogne' => [
            'name' => 'Dordogne', 'code' => '24',
            'intro' => "Périgord blanc, vert, pourpre ou noir : chaque commune a ses règles (PLU, carte communale, RNU) et, souvent, un périmètre de monument historique. Je prépare des dossiers adaptés aux exigences locales, avec une attention particulière à l'insertion dans le paysage.",
            'towns' => ['Périgueux', 'Bergerac', 'Sarlat-la-Canéda', 'Ribérac', 'Mussidan', 'Sourzac', 'Neuvic', 'Saint-Astier', 'Montpon-Ménestérol', 'Nontron', 'Terrasson-Lavilledieu', 'Brantôme', 'Vergt', 'Lalinde', 'Eymet', 'Excideuil'],
        ],
        'gironde' => [
            'name' => 'Gironde', 'code' => '33',
            'intro' => "De la métropole bordelaise au Libournais, du Bassin d'Arcachon à l'Entre-deux-Mers : les PLU y sont souvent exigeants (emprise au sol, retraits, aspect des façades). Je construis votre dossier pour qu'il réponde au règlement de votre zone dès le premier dépôt.",
            'towns' => ['Bordeaux', 'Libourne', 'Saint-Émilion', 'Coutras', 'Sainte-Foy-la-Grande', 'Castillon-la-Bataille', 'Mérignac', 'Pessac', 'Le Bouscat', 'Arcachon', 'La Teste-de-Buch', 'Blaye', 'Langon', 'Créon', 'Saint-André-de-Cubzac', 'Lesparre-Médoc'],
        ],
    ];

    /** Projets courants : [clé, titre, procédure, ligne courte]. */
    public const PROJECTS = [
        ['veranda', 'Véranda', 'DP ou PC', 'Selon la surface créée'],
        ['pergola', 'Pergola', 'DP', 'Bioclimatique ou bois'],
        ['carport', 'Carport, abri', 'DP', 'Entre 5 et 20 m²'],
        ['piscine', 'Piscine', 'DP', 'Bassin de 10 à 100 m²'],
        ['extension', 'Extension, garage', 'DP ou PC', 'Jusqu\'à 40 m² en zone U'],
        ['maison', 'Maison individuelle', 'PC', 'Moins de 150 m²'],
        ['cloture', 'Clôture, portail', 'DP', 'Si la commune l\'exige'],
        ['facade', 'Façade, ouvertures', 'DP', 'Fenêtres, enduit, toiture'],
        ['solaire', 'Panneaux solaires', 'DP', 'En toiture ou au sol'],
    ];

    public const STEPS = [
        ['Un appel pour comprendre votre projet', "On en parle simplement au téléphone. Je vérifie quelle démarche s'applique et je vous envoie un devis clair, au prix fixe."],
        ['Vous signez le devis en ligne', "Un clic depuis votre espace, sans code par SMS. Vous m'envoyez ensuite quelques photos et les mesures utiles."],
        ['Je dessine les plans', "Plan de situation, plan de masse, coupes, façades, insertion paysagère. Deux versions sont comprises pour vos corrections."],
        ['Je dépose le dossier en votre nom', "Sur la plateforme en ligne de la mairie ou au guichet. Vous recevez le récépissé de dépôt dans votre espace."],
        ['Je suis l\'instruction jusqu\'à la décision', "S'il manque une pièce, je m'en occupe. Vous suivez chaque étape depuis votre espace client."],
    ];

    public const DP_PIECES = [
        ['DP1', 'Plan de situation', 'Situe le terrain dans la commune.'],
        ['DP2', 'Plan de masse', 'Le projet sur la parcelle, cotes et distances aux limites.'],
        ['DP3', 'Plan en coupe', 'Le profil du terrain et la hauteur du projet.'],
        ['DP4', 'Plans des façades et des toitures', 'Avant et après travaux.'],
        ['DP6', 'Insertion paysagère', 'Photo-montage du projet dans son environnement.'],
        ['DP7', 'Photographie proche', 'Le terrain vu de près.'],
        ['DP8', 'Photographie lointaine', 'Le terrain dans le paysage.'],
        ['CERFA', 'Formulaire n° 13703', 'Rempli et signé pour vous.'],
    ];

    public const PC_PIECES = [
        ['PCMI1', 'Plan de situation', 'Situe le terrain dans la commune.'],
        ['PCMI2', 'Plan de masse', 'Implantation, cotes, réseaux, accès.'],
        ['PCMI3', 'Plan en coupe', 'Le terrain et la construction en coupe.'],
        ['PCMI4', 'Notice descriptive', 'Le terrain, le projet, les matériaux.'],
        ['PCMI5', 'Façades et toitures', 'Toutes les élévations du projet.'],
        ['PCMI6', 'Insertion paysagère', 'Photo-montage du projet dans son environnement.'],
        ['PCMI7', 'Photographie proche', 'Le terrain vu de près.'],
        ['PCMI8', 'Photographie lointaine', 'Le terrain dans le paysage.'],
        ['CERFA', 'Formulaire n° 13406', 'Rempli et signé pour vous.'],
    ];

    public const FAQ = [
        ['Déclaration préalable ou permis de construire : comment savoir ?', "Tout dépend de la surface créée (emprise au sol ou surface de plancher). En dessous de 5 m², aucune démarche en général. Entre 5 et 20 m², c'est une déclaration préalable. Au-delà de 20 m², c'est un permis de construire, sauf pour une extension en zone urbaine d'un PLU, où la déclaration préalable va jusqu'à 40 m². Dites-moi votre projet au téléphone : je vous indique la bonne démarche."],
        ['Combien de temps dure l\'instruction ?', "Un mois pour une déclaration préalable, deux mois pour le permis d'une maison individuelle ou de son extension, trois mois pour les autres permis. Comptez un mois de plus si le terrain est proche d'un monument historique (avis de l'Architecte des Bâtiments de France). Le délai démarre quand le dossier est complet."],
        ['Faut-il un architecte ?', "Seulement si la surface de plancher totale (existant compris) dépasse 150 m². En dessous, je prépare tout le dossier. Au-delà, je travaille avec un architecte partenaire qui signe le projet."],
        ['Devez-vous vous déplacer chez moi ?', "Dans la plupart des cas, non : vos photos, quelques mesures et le cadastre suffisent. Si un relevé sur place est nécessaire, je vous le dis avant le devis."],
        ['Que se passe-t-il si la mairie demande des pièces en plus ?', "Je reprends les pièces demandées et je dépose les compléments dans le délai imparti. Si la demande vient d'une erreur de ma part, c'est sans frais."],
        ['Le prix comprend-il la taxe d\'aménagement ?', "Non. La taxe d'aménagement et la redevance d'archéologie préventive sont des taxes dues à l'administration après l'accord. Je peux vous en donner une estimation."],
        ['L\'étude thermique RE2020 est-elle comprise ?', "Non. Pour une maison neuve, l'attestation RE2020 est exigée au dépôt du permis. Elle est réalisée à part par un bureau d'études thermiques."],
        ['Comment payer ?', "Par virement ou par chèque. Les coordonnées figurent sur la facture et dans votre espace client. Le dossier est déposé en mairie après règlement."],
        ['Une fois l\'accord obtenu, que dois-je faire ?', "Affichez le panneau sur le terrain, visible de la rue, pendant toute la durée des travaux. Les voisins disposent de deux mois de recours à partir du premier jour d'affichage. L'autorisation est valable trois ans et peut être prolongée deux fois d'un an."],
    ];
}
