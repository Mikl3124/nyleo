<?php

namespace App\Support;

/** Textes du site vitrine nyleo.fr (prestations, secteurs, FAQ). */
class SiteContent
{
    /**
     * Notes locales par commune (code INSEE => texte), affichées sur la page de la ville.
     * À compléter avec ce que Mickaël connaît du terrain : document d'urbanisme, secteur
     * protégé, risques, projets déjà réalisés. Exemple : '24543' => "…".
     */
    public const TOWN_NOTES = [];

    public const DEPARTMENTS = [
        'dordogne' => [
            'name' => 'Dordogne', 'code' => '24', 'in' => 'en Dordogne', 'of' => 'de la Dordogne', 'all' => 'toute la Dordogne',
            'intro' => "Périgord blanc, vert, pourpre ou noir : chaque commune a ses règles (PLU, carte communale, RNU) et, souvent, un périmètre de monument historique. Je prépare des dossiers adaptés aux exigences locales, avec une attention particulière à l'insertion dans le paysage.",
            'tips' => [
                ['Beaucoup de secteurs protégés', "Monuments historiques, vallée de la Vézère, site patrimonial de Sarlat : près d'un monument, l'avis de l'Architecte des Bâtiments de France ajoute un mois d'instruction et des exigences sur les matériaux."],
                ['Des PLU intercommunaux', "De plus en plus de communautés de communes ont un PLU commun à toutes leurs communes. Je vérifie le règlement de la zone de votre parcelle."],
                ['Assainissement individuel', "Hors du tout-à-l'égout, si le projet crée ou modifie l'assainissement (maison neuve, nouvelle salle d'eau…), le permis de construire doit contenir l'attestation du SPANC. Je vous indique la démarche."],
            ],
            'towns' => ['Périgueux', 'Bergerac', 'Sarlat-la-Canéda', 'Ribérac', 'Mussidan', 'Sourzac', 'Neuvic', 'Saint-Astier', 'Montpon-Ménestérol', 'Nontron', 'Terrasson-Lavilledieu', 'Brantôme', 'Vergt', 'Lalinde', 'Eymet', 'Excideuil'],
        ],
        'gironde' => [
            'name' => 'Gironde', 'code' => '33', 'in' => 'en Gironde', 'of' => 'de la Gironde', 'all' => 'toute la Gironde',
            'intro' => "De la métropole bordelaise au Libournais, du Bassin d'Arcachon à l'Entre-deux-Mers : les PLU y sont souvent exigeants (emprise au sol, retraits, aspect des façades). Je construis votre dossier pour qu'il réponde au règlement de votre zone dès le premier dépôt.",
            'tips' => [
                ['PLU de Bordeaux Métropole', "Les 28 communes de la métropole partagent un même PLU, aux règles précises sur l'emprise au sol, les hauteurs et les retraits."],
                ['Zones à risques', "Bords de Garonne et de Dordogne (inondation), forêt des Landes (feux de forêt), littoral : des règles particulières peuvent s'ajouter. Je les intègre au dossier."],
                ['Assainissement individuel', "Hors du tout-à-l'égout, si le projet crée ou modifie l'assainissement (maison neuve, nouvelle salle d'eau…), le permis de construire doit contenir l'attestation du SPANC. Je vous indique la démarche."],
            ],
            'towns' => ['Bordeaux', 'Libourne', 'Saint-Émilion', 'Coutras', 'Sainte-Foy-la-Grande', 'Castillon-la-Bataille', 'Mérignac', 'Pessac', 'Le Bouscat', 'Arcachon', 'La Teste-de-Buch', 'Blaye', 'Langon', 'Créon', 'Saint-André-de-Cubzac', 'Lesparre-Médoc'],
        ],
        'charente' => [
            'name' => 'Charente', 'code' => '16', 'in' => 'en Charente', 'of' => 'de la Charente', 'all' => 'toute la Charente',
            'intro' => "De l'Angoumois au Cognaçais, de la Charente limousine au Sud-Charente : PLU intercommunaux, cartes communales ou règlement national selon les communes. Je vérifie les règles de votre zone et je prépare un dossier prêt à instruire, à distance depuis le bureau de Sourzac.",
            'tips' => [
                ['Vallée de la Charente', "Le long du fleuve et de ses affluents, des plans de prévention du risque inondation fixent la cote des planchers et limitent les extensions. Chaque page de ville indique les plans qui s'appliquent."],
                ['Patrimoine des bourgs', "Églises romanes, logis et châteaux : près d'un monument historique, l'Architecte des Bâtiments de France donne son avis et le délai d'instruction s'allonge d'un mois."],
                ['Sols argileux', "Une grande partie du département est exposée au retrait-gonflement des argiles. À la vente d'un terrain constructible en zone d'exposition moyenne ou forte, une étude de sol est obligatoire : elle sert aussi à adapter les fondations."],
                ['Assainissement individuel', "Hors du tout-à-l'égout, si le projet crée ou modifie l'assainissement (maison neuve, nouvelle salle d'eau…), le permis de construire doit contenir l'attestation du SPANC. Je vous indique la démarche."],
            ],
        ],
        'charente-maritime' => [
            'name' => 'Charente-Maritime', 'code' => '17', 'in' => 'en Charente-Maritime', 'of' => 'de la Charente-Maritime', 'all' => 'toute la Charente-Maritime',
            'intro' => "Du littoral et des îles à la Saintonge et à la Haute-Saintonge : les règles changent beaucoup d'une commune à l'autre, entre loi Littoral, risques de submersion et secteurs protégés. Je les vérifie pour votre parcelle avant de dessiner.",
            'tips' => [
                ['Communes littorales', "La loi Littoral limite les constructions nouvelles hors des villages et des agglomérations, et les interdit en principe dans la bande des 100 mètres hors espaces urbanisés. Je vérifie la situation de votre terrain."],
                ['Submersion marine et inondation', "Sur la côte et dans les marais, des plans de prévention des risques littoraux imposent souvent un plancher surélevé ou des extensions limitées."],
                ['Sites protégés', "Sites classés ou inscrits, abords de monuments historiques : l'Architecte des Bâtiments de France donne son avis, et le délai d'instruction s'allonge d'un mois."],
                ['Assainissement individuel', "Hors du tout-à-l'égout, si le projet crée ou modifie l'assainissement (maison neuve, nouvelle salle d'eau…), le permis de construire doit contenir l'attestation du SPANC. Je vous indique la démarche."],
            ],
        ],
        'haute-vienne' => [
            'name' => 'Haute-Vienne', 'code' => '87', 'in' => 'en Haute-Vienne', 'of' => 'de la Haute-Vienne', 'all' => 'toute la Haute-Vienne',
            'intro' => "De Limoges aux monts d'Ambazac, des monts de Blond à la vallée de la Vienne : je prépare vos plans selon le document d'urbanisme de votre commune et je dépose le dossier en mairie en votre nom.",
            'tips' => [
                ['Parc naturel régional Périgord-Limousin', "À l'ouest du département, le parc est partagé avec la Dordogne. Sa charte n'impose pas de démarche en plus, mais les PLU des communes en reprennent souvent les orientations sur les paysages et les matériaux."],
                ['Radon', "Sur le socle granitique, une grande partie des communes est classée en zone 3 pour le radon. C'est une information à connaître pour une maison neuve : la ventilation et l'étanchéité du plancher bas y répondent."],
                ['Patrimoine', "Près d'un monument historique, l'Architecte des Bâtiments de France donne son avis et le délai d'instruction s'allonge d'un mois."],
                ['Assainissement individuel', "Hors du tout-à-l'égout, si le projet crée ou modifie l'assainissement (maison neuve, nouvelle salle d'eau…), le permis de construire doit contenir l'attestation du SPANC. Je vous indique la démarche."],
            ],
        ],
        'correze' => [
            'name' => 'Corrèze', 'code' => '19', 'in' => 'en Corrèze', 'of' => 'de la Corrèze', 'all' => 'toute la Corrèze',
            'intro' => "Du bassin de Brive au plateau de Millevaches, de Tulle à la Xaintrie : terrains en pente, villages patrimoniaux et règles locales variées. Je prépare les plans et les coupes qui montrent clairement votre projet dans son terrain.",
            'tips' => [
                ['Terrains en pente', "Sur les terrains pentus, le plan en coupe et l'insertion paysagère comptent beaucoup pour l'instructeur : je les dessine avec les niveaux du terrain avant et après."],
                ['Parc naturel régional de Millevaches', "Sur le plateau, les PLU reprennent souvent les orientations du parc sur les volumes, les couleurs et les matériaux."],
                ['Radon', "Sur les terrains granitiques, beaucoup de communes sont classées en zone 3 pour le radon : à connaître pour une maison neuve."],
                ['Assainissement individuel', "Hors du tout-à-l'égout, si le projet crée ou modifie l'assainissement (maison neuve, nouvelle salle d'eau…), le permis de construire doit contenir l'attestation du SPANC. Je vous indique la démarche."],
            ],
        ],
        'lot' => [
            'name' => 'Lot', 'code' => '46', 'in' => 'dans le Lot', 'of' => 'du Lot', 'all' => 'tout le Lot',
            'intro' => "Du Quercy blanc aux causses, de la vallée du Lot à celle de la Dordogne : un patrimoine très présent et des paysages surveillés. Je prépare des dossiers soignés, attentifs à l'insertion du projet dans son site.",
            'tips' => [
                ['Parc naturel régional des Causses du Quercy', "Une grande partie du département est dans le parc. Les PLU y encadrent souvent l'aspect des constructions : pierre, enduits, toitures."],
                ['Beaucoup de secteurs protégés', "Monuments historiques, sites classés et sites patrimoniaux remarquables : l'Architecte des Bâtiments de France donne souvent son avis, et le délai d'instruction s'allonge d'un mois."],
                ['Vallées inondables', "Le long du Lot, du Célé et de la Dordogne, des plans de prévention du risque inondation fixent des règles particulières."],
                ['Assainissement individuel', "Hors du tout-à-l'égout, si le projet crée ou modifie l'assainissement (maison neuve, nouvelle salle d'eau…), le permis de construire doit contenir l'attestation du SPANC. Je vous indique la démarche."],
            ],
        ],
        'lot-et-garonne' => [
            'name' => 'Lot-et-Garonne', 'code' => '47', 'in' => 'en Lot-et-Garonne', 'of' => 'du Lot-et-Garonne', 'all' => 'tout le Lot-et-Garonne',
            'intro' => "De l'Agenais au Marmandais, du Villeneuvois aux Landes de Gascogne : vallées inondables, sols argileux et bastides. Je vérifie les règles de votre commune et je construis un dossier complet dès le premier dépôt.",
            'tips' => [
                ['Garonne et Lot', "Dans les vallées, des plans de prévention du risque inondation fixent la cote des planchers et limitent les extensions. Chaque page de ville indique les plans qui s'appliquent."],
                ['Sols argileux', "Une grande partie du département est exposée au retrait-gonflement des argiles. À la vente d'un terrain constructible en zone d'exposition moyenne ou forte, une étude de sol est obligatoire : elle sert aussi à adapter les fondations."],
                ['Bastides et monuments', "Dans les abords d'un monument historique, l'Architecte des Bâtiments de France donne son avis et le délai d'instruction s'allonge d'un mois."],
                ['Assainissement individuel', "Hors du tout-à-l'égout, si le projet crée ou modifie l'assainissement (maison neuve, nouvelle salle d'eau…), le permis de construire doit contenir l'attestation du SPANC. Je vous indique la démarche."],
            ],
        ],
    ];

