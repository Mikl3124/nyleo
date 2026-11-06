<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Textes propres à une démarche (déclaration préalable ou permis de construire) pour une commune.
 *
 * Chaque commune a deux pages : l'une parle des petits travaux sur l'existant (DP), l'autre
 * de la construction neuve et des grandes extensions (PC). Les textes s'appuient sur les
 * données publiques de TownFacts ; une donnée absente n'est jamais devinée.
 */
class TownGuide
{
    public readonly TownFacts $facts;

    public function __construct(public readonly array $town)
    {
        $this->facts = TownFacts::for($town);
    }

    public static function for(array $town): self
    {
        return new self($town);
    }

    private function n(): string
    {
        return $this->town['name'];
    }

    private function a(): string
    {
        return Geo::a($this->town['name']);
    }

    private function de(): string
    {
        return Geo::de($this->town['name']);
    }

    /** Données de patrimoine connues ? (sinon on reste prudent). */
    public function heritageKnown(): bool
    {
        return $this->facts->heritage() !== null;
    }

    public function spr(): bool
    {
        return $this->facts->spr() !== [];
    }

    public function monumentCount(): int
    {
        return $this->facts->monuments()->count();
    }

    /** Le dossier passe-t-il par l'ABF ? true, false, ou null si on ne sait pas. */
    public function abf(): ?bool
    {
        return $this->heritageKnown() ? $this->facts->abfLikely() : null;
    }

