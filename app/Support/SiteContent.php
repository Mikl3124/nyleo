<?php

namespace App\Support;

/** Textes du site vitrine nyleo.fr (prestations, secteurs, FAQ). */
class SiteContent
{
    public const DEPARTMENTS = [
        'dordogne' => [
            'name' => 'Dordogne', 'code' => '24',
            'intro' => "Périgord blanc, vert, pourpre ou noir : chaque commune a ses règles (PLU, carte communale, RNU) et, souvent, un périmètre de monument historique. Je prépare des dossiers adaptés aux exigences locales, avec une attention particulière à l'insertion dans le paysage.",
            'tips' => [
                ['Beaucoup de secteurs protégés', "Monuments historiques, vallée de la Vézère, site patrimonial de Sarlat : près d'un monument, l'avis de l'Architecte des Bâtiments de France ajoute un mois d'instruction et des exigences sur les matériaux."],
                ['Des PLU intercommunaux', "De plus en plus de communautés de communes ont un PLU commun à toutes leurs communes. Je vérifie le règlement de la zone de votre parcelle."],
                ['Assainissement individuel', "Hors du tout-à-l'égout, un permis de construire demande l'attestation du SPANC pour l'assainissement. Je vous indique la démarche."],
            ],
            'towns' => ['Périgueux', 'Bergerac', 'Sarlat-la-Canéda', 'Ribérac', 'Mussidan', 'Sourzac', 'Neuvic', 'Saint-Astier', 'Montpon-Ménestérol', 'Nontron', 'Terrasson-Lavilledieu', 'Brantôme', 'Vergt', 'Lalinde', 'Eymet', 'Excideuil'],
        ],
        'gironde' => [
            'name' => 'Gironde', 'code' => '33',
            'intro' => "De la métropole bordelaise au Libournais, du Bassin d'Arcachon à l'Entre-deux-Mers : les PLU y sont souvent exigeants (emprise au sol, retraits, aspect des façades). Je construis votre dossier pour qu'il réponde au règlement de votre zone dès le premier dépôt.",
            'tips' => [
                ['PLU de Bordeaux Métropole', "Les 28 communes de la métropole partagent un même PLU, aux règles précises sur l'emprise au sol, les hauteurs et les retraits."],
                ['Zones à risques', "Bords de Garonne et de Dordogne (inondation), forêt des Landes (feux de forêt), littoral : des règles particulières peuvent s'ajouter. Je les intègre au dossier."],
                ['Assainissement individuel', "Hors du tout-à-l'égout, un permis de construire demande l'attestation du SPANC pour l'assainissement. Je vous indique la démarche."],
            ],
            'towns' => ['Bordeaux', 'Libourne', 'Saint-Émilion', 'Coutras', 'Sainte-Foy-la-Grande', 'Castillon-la-Bataille', 'Mérignac', 'Pessac', 'Le Bouscat', 'Arcachon', 'La Teste-de-Buch', 'Blaye', 'Langon', 'Créon', 'Saint-André-de-Cubzac', 'Lesparre-Médoc'],
        ],
    ];

    /** Projets courants : clé (icône), titre, démarche, phrase courte, type pour le simulateur. */
    public const PROJECTS = [
        ['veranda', 'Véranda', 'DP ou PC', "Accolée à la maison, la surface créée décide de la démarche.", 'extension'],
        ['extension', 'Extension, garage', 'DP ou PC', "Jusqu'à 40 m² en déclaration préalable en zone urbaine d'un PLU.", 'extension'],
        ['maison', 'Maison individuelle', 'PC', 'Plans complets du permis, notice et insertion paysagère.', 'maison'],
        ['piscine', 'Piscine', 'DP', 'Bassin de 10 à 100 m², non couvert ou abri bas.', 'piscine'],
        ['pergola', 'Pergola, carport', 'DP', 'Bioclimatique, bois ou alu, abri pour voiture.', 'construction'],
        ['facade', 'Façade, ouvertures, toiture', 'DP', 'Fenêtres, enduit, couverture, panneaux solaires.', 'aspect'],
    ];

    /** Couloirs « vous / moi » pour chaque étape. */
    public const LANES = [
        ['Devis', ['Vous m\'appelez et décrivez votre projet', 'Cinq minutes au téléphone'], ['Je vérifie la démarche et vous envoie le devis', 'Prix fixe, par e-mail']],
        ['Signature', ['Vous signez en ligne', 'Un clic, sans code SMS'], ['Je vous demande photos et mesures', 'Liste simple, au cas par cas']],
        ['Plans', ['Vous relisez et validez', 'Deux versions comprises'], ['Je dessine toutes les pièces', 'Situation, masse, coupe, façades, insertion']],
        ['Dépôt', ['Vous réglez la facture', 'Virement ou chèque'], ['Je dépose le dossier en votre nom', 'En ligne ou en mairie, récépissé envoyé']],
        ['Décision', [null, 'Rien à faire, vous suivez en ligne'], ['Je suis l\'instruction et réponds à la mairie', 'Jusqu\'à l\'arrêté']],
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
        ['Déclaration préalable ou permis de construire : comment savoir ?', "Tout dépend de la surface créée (emprise au sol ou surface de plancher). En dessous de 5 m², aucune démarche en général. Entre 5 et 20 m², c'est une déclaration préalable. Au-delà de 20 m², c'est un permis de construire, sauf pour une extension en zone urbaine d'un PLU, où la déclaration préalable va jusqu'à 40 m². Dites-moi votre projet au téléphone : je vous indique la bonne démarche."],
        ['Combien de temps dure l\'instruction ?', "Un mois pour une déclaration préalable, deux mois pour le permis d'une maison individuelle ou de son extension, trois mois pour les autres permis. Comptez un mois de plus si le terrain est proche d'un monument historique (avis de l'Architecte des Bâtiments de France). Le délai démarre quand le dossier est complet."],
        ['Faut-il un architecte ?', "Seulement si la surface de plancher totale (existant compris) dépasse 150 m². En dessous, je prépare tout le dossier. Au-delà, je travaille avec un architecte partenaire qui signe le projet."],
        ['Devez-vous vous déplacer chez moi ?', "Dans la plupart des cas, non : vos photos, quelques mesures et le cadastre suffisent. Si un relevé sur place est nécessaire, je vous le dis avant le devis."],
        ['Que se passe-t-il si la mairie demande des pièces en plus ?', "Je reprends les pièces demandées et je dépose les compléments dans le délai imparti. Si la demande vient d'une erreur de ma part, c'est sans frais."],
        ['Le prix comprend-il la taxe d\'aménagement ?', "Non. La taxe d'aménagement et la redevance d'archéologie préventive sont des taxes dues à l'administration après l'accord. Je peux vous en donner une estimation."],
        ['L\'étude thermique RE2020 est-elle comprise ?', "Non. Pour une maison neuve, l'attestation RE2020 est exigée au dépôt du permis. Elle est réalisée à part par un bureau d'études thermiques."],
        ['Comment payer ?', "Par virement ou par chèque. Les coordonnées figurent sur la facture et dans votre espace client. Le dossier est déposé en mairie après règlement."],
        ['Une fois l\'accord obtenu, que dois-je faire ?', "Affichez le panneau sur le terrain, visible de la rue, pendant toute la durée des travaux. Les voisins disposent de deux mois de recours à partir du premier jour d'affichage. L'autorisation est valable trois ans et peut être prolongée deux fois d'un an."],
    ];
}