    /** Projets courants : clé (icône), titre, démarche, phrase courte, type pour le simulateur, guide (ProjectGuides). */
    public const PROJECTS = [
        ['veranda', 'Véranda', 'DP ou PC', "Accolée à la maison, la surface créée décide de la démarche.", 'extension', 'veranda'],
        ['extension', 'Extension, garage accolé', 'DP ou PC', "Accolée à la maison : jusqu'à 40 m² en déclaration préalable en zone urbaine d'un PLU.", 'extension', 'extension-maison'],
        ['maison', 'Maison individuelle', 'PC', 'Plans complets du permis, notice et insertion paysagère.', 'maison', 'maison-individuelle'],
        ['piscine', 'Piscine', 'DP', 'Bassin de plus de 10 m² et jusqu\'à 100 m², non couvert ou abri bas.', 'piscine', 'piscine'],
        ['pergola', 'Pergola, carport', 'DP ou PC', "De 5 à 20 m², déclaration préalable. Au-delà, permis de construire.", 'construction', 'abri-de-jardin-carport-pergola'],
        ['facade', 'Façade, ouvertures, toiture', 'DP', 'Fenêtres, enduit, couverture, panneaux solaires.', 'aspect', 'facade-fenetres-toiture'],
    ];

    /** Couloirs « vous / moi » pour chaque étape. */
    public const LANES = [
        ['Devis', ['Vous m\'appelez et décrivez votre projet', 'Cinq minutes au téléphone'], ['Je vérifie la démarche et vous envoie le devis', 'Clair et détaillé, par e-mail']],
        ['Signature', ['Vous signez en ligne', 'Un clic, sans code SMS'], ['Je vous demande photos et mesures', 'Liste simple, au cas par cas']],
        ['Plans', ['Vous relisez et validez', 'Deux versions comprises'], ['Je dessine toutes les pièces', 'Situation, masse, coupe, façades, insertion']],
        ['Dépôt', ['Vous réglez la facture', 'Virement ou chèque'], ['Je dépose le dossier en votre nom', 'En ligne ou en mairie, récépissé envoyé']],
        ['Décision', [null, 'Rien à faire, vous suivez en ligne'], ['Je suis l\'instruction et réponds à la mairie', 'Jusqu\'à l\'arrêté']],
    ];

