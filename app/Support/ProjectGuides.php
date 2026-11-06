<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Guides « quelle démarche pour mon projet » (véranda, piscine, abri…), pages /projets/{slug}.
 *
 * Elles répondent aux recherches des particuliers (« permis véranda 30 m² », « piscine déclaration
 * préalable »…) et renvoient vers les pages DP, PC et villes. Règles du Code de l'urbanisme
 * en vigueur en 2026, formulées avec prudence : le règlement de la zone peut être plus strict.
 * Ne jamais inventer de chiffres ni d'avis clients.
 */
class ProjectGuides
{
    /**
     * slug => [
     *   name, icon, line (phrase des cartes), sim (type du simulateur), proc (DP, PC ou DP ou PC), title (balise title), description (meta),
     *   h1, lead, rules: [[cas, démarche]], sections: [[titre, [paragraphes]]], faq: [[question, réponse]]
     * ]
     */
    public const GUIDES = [
        'veranda' => [
            'name' => 'Véranda', 'icon' => 'veranda', 'sim' => 'extension', 'proc' => 'DP ou PC',
            'line' => "Accolée à la maison, la surface créée décide de la démarche.",
            'title' => 'Véranda : déclaration préalable ou permis de construire ?',
            'description' => "Véranda de 5 à 20 m², jusqu'à 40 m² en zone urbaine d'un PLU, ou plus : quelle démarche, quelles pièces, quel délai. Dossier déposé pour vous en Dordogne et dans les départements voisins.",
            'h1' => 'Véranda : <span class="hl">déclaration préalable</span> ou permis ?',
            'lead' => "Une véranda accolée à la maison est une extension : c'est sa surface qui décide de la démarche. Je dessine les plans, je remplis le formulaire et je dépose le dossier en mairie à votre place.",
            'rules' => [
                ['Moins de 5 m²', 'Pas de démarche en général (déclaration en secteur protégé)'],
                ['De 5 à 20 m²', 'Déclaration préalable'],
                ["De 20 à 40 m² en zone urbaine (U) d'un PLU", 'Déclaration préalable, si la maison ne dépasse pas 150 m² au total'],
                ['Au-delà, ou hors zone U', 'Permis de construire'],
            ],
            'sections' => [
                ['Quelle surface compte ?', [
                    "On regarde la plus grande des deux surfaces créées : l'emprise au sol (l'ombre de la véranda au sol, murs compris) et la surface de plancher (la surface close et couverte de plus de 1,80 m de haut). Pour une véranda, les deux sont en général très proches.",
                    "Si la maison dépasse 150 m² une fois la véranda construite, le permis de construire et la signature d'un architecte deviennent obligatoires, même pour une petite véranda. Dans ce cas, je travaille avec un architecte partenaire.",
                ]],
                ['Ce que la mairie regarde', [
                    "Le règlement de la zone fixe les distances aux limites du terrain, la hauteur, l'emprise au sol maximale et souvent l'aspect : couleur des menuiseries, pente et matériau de la toiture. Je vérifie ces règles avant de dessiner, pour éviter un refus.",
                    "Près d'un monument historique ou dans un site patrimonial remarquable, l'Architecte des Bâtiments de France donne son avis : le délai passe à deux mois pour une déclaration préalable, et il peut demander des profils fins ou une teinte précise.",
                ]],
                ['Les pièces du dossier', [
                    "Plan de situation, plan de masse coté, plan en coupe, façades avant et après, insertion paysagère et photos. Pour une véranda, le plan des façades doit montrer clairement les montants et la toiture : c'est ce que l'instructeur compare au règlement.",
                    "Je travaille aussi pour les vérandalistes qui me confient le dossier de leurs clients : il suffit de me transmettre le plan du fabricant et quelques photos.",
                ]],
            ],
            'faq' => [
                ['Une véranda ouverte compte-t-elle ?', "Oui pour l'emprise au sol, dès qu'elle a une toiture. Elle ne crée pas de surface de plancher si elle n'est pas close, mais c'est la plus grande des deux surfaces qui décide de la démarche."],
                ['La véranda entre-t-elle dans la taxe d\'aménagement ?', "Oui si elle est close et couverte : sa surface entre dans le calcul de la taxe d'aménagement. Je peux vous en donner une estimation avec le devis."],
                ['Combien de temps faut-il compter ?', "Un mois d'instruction pour une déclaration préalable, deux mois pour un permis, un mois de plus avec l'avis de l'Architecte des Bâtiments de France. Ajoutez une à deux semaines pour les plans."],
            ],
        ],
        'extension-maison' => [
            'name' => 'Extension, garage accolé', 'icon' => 'extension', 'sim' => 'extension', 'proc' => 'DP ou PC',
            'line' => "Jusqu'à 40 m² en déclaration préalable en zone urbaine d'un PLU.",
            'title' => "Extension de maison : quelle autorisation d'urbanisme ?",
            'description' => "Agrandir sa maison ou accoler un garage : seuils de 20 et 40 m², règle des 150 m², pièces et délais. Plans et dossier déposés pour vous en Dordogne et dans les départements voisins.",
            'h1' => 'Agrandir votre maison : <span class="hl">la bonne démarche</span>',
            'lead' => "Pièce en plus, garage accolé, surélévation : je vous dis au téléphone quelle autorisation s'applique, puis je dessine et je dépose le dossier en votre nom.",
            'rules' => [
                ['Moins de 5 m²', 'Pas de démarche en général (déclaration en secteur protégé)'],
                ['De 5 à 20 m²', 'Déclaration préalable'],
                ["De 20 à 40 m² en zone urbaine (U) d'un PLU", 'Déclaration préalable, si la maison ne dépasse pas 150 m² au total'],
                ['Au-delà, ou hors zone U', 'Permis de construire'],
            ],
            'sections' => [
                ['Les deux surfaces à connaître', [
                    "L'emprise au sol est la projection de la construction au sol, débords de toit exclus. La surface de plancher est la surface close et couverte de plus de 1,80 m de haut, mesurée à l'intérieur des murs. C'est la plus grande des deux qui sert à choisir entre déclaration et permis.",
                    "Un garage accolé compte comme une extension. Un garage séparé de la maison est une construction neuve : le seuil des 40 m² ne s'applique pas, il faut un permis au-delà de 20 m².",
                ]],
                ['La règle des 150 m²', [
                    "Si la surface de plancher ou l'emprise au sol de la maison dépasse 150 m² après les travaux, l'extension passe en permis de construire et un architecte doit signer le projet. Je travaille alors avec un architecte partenaire, et je reste votre interlocuteur.",
                ]],
                ['Sans PLU, la limite reste à 20 m²', [
                    "Dans les communes en carte communale ou au règlement national d'urbanisme (RNU), nombreuses en Dordogne, la déclaration préalable s'arrête à 20 m². Je vérifie le document d'urbanisme de votre commune avant le devis : chaque page de ville du site l'indique quand il est publié.",
                ]],
            ],
            'faq' => [
                ['Une surélévation demande-t-elle un permis ?', "Elle crée de la surface de plancher et modifie les façades : déclaration préalable ou permis selon la surface créée, avec les mêmes seuils qu'une extension. La hauteur maximale du règlement de zone est souvent la contrainte principale."],
                ['Aménager un garage en pièce de vie ?', "Si les travaux modifient la façade (porte de garage remplacée par une fenêtre), une déclaration préalable suffit en général. Si la surface créée dépasse les seuils, c'est un permis."],
                ['Faut-il un relevé de la maison existante ?', "Vos photos et quelques mesures suffisent dans la plupart des cas. Si un relevé sur place est nécessaire, je vous le dis avant le devis."],
            ],
        ],
        'piscine' => [
            'name' => 'Piscine', 'icon' => 'piscine', 'sim' => 'piscine', 'proc' => 'DP',
            'line' => "Bassin de plus de 10 m² et jusqu'à 100 m² : déclaration préalable.",
            'title' => 'Piscine : faut-il une déclaration préalable ?',
            'description' => "Piscine de plus de 10 m², jusqu'à 100 m², abri de plus de 1,80 m : quelle démarche, quelles pièces, quel délai. Dossier de déclaration préalable déposé pour vous en Dordogne et départements voisins.",
            'h1' => 'Piscine : <span class="hl">déclaration préalable</span> ou rien du tout ?',
            'lead' => "Pour une piscine enterrée ou semi-enterrée, c'est la surface du bassin et la hauteur de l'abri qui comptent. Je prépare le dossier et je le dépose en mairie pour vous.",
            'rules' => [
                ['Bassin de 10 m² ou moins', 'Pas de démarche en général (déclaration en secteur protégé)'],
                ['Bassin de plus de 10 m² et jusqu\'à 100 m², sans abri ou abri de moins de 1,80 m', 'Déclaration préalable'],
                ['Bassin de plus de 100 m², ou abri de 1,80 m et plus', 'Permis de construire'],
                ['Piscine hors-sol installée moins de 3 mois par an', 'Pas de démarche en général'],
            ],
            'sections' => [
                ['Ce que la mairie vérifie', [
                    "La distance du bassin aux limites du terrain, souvent fixée par le règlement de la zone, et parfois l'aspect des abords (plage, local technique, clôture). Le local technique, s'il dépasse 5 m², s'ajoute à la déclaration.",
                    "Près d'un monument historique, l'Architecte des Bâtiments de France peut demander une couleur de liner discrète : le délai passe de un à deux mois.",
                ]],
                ['Les pièces du dossier', [
                    "Plan de situation, plan de masse avec les cotes du bassin et les distances aux limites, plan en coupe montrant la profondeur et le terrain, insertion paysagère et photos. Je travaille aussi avec les pisciniers qui me confient le dossier de leurs clients.",
                ]],
                ['Après l\'accord', [
                    "Affichez le panneau sur le terrain pendant toute la durée des travaux. Une piscine enterrée doit être équipée d'un dispositif de sécurité normalisé (barrière, couverture, abri ou alarme). La taxe d'aménagement s'applique aux bassins : je peux vous en donner une estimation.",
                ]],
            ],
            'faq' => [
                ['Comment se mesure la surface du bassin ?', "On compte la surface intérieure du bassin, sans la plage autour. Un bassin de 8 × 4 m fait 32 m² : c'est une déclaration préalable."],
                ['Un spa ou une piscine hors-sol ?', "Un spa ou une piscine hors-sol de 10 m² ou moins ne demande en général aucune démarche. Au-delà, et s'ils restent installés plus de trois mois par an, les mêmes règles que pour une piscine enterrée s'appliquent."],
                ['Quand commencer les démarches ?', "Comptez un mois d'instruction et une semaine pour les plans. Pour une baignade au printemps, mieux vaut déposer le dossier en début d'année."],
            ],
        ],
        'abri-de-jardin-carport-pergola' => [
            'name' => 'Abri de jardin, carport, pergola', 'icon' => 'pergola', 'sim' => 'construction', 'proc' => 'DP',
            'line' => "De 5 à 20 m², déclaration préalable. Au-delà, permis.",
            'title' => 'Abri de jardin, carport, pergola : quelle démarche ?',
            'description' => "Abri de jardin, carport, pergola couverte ou garage séparé : seuils de 5 et 20 m², pièces, délais. Déclaration préalable préparée et déposée pour vous en Dordogne et dans les départements voisins.",
            'h1' => 'Abri, carport, pergola : <span class="hl">la règle des 5 et 20 m²</span>',
            'lead' => "Une construction séparée de la maison suit une règle simple, fondée sur son emprise au sol. Je vous confirme la démarche, je dessine les plans et je dépose la déclaration.",
            'rules' => [
                ['Moins de 5 m²', 'Pas de démarche en général (déclaration en secteur protégé)'],
                ['De 5 à 20 m²', 'Déclaration préalable'],
                ['Plus de 20 m²', 'Permis de construire'],
            ],
            'sections' => [
                ['Le carport et la pergola couverte comptent', [
                    "L'emprise au sol est l'ombre portée de la toiture : un carport de 3 × 5 m crée 15 m² d'emprise au sol, même sans murs, et demande une déclaration préalable. Une pergola couverte (lames, polycarbonate, toile fixe) est traitée de la même façon.",
                    "Une pergola sans toiture, faite de simples poutres, ne crée en général pas d'emprise au sol. La mairie apprécie au cas par cas, surtout pour les pergolas bioclimatiques à lames orientables : je vous dis comment la présenter.",
                ]],
                ['Les points qui bloquent le plus souvent', [
                    "La distance aux limites du terrain, la hauteur maximale en limite et l'aspect (bardage, couleur, toiture) sont fixés par le règlement de la zone. Un abri en limite séparative est souvent possible, mais avec une hauteur réduite.",
                    "En secteur protégé (abords d'un monument historique, site patrimonial remarquable), une déclaration peut être exigée même en dessous de 5 m².",
                ]],
            ],
            'faq' => [
                ['Un abri de jardin en kit demande-t-il une déclaration ?', "Oui dès 5 m² d'emprise au sol, même démontable, s'il reste installé plus de trois mois. Le plan du fabricant m'aide à préparer le dossier."],
                ['Plusieurs petits abris de moins de 5 m² ?', "Chaque construction compte, mais la mairie peut les additionner s'ils forment un même projet. Mieux vaut me décrire l'ensemble."],
                ['Le carport accolé à la maison ?', "Accolé, il devient une extension : le seuil de 40 m² en zone urbaine d'un PLU peut alors s'appliquer. Voir la page extension."],
            ],
        ],
        'maison-individuelle' => [
            'name' => 'Maison individuelle', 'icon' => 'maison', 'sim' => 'maison', 'proc' => 'PC',
            'line' => "Plans complets du permis, notice et insertion paysagère.",
            'title' => 'Permis de construire de maison individuelle : plans et dossier',
            'description' => "Plans du permis de construire de votre maison jusqu'à 150 m² : plan de masse, coupes, façades, notice, insertion, CERFA 13406. Dépôt et suivi en Dordogne et dans les départements voisins.",
            'h1' => 'Votre maison neuve : <span class="hl">les plans du permis</span>',
            'lead' => "Je dessine les plans du permis de construire de votre maison, je rédige la notice et je dépose le dossier en mairie en votre nom, jusqu'à la décision.",
            'rules' => [
                ['Maison neuve jusqu\'à 150 m²', 'Permis de construire, sans architecte obligatoire pour un particulier'],
                ['Plus de 150 m² de surface de plancher ou d\'emprise au sol', 'Permis de construire signé par un architecte (mon architecte partenaire)'],
                ['Projet porté par une société (SCI…)', 'Architecte obligatoire, quelle que soit la surface'],
            ],
            'sections' => [
                ['Avant d\'acheter le terrain', [
                    "Vérifiez que le terrain est constructible : zone U ou AU d'un PLU, zone constructible d'une carte communale, ou partie déjà urbanisée de la commune au règlement national d'urbanisme. En cas de doute, un certificat d'urbanisme opérationnel donne une réponse écrite de la mairie.",
                    "Hors du tout-à-l'égout, le permis doit contenir l'attestation du SPANC sur votre projet d'assainissement individuel : prévoyez l'étude de sol correspondante.",
                ]],
                ['Ce que contient le dossier', [
                    "Plan de situation, plan de masse avec l'implantation, les réseaux et l'accès, plan en coupe, notice descriptive, plans des façades et des toitures, insertion paysagère, photos proche et lointaine, et le formulaire CERFA 13406.",
                    "Pour une maison neuve, l'attestation RE2020 doit être jointe au dépôt. Elle est réalisée à part par un bureau d'études thermiques, en lien avec mes plans.",
                ]],
                ['Délais', [
                    "Deux mois d'instruction à partir du dossier complet, trois mois avec l'avis de l'Architecte des Bâtiments de France. Comptez deux à quatre semaines pour les plans et vos corrections, avec deux versions comprises.",
                ]],
            ],
            'faq' => [
                ['Travaillez-vous avec les constructeurs ?', "Je dessine les plans du permis pour les particuliers qui font construire avec un maçon ou en auto-construction. Si vous signez un contrat de construction avec un constructeur, c'est en général lui qui dépose le permis."],
                ['Et si ma maison dépasse 150 m² ?', "Le recours à un architecte est obligatoire. Je travaille avec un architecte partenaire qui signe le projet : vous gardez un seul interlocuteur."],
                ['Quelles taxes prévoir pour le permis ?', "La taxe d'aménagement et la redevance d'archéologie préventive sont dues après l'accord, selon la surface et la commune. Je peux vous en donner une estimation avec le devis."],
            ],
        ],
        'facade-fenetres-toiture' => [
            'name' => 'Façade, fenêtres, toiture', 'icon' => 'facade', 'sim' => 'aspect', 'proc' => 'DP',
            'line' => "Ouvertures, menuiseries, enduit, couverture, isolation.",
            'title' => 'Fenêtres, façade, toiture : faut-il une déclaration préalable ?',
            'description' => "Créer une fenêtre, changer les menuiseries, isoler par l'extérieur, refaire la toiture ou l'enduit : quand la déclaration préalable est obligatoire. Dossier déposé pour vous en Dordogne et départements voisins.",
            'h1' => "Modifier l'aspect de la maison : <span class=\"hl\">la déclaration préalable</span>",
            'lead' => "Dès que les travaux changent l'aspect extérieur de la maison, une déclaration préalable est demandée. Je prépare les façades avant et après, et je dépose le dossier.",
            'rules' => [
                ['Créer ou agrandir une ouverture, poser une fenêtre de toit', 'Déclaration préalable'],
                ['Changer les menuiseries pour un autre modèle, matériau ou couleur', 'Déclaration préalable'],
                ["Isolation par l'extérieur, changement de couverture ou de teinte", 'Déclaration préalable'],
                ["Remplacement à l'identique, ravalement simple", "En général pas de démarche, sauf secteur protégé ou décision de la commune"],
            ],
            'sections' => [
                ['Pourquoi la mairie le demande', [
                    "Le règlement de la zone fixe souvent les couleurs d'enduit et de menuiseries, le type de tuiles et la forme des ouvertures. Le dossier montre la façade avant et après : la mairie vérifie que le projet respecte ces règles.",
                    "En Dordogne, beaucoup de communes ont un monument historique : dans ses abords, l'Architecte des Bâtiments de France donne son avis, peut demander du bois plutôt que du PVC ou une teinte précise, et le délai passe à deux mois.",
                ]],
                ['Les pièces du dossier', [
                    "Plan de situation, plans des façades et des toitures avant et après, photos et insertion si l'aspect change beaucoup. Pas besoin de plan de masse détaillé quand rien n'est construit au sol.",
                ]],
            ],
            'faq' => [
                ['Changer des fenêtres en bois par du PVC ?', "Le matériau et souvent l'aspect changent : une déclaration préalable est demandée. Elle est d'autant plus importante en secteur protégé, où le PVC peut être refusé."],
                ['Le ravalement de façade ?', "Un ravalement à l'identique ne demande en général aucune démarche, sauf en secteur protégé ou si la commune a décidé de le soumettre à déclaration. Je vérifie pour votre commune."],
                ['Une isolation par l\'extérieur ?', "Elle épaissit les murs et change l'aspect : déclaration préalable. Le règlement de la zone peut prévoir une dérogation aux distances pour l'isolation."],
            ],
        ],
        'panneaux-solaires' => [
            'name' => 'Panneaux solaires', 'icon' => 'solaire', 'sim' => 'aspect', 'proc' => 'DP',
            'line' => "En toiture, une déclaration préalable est demandée.",
            'title' => 'Panneaux solaires : déclaration préalable en mairie',
            'description' => "Panneaux photovoltaïques en toiture ou au sol : quand une déclaration préalable est obligatoire, quelles pièces fournir, quel délai. Dossier déposé pour vous en Dordogne et dans les départements voisins.",
            'h1' => 'Panneaux solaires : <span class="hl">la déclaration préalable</span>',
            'lead' => "Des panneaux en toiture changent l'aspect de la maison : une déclaration préalable est demandée. Je prépare le dossier pour vous ou pour votre installateur.",
            'rules' => [
                ['Panneaux sur la toiture ou en façade', 'Déclaration préalable'],
                ['Au sol, moins de 3 kWc et moins de 1,80 m de haut', 'Pas de démarche en général (déclaration en secteur protégé)'],
                ['Au sol, au-delà (installation d\'un particulier)', 'Déclaration préalable'],
            ],
            'sections' => [
                ['Ce que la mairie regarde', [
                    "L'implantation sur le toit, la couleur des panneaux et du cadre, l'alignement avec les ouvertures. Le règlement de la zone peut imposer des panneaux noirs, intégrés ou alignés sur le faîtage.",
                    "Dans les abords d'un monument historique, l'Architecte des Bâtiments de France donne son avis : il peut refuser des panneaux visibles depuis le monument, et le délai passe de un à deux mois.",
                ]],
                ['Les pièces du dossier', [
                    "Plan de situation, plan de masse ou plan de toiture avec l'implantation des panneaux, façades et toitures avant et après, photos et insertion. Je peux travailler à partir du plan de calepinage de votre installateur.",
                ]],
            ],
            'faq' => [
                ['L\'installateur peut-il déposer la déclaration ?', "Oui, beaucoup le font. Si le vôtre ne s'en occupe pas, ou si la mairie a demandé des compléments, je prends le dossier en main."],
                ['Faut-il attendre l\'accord avant la pose ?', "Oui : un mois d'instruction, deux en secteur protégé. Le raccordement et l'obligation d'achat demandent en général la preuve de l'autorisation d'urbanisme."],
                ['Une pergola ou un carport solaire ?', "La structure crée de l'emprise au sol : les règles de l'abri et du carport s'appliquent (déclaration de 5 à 20 m², permis au-delà)."],
            ],
        ],
        'cloture-portail' => [
            'name' => 'Clôture, portail', 'icon' => 'cloture', 'sim' => 'cloture', 'proc' => 'DP',
            'line' => "Déclaration seulement si la commune ou un monument l'impose.",
            'title' => 'Clôture et portail : faut-il une déclaration préalable ?',
            'description' => "Clôture, mur de clôture ou portail : quand la déclaration préalable est obligatoire (PLU, délibération, monument historique), quelles pièces fournir. Dossier déposé pour vous en Dordogne et départements voisins.",
            'h1' => 'Clôture et portail : <span class="hl">ça dépend de la commune</span>',
            'lead' => "Une clôture ne demande pas toujours de démarche : tout dépend de votre commune et de la situation du terrain. Je vérifie, puis je prépare la déclaration si elle est exigée.",
            'rules' => [
                ['La commune impose la déclaration (PLU ou délibération)', 'Déclaration préalable'],
                ["Abords d'un monument historique, site patrimonial remarquable, site classé ou inscrit", 'Déclaration préalable'],
                ['Ailleurs', 'Pas de démarche, mais le règlement de la zone s\'applique quand même'],
                ['Clôture agricole ou forestière', 'Pas de démarche'],
            ],
            'sections' => [
                ['Même sans déclaration, des règles s\'appliquent', [
                    "Le règlement de la zone fixe souvent la hauteur maximale, les matériaux (mur bahut, grillage, haie) et parfois la couleur. Une clôture non conforme peut être contestée, même sans déclaration.",
                    "En zone inondable, le plan de prévention des risques peut imposer une clôture transparente à l'eau. Chaque page de ville du site indique les risques connus de la commune.",
                ]],
                ['Les pièces du dossier', [
                    "Plan de situation, plan de masse avec le tracé de la clôture, élévation cotée de la clôture et du portail, photos. Un dossier court, souvent bouclé en quelques jours.",
                ]],
            ],
            'faq' => [
                ['Comment savoir si ma commune l\'impose ?', "Le règlement du PLU ou une délibération du conseil municipal le précise. Donnez-moi votre adresse : je vérifie avant le devis."],
                ['Un mur de soutènement est-il une clôture ?', "Non, un mur de soutènement retient la terre et n'est pas une clôture. S'il est surmonté d'une clôture, c'est la partie clôture qui est concernée."],
                ['Le portail seul ?', "Un portail fait partie de la clôture : mêmes règles. Le déplacer ou l'élargir peut aussi demander l'accord du gestionnaire de la voirie pour l'accès."],
            ],
        ],
        'changement-de-destination' => [
            'name' => 'Grange, changement de destination', 'icon' => 'home', 'sim' => 'extension', 'proc' => 'DP ou PC',
            'line' => "Grange ou séchoir en logement : possible sous conditions.",
            'title' => 'Transformer une grange en maison : changement de destination',
            'description' => "Grange, séchoir, chai ou garage transformé en logement : déclaration préalable ou permis, règles en zone agricole ou naturelle, pièces du dossier. Dordogne et départements voisins.",
            'h1' => 'Grange, séchoir, chai : <span class="hl">le changement de destination</span>',
            'lead' => "Transformer un bâtiment agricole ou un garage en logement change sa destination. Je vérifie que c'est possible sur votre terrain, puis je monte le dossier adapté.",
            'rules' => [
                ['Sans toucher aux murs porteurs ni aux façades', 'Déclaration préalable'],
                ['Avec modification de la structure porteuse ou des façades', 'Permis de construire'],
                ['Surface totale de plus de 150 m² après travaux', 'Permis de construire signé par un architecte'],
            ],
            'sections' => [
                ['En zone agricole ou naturelle', [
                    "En zone A ou N d'un PLU, seul un bâtiment repéré par le PLU comme pouvant changer de destination peut devenir un logement. La commission départementale (CDPENAF en zone agricole, CDNPS en zone naturelle) donne ensuite son avis sur le dossier.",
                    "En Dordogne, beaucoup de granges et de séchoirs à tabac sont dans ce cas. Je vérifie le plan de zonage et la liste des bâtiments repérés avant le devis, pour ne pas monter un dossier voué au refus.",
                ]],
                ['Ce que le dossier doit montrer', [
                    "L'état existant et le projet : façades avant et après, nouvelles ouvertures, plan de masse avec l'accès, les réseaux et l'assainissement. Hors du tout-à-l'égout, l'attestation du SPANC est demandée pour le permis.",
                    "Fermer un hangar ou créer un plancher dans une grange crée de la surface de plancher : elle entre dans la taxe d'aménagement et dans le calcul des 150 m².",
                ]],
            ],
            'faq' => [
                ['Ma grange n\'est pas repérée par le PLU, que faire ?', "Le changement de destination n'est alors en principe pas possible en zone A ou N. La commune peut repérer le bâtiment lors d'une modification ou d'une révision du PLU : il faut en faire la demande à la mairie."],
                ['Transformer un garage en chambre ?', "Le garage fait déjà partie du logement : ce n'est pas un changement de destination. Une déclaration préalable suffit en général si la façade change."],
                ['Un gîte ou une location saisonnière ?', "Un gîte rattaché à votre logement reste en général un logement. Une activité d'hébergement plus importante peut relever d'une autre destination : parlons-en au téléphone."],
            ],
        ],
    ];

    public static function all(): Collection
    {
        return collect(self::GUIDES)->map(fn ($g, $slug) => $g + ['slug' => $slug]);
    }

    public static function find(string $slug): ?array
    {
        return isset(self::GUIDES[$slug]) ? self::GUIDES[$slug] + ['slug' => $slug] : null;
    }

    /** Guides proposés à côté de celui-ci (les autres, dans l'ordre du site). */
    public static function others(string $slug, int $n = 4): Collection
    {
        return self::all()->reject(fn ($g) => $g['slug'] === $slug)->take($n)->values();
    }
}