    private function hasRisk(string $needle): bool
    {
        foreach ($this->facts->f['risques'] ?? [] as $r) {
            if (stripos(\Illuminate\Support\Str::ascii($r), $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function clay(): bool
    {
        return $this->hasRisk('tassements differentiels') || $this->hasRisk('argile');
    }

    // ---- Déclaration préalable -------------------------------------------------------

    /** Seuil de surface d'une extension en déclaration préalable. */
    public function dpThreshold(): string
    {
        return match (true) {
            $this->facts->hasPlu() => '40 m² en zone U',
            $this->facts->docType() !== null => '20 m²',
            default => '20 ou 40 m²',
        };
    }

    public function dpDelay(): string
    {
        return match ($this->abf()) {
            true => '1 ou 2 mois',
            false => '1 mois',
            null => '1 mois',
        };
    }

    public function dpLead(): string
    {
        $parts = ["Véranda, abri de jardin, piscine, clôture, fenêtres ou ravalement : {$this->a()}, ces travaux passent le plus souvent par une déclaration préalable."];
        if ($this->spr()) {
            $parts[] = "Dans le site patrimonial remarquable, même un petit abri ou un changement de volets doit être déclaré.";
        } elseif ($this->monumentCount()) {
            $parts[] = $this->monumentCount() > 1
                ? "Près de ses {$this->monumentCount()} monuments historiques, l'Architecte des Bâtiments de France donne son avis sur le dossier."
                : "Près de son monument historique, l'Architecte des Bâtiments de France donne son avis sur le dossier.";
        } elseif ($this->facts->hasPlu()) {
            $parts[] = "Avec le PLU, une extension en zone urbaine peut atteindre 40 m² sans permis.";
        } elseif ($this->facts->docType()) {
            $parts[] = "Sans PLU, une extension de plus de 20 m² demande un permis : je vous dis tout de suite ce qui s'applique.";
        }
        $parts[] = "Je dessine les plans, je remplis le CERFA et je dépose le dossier à la mairie {$this->de()}.";

        return implode(' ', $parts);
    }

    /** Paragraphes « les règles qui comptent pour une DP ». */
    public function dpRules(): array
    {
        $n = $this->n();
        $p = [];
        switch ($this->facts->docType()) {
            case 'PLU':
            case 'PSMV':
            case 'PLUi':
                $doc = $this->facts->docType() === 'PLUi' ? 'Le PLU intercommunal' : 'Le PLU de la commune';
                $p[] = "{$doc} classe chaque parcelle en zone U (urbaine), AU (à urbaniser), A (agricole) ou N (naturelle). En zone U, une extension accolée jusqu'à 40 m² se déclare en mairie sans permis, à condition que la maison ne dépasse pas 150 m² une fois agrandie. En zone A ou N, le seuil retombe à 20 m², et le règlement limite souvent les annexes.";
                $p[] = "Le règlement fixe aussi l'aspect : couleur des enduits, pente et matériau des toitures, hauteur des clôtures. Je le lis pour votre zone avant de dessiner, pour que la déclaration soit acceptée du premier coup.";
                break;
            case 'CC':
                $p[] = "{$n} a une carte communale : il n'y a pas de règlement de zone, et ce sont les règles nationales qui s'appliquent. Pour une extension, la déclaration préalable vaut de 5 à 20 m² ; au-delà, il faut un permis de construire.";
                $p[] = "Sans règlement local, la mairie juge surtout l'insertion dans le paysage : volumes simples, matériaux et teintes proches de la maison existante.";
                break;
            case 'RNU':
                $p[] = "{$n} relève du règlement national d'urbanisme. Pour agrandir une maison, la déclaration préalable vaut de 5 à 20 m² de surface créée ; au-delà, c'est un permis de construire. Les travaux sur une maison existante restent possibles partout, même hors des parties urbanisées.";
                $p[] = "La mairie et l'État veillent surtout à l'aspect : un projet qui reprend les matériaux et les teintes de la maison a toutes ses chances.";
                break;
            default:
                $p[] = "Le seuil d'une extension en déclaration préalable dépend du document d'urbanisme : 40 m² en zone urbaine d'un PLU, 20 m² ailleurs. Je vérifie celui qui s'applique {$this->a()} pour votre parcelle avant le devis.";
        }

        return $p;
    }

    /**
     * Les travaux courants et la démarche qui s'applique dans la commune.
     *
     * @return array<int, array{0:string,1:string,2:string}> [travaux, démarche, précision]
     */
    public function dpWorks(): array
    {
        $abf = $this->abf();
        $protected = $abf === true;
        $plu = $this->facts->hasPlu();
        $known = $this->facts->docType() !== null;
        $zone = $this->spr() ? 'le site patrimonial remarquable ou les abords d\'un monument' : 'les abords d\'un monument historique';

        $rows = [];
        $rows[] = ['Extension, véranda de 20 à 40 m²',
            $plu ? 'DP en zone U' : ($known ? 'Permis' : 'DP ou permis'),
            $plu ? 'En zone U du PLU, déclaration préalable si la maison reste sous 150 m². En zone A ou N, permis de construire.'
                : ($known ? "Sans PLU {$this->a()}, au-delà de 20 m² c'est un permis de construire." : 'Déclaration préalable en zone U d\'un PLU, permis ailleurs.')];
        $rows[] = ['Abri de jardin, carport, pergola couverte',
            'DP de 5 à 20 m²',
            $protected ? "Dans {$zone}, même sous 5 m², une déclaration est nécessaire." : 'Sous 5 m², pas de démarche en général. Au-delà de 20 m², permis.'];
        $rows[] = ['Piscine de 10 à 100 m²',
            'DP',
            ($protected ? "Dans {$zone}, même un petit bassin se déclare. " : '').'Couverte d\'un abri de plus de 1,80 m, ou au-delà de 100 m², c\'est un permis.'];
        $rows[] = ['Fenêtres, volets, porte, couleur de façade', 'DP',
            $protected ? 'L\'ABF regarde le matériau et la couleur des menuiseries : je les précise sur les plans.' : 'Toute modification de l\'aspect extérieur se déclare : autre couleur, autre matériau ou autre dessin.'];
        $rows[] = ['Ravalement de façade',
            $protected ? 'DP près des monuments' : 'Selon la commune',
            $protected ? "Obligatoire dans {$zone} ; ailleurs, seulement si la commune l'a décidé." : "Obligatoire seulement si la mairie {$this->de()} l'a décidé, ou près d'un monument : je vérifie."];
        $rows[] = ['Clôture, portail',
            $protected ? 'DP près des monuments' : 'Selon le PLU',
            $protected ? "Obligatoire dans {$zone}, ou si la commune l'impose." : ($plu ? 'Beaucoup de PLU imposent la déclaration et fixent une hauteur maximale.' : 'Nécessaire si la commune l\'a décidé par délibération.')];
        $rows[] = ['Panneaux solaires en toiture', 'DP',
            $protected ? 'Près d\'un monument, l\'ABF peut demander une pose encastrée ou une autre implantation.' : 'Je dessine l\'implantation et l\'insertion depuis la rue.'];
        $rows[] = ['Grange ou garage transformé en logement',
            'DP ou permis',
            'Déclaration préalable si la structure et les façades ne changent pas ; permis dès que l\'on crée des ouvertures ou que l\'on touche aux murs porteurs.'];
        $rows[] = ['Division d\'un terrain pour bâtir',
            $protected ? 'DP ou permis d\'aménager' : 'DP',
            $protected ? "Sans voie commune, déclaration préalable ; dans {$zone}, il faut un permis d'aménager." : 'Sans voie ni espace commun créés, une déclaration préalable suffit.'];

        return $rows;
    }

    /** Patrimoine vu sous l'angle des petits travaux. null si non collecté. */
    public function dpHeritage(): ?array
    {
        return $this->facts->heritage();
    }

    /** Conseils de dossier quand l'ABF donne son avis. */
    public function dpAbfTips(): array
    {
        if ($this->abf() !== true) {
            return [];
        }

        return [
            ['Des façades détaillées', "Sur le plan des façades (DP4), j'indique le matériau, la couleur et le dessin de chaque menuiserie, avant et après travaux."],
            ['Une insertion réaliste', "Le photo-montage (DP6) montre le projet vu depuis la rue et, si possible, avec le monument dans le champ."],
            ['Des références de teintes', "Enduit, tuiles, volets : préciser les teintes dès le dépôt évite une demande de pièces et un mois de retard."],
        ];
    }

    /** Risques vus sous l'angle des petits travaux (abri, piscine, extension). null si non collecté. */
    public function dpRisks(): ?array
    {
        if (! array_key_exists('risques', $this->facts->f)) {
            return null;
        }
        $out = [];
        if ($this->facts->ppr('inond')->isNotEmpty()) {
            $out[] = ['Zone inondable', "Le plan de prévention du risque inondation s'applique aussi aux petits travaux : en zone rouge, un abri, une extension ou une piscine peut être refusé ou limité ; en zone bleue, l'extension doit souvent avoir son plancher au-dessus de la cote de crue. Je reporte la zone sur le plan de masse."];
        } elseif ($this->hasRisk('inond')) {
            $out[] = ['Crues', "La commune connaît des crues sans plan de prévention approuvé. Près d'un cours d'eau, la mairie peut demander de surélever le plancher d'une extension."];
        }
        if ($this->hasRisk('feu') || $this->facts->ppr('feu')->isNotEmpty()) {
            $out[] = ['Près des bois', "Un abri ou un carport en bois près de la forêt doit rester dans une zone débroussaillée. Le débroussaillement autour de la maison est obligatoire dans les secteurs exposés."];
        }
        if ($this->facts->ppr('mouvement')->isNotEmpty()) {
            $out[] = ['Mouvements de terrain', "Un plan de prévention des mouvements de terrain peut encadrer les terrassements et les piscines enterrées selon la zone."];
        }
        if ($this->hasRisk('dunaire')) {
            $out[] = ['Littoral', "Près du rivage, la loi Littoral et les plans de prévention limitent aussi les annexes et les extensions."];
        }

        return $out;
    }

    /** Questions-réponses de la page DP. */
    public function dpFaq(): array
    {
        $n = $this->n();
        $a = $this->a();
        $m = $this->facts->mairie();
        $q = [];

        $q[] = ["Faut-il une déclaration préalable pour une piscine {$a} ?", match ($this->abf()) {
            true => "Oui pour un bassin de 10 à 100 m² non couvert. {$this->a()}, une partie du territoire est protégée : dans ces secteurs, même un bassin plus petit se déclare et l'Architecte des Bâtiments de France donne son avis, ce qui porte le délai à deux mois.",
            default => "Oui pour un bassin de 10 à 100 m² non couvert, ou couvert d'un abri de moins de 1,80 m. En dessous de 10 m², aucune démarche n'est nécessaire en général. Au-delà de 100 m², ou avec un abri haut, il faut un permis de construire.",
        }];

        $q[] = ["Quel est le délai d'une déclaration préalable {$a} ?", match ($this->abf()) {
            true => "Un mois après le dépôt d'un dossier complet, et deux mois si le terrain est dans le site patrimonial remarquable ou dans les abords d'un monument historique. Sans réponse de la mairie dans ce délai, la déclaration est acceptée.",
            false => "Un mois après le dépôt d'un dossier complet. Aucun monument historique n'étant protégé sur la commune, le délai n'est en général pas allongé. Sans réponse de la mairie dans ce délai, la déclaration est acceptée.",
            null => "Un mois après le dépôt d'un dossier complet, deux mois dans les abords d'un monument historique. Sans réponse de la mairie dans ce délai, la déclaration est acceptée.",
        }];

        $q[] = ["Une extension de 30 m² {$a} : déclaration ou permis ?", match (true) {
            $this->facts->hasPlu() => "En zone urbaine (U) du PLU, une déclaration préalable suffit, si la maison ne dépasse pas 150 m² une fois agrandie. En zone A ou N, il faut un permis de construire.",
            $this->facts->docType() !== null => "Un permis de construire. {$n} n'ayant pas de PLU, le seuil de la déclaration préalable est de 20 m².",
            default => "Cela dépend du document d'urbanisme de votre zone : déclaration préalable en zone urbaine d'un PLU, permis de construire ailleurs. Je vérifie pour votre parcelle.",
        }];

        $q[] = ["Où déposer une déclaration préalable {$a} ?", ($m && ! empty($m['adresse'])
            ? "À la mairie {$this->de()}, {$m['adresse']}, {$m['cp']} {$m['ville']}, ou sur son guichet en ligne."
            : "À la mairie {$this->de()}, au guichet ou sur son guichet en ligne.")
            .' Je la dépose en votre nom et je vous envoie le récépissé.'];

        $q[] = ["Que faire après l'accord de la déclaration {$a} ?", "Affichez le panneau sur le terrain, lisible depuis la rue, pendant toute la durée des travaux. Les tiers ont deux mois de recours à partir du premier jour d'affichage. L'accord est valable trois ans, et la fin des travaux se déclare en mairie."];

        return $q;
    }

    // ---- Permis de construire --------------------------------------------------------

    public function pcLead(): string
    {
        $parts = ["Maison neuve, grande extension, garage de plus de 20 m² ou grange transformée en logement : {$this->a()}, ces projets demandent un permis de construire."];
        $parts[] = match ($this->facts->docType()) {
            'PLU', 'PSMV' => "Le PLU communal dit où et comment construire.",
            'PLUi' => "Le PLU intercommunal dit où et comment construire.",
            'CC' => "La carte communale dit où l'on peut construire.",
            'RNU' => "Sans document d'urbanisme, on construit en principe dans les parties déjà urbanisées.",
            default => "Je vérifie d'abord où et comment on peut construire sur votre terrain.",
        };
        $parts[] = "Je prépare toutes les pièces, de la notice à l'insertion paysagère, et je dépose le permis à la mairie {$this->de()}.";

        return implode(' ', $parts);
    }

    public function pcDelay(): string
    {
        return $this->abf() === true ? '2 ou 3 mois' : '2 mois';
    }

    /** Patrimoine vu sous l'angle d'une construction neuve. null si non collecté. */
    public function pcHeritage(): ?string
    {
        if (! $this->heritageKnown()) {
            return null;
        }
        $mh = $this->monumentCount();
        if ($this->spr()) {
            return "Dans le site patrimonial remarquable {$this->de()}, un permis n'est accordé qu'avec l'accord de l'Architecte des Bâtiments de France. Il peut imposer l'implantation, la volumétrie, la pente des toits et les matériaux. Le délai passe de deux à trois mois pour une maison.";
        }
        if ($mh) {
            return ($mh > 1 ? "{$this->n()} compte {$mh} monuments historiques." : "{$this->n()} compte un monument historique.")
                .' Pour un terrain situé dans '.($mh > 1 ? 'leurs' : 'ses')." abords, l'Architecte des Bâtiments de France donne son avis sur le permis : comptez trois mois d'instruction au lieu de deux pour une maison, et un projet soigné dans ses volumes et ses matériaux.";
        }

        return "Aucun monument historique n'est protégé sur la commune : un permis de maison y est instruit en deux mois, sauf si les abords d'un monument voisin débordent sur votre terrain.";
    }

    /** Pièces et études à prévoir en plus des plans, selon la commune. */
    public function pcExtras(): array
    {
        $out = [
            ['Attestation RE2020', "Pour une maison neuve, elle se joint au dépôt du permis. Elle est réalisée par un bureau d'études thermiques ; je vous indique quand la demander."],
        ];
        if ($this->clay()) {
            $out[] = ['Étude de sol', "{$this->n()} est exposée au retrait-gonflement des argiles. Dans les zones d'exposition moyenne ou forte, une étude de sol est obligatoire pour une maison neuve : elle fixe la profondeur des fondations."];
        }
        $out[] = ['Assainissement', "Si le terrain n'est pas raccordé au tout-à-l'égout, le permis contient l'attestation du SPANC sur votre installation individuelle."];
        if ($this->facts->ppr()->isNotEmpty()) {
            $out[] = ['Plan de prévention des risques', "Le terrain peut être concerné par un plan de prévention : je reporte la zone et ses règles sur le plan de masse et dans la notice."];
        }
        if ($this->abf() === true) {
            $out[] = ['Notice architecturale soignée', "Dans les secteurs protégés, la notice décrit précisément matériaux, teintes et menuiseries pour l'Architecte des Bâtiments de France."];
        }

        return $out;
    }

    /** Questions-réponses de la page PC. */
    public function pcFaq(): array
    {
        $n = $this->n();
        $a = $this->a();
        $u = $this->facts->f['urba'] ?? [];
        $q = [];

        $q[] = ["Peut-on construire une maison {$a} ?", match ($this->facts->docType()) {
            'PLU', 'PSMV' => "Oui, dans les zones U et AU du PLU de la commune, selon leur règlement. En zone A ou N, une maison neuve n'est en principe pas possible. Je vérifie la zone de votre parcelle avant le devis.",
            'PLUi' => 'Oui, dans les zones U et AU du PLU intercommunal'.(! empty($u['porteur']) ? ' '.TownFacts::epciDe($u['porteur']) : '').', selon leur règlement. En zone A ou N, une maison neuve n\'est en principe pas possible.',
            'CC' => "Oui, dans les secteurs constructibles de la carte communale. Ailleurs, seuls l'agrandissement des maisons existantes et quelques exceptions sont possibles.",
            'RNU' => "En principe dans les parties déjà urbanisées de la commune, au titre du règlement national d'urbanisme. Le maire décide au nom de l'État ; demander un certificat d'urbanisme avant d'acheter un terrain est prudent.",
            default => "Cela dépend du document d'urbanisme de votre parcelle. Avant d'acheter un terrain, un certificat d'urbanisme opérationnel dit si la maison est possible.",
        }];

        $q[] = ["Combien de temps pour obtenir un permis de construire {$a} ?", $this->abf() === true
            ? "Deux mois pour une maison ou son extension, trois mois si le terrain est dans les abords d'un monument historique ou dans le site patrimonial remarquable. Le délai court à partir du dossier complet."
            : "Deux mois pour une maison ou son extension, trois mois pour les autres projets. Le délai court à partir du dossier complet ; la mairie a un mois pour réclamer une pièce manquante."];

        $q[] = ["Faut-il un architecte pour un permis {$a} ?", "Seulement si la surface de plancher ou l'emprise au sol dépasse 150 m², existant compris, ou si le demandeur est une société. En dessous, je prépare le permis complet ; au-delà, je travaille avec un architecte partenaire."];

        if ($this->facts->ppr('inond')->isNotEmpty()) {
            $q[] = ["Peut-on construire en zone inondable {$a} ?", "Cela dépend de la zone du plan de prévention ({$this->facts->ppr('inond')->first()['l']}). En zone rouge, les constructions nouvelles sont en général interdites ; en zone bleue, elles sont possibles avec un plancher au-dessus de la cote de référence."];
        }
        if ($this->clay()) {
            $q[] = ["Faut-il une étude de sol pour construire {$a} ?", "Oui dans les zones d'exposition moyenne ou forte au retrait-gonflement des argiles, que l'on trouve sur la commune. Le vendeur du terrain la fournit, et le constructeur en tient compte pour les fondations."];
        }

        return $q;
    }

    /** Phrase courte pour la meta description, selon la démarche. */
    public function summary(string $kind): string
    {
        $bits = [];
        if ($kind === 'dp') {
            $bits[] = 'extension jusqu\'à '.$this->dpThreshold();
            if ($this->abf() === true) {
                $bits[] = 'avis de l\'ABF près des monuments';
            }
        } else {
            if ($l = $this->facts->docLabel()) {
                $bits[] = str_starts_with($l, 'PLU') ? $l : mb_strtolower($l);
            }
            if ($this->facts->ppr('inond')->isNotEmpty()) {
                $bits[] = 'zone inondable (PPRI)';
            }
            if ($this->clay()) {
                $bits[] = 'sols argileux';
            }
        }

        return implode(', ', $bits);
    }

    /** Guides de projets qui correspondent à la démarche. */
    public static function guides(string $kind): Collection
    {
        return ProjectGuides::all()->filter(fn ($g) => $kind === 'dp' ? str_contains($g['proc'], 'DP') : str_contains($g['proc'], 'PC'))->values();
    }
}