    public const STEPS = [
        ['Un appel pour comprendre votre projet', "On en parle simplement au téléphone. Je vérifie quelle démarche s'applique et je vous envoie un devis clair."],
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
        ['Faut-il un architecte ?', "Oui si la surface de plancher ou l'emprise au sol de la maison (existant compris, garage non compté) dépasse 150 m², ou si le permis est demandé par une société (SCI…). Sinon, je prépare tout le dossier. Dans ces cas, je travaille avec un architecte partenaire qui signe le projet."],
        ['Devez-vous vous déplacer chez moi ?', "Dans la plupart des cas, non : vos photos, quelques mesures et le cadastre suffisent. Si un relevé sur place est nécessaire, je vous le dis avant le devis."],
        ['Que se passe-t-il si la mairie demande des pièces en plus ?', "Je reprends les pièces demandées et je dépose les compléments dans le délai imparti. Si la demande vient d'une erreur de ma part, c'est sans frais."],
        ['Quelles taxes prévoir après l\'accord ?', "La taxe d'aménagement et la redevance d'archéologie préventive sont des taxes dues à l'administration après l'accord. Je peux vous en donner une estimation."],
        ['L\'étude thermique RE2020 est-elle comprise ?', "Non. Pour une maison neuve, l'attestation RE2020 est exigée au dépôt du permis. Elle est réalisée à part par un bureau d'études thermiques."],
        ['Comment payer ?', "Par virement ou par chèque. Les coordonnées figurent sur la facture et dans votre espace client. Le dossier est déposé en mairie après règlement."],
        ['Une fois l\'accord obtenu, que dois-je faire ?', "Affichez le panneau sur le terrain, visible de la rue, pendant toute la durée des travaux. Les voisins disposent de deux mois de recours à partir du premier jour d'affichage. L'autorisation est valable trois ans et peut être prolongée deux fois d'un an."],
    ];
}
