<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Règles d'urbanisme, patrimoine protégé et risques d'une commune, rédigés pour sa page.
 *
 * Données publiques par commune dans resources/data/seo.json (générées par
 * tools/geo/build_seo.py : Géoportail de l'urbanisme, base Mérimée, Géorisques,
 * Annuaire de l'administration). Une donnée absente n'est jamais devinée : la section
 * correspondante est simplement plus générale.
 */
class TownFacts
{
    private static ?array $data = null;

    public static function data(): array
    {
        if (self::$data === null) {
            $file = resource_path('data/seo.json');
            self::$data = is_file($file) ? json_decode(file_get_contents($file), true) : ['communes' => []];
        }

        return self::$data;
    }

    public static function raw(string $code): array
    {
        return self::data()['communes'][$code] ?? [];
    }

    public static function updated(): ?string
    {
        return self::data()['updated'] ?? null;
    }

    public function __construct(public readonly array $town, public readonly array $f = [])
    {
    }

    public static function for(array $town): self
    {
        return new self($town, self::raw($town['code']));
    }

    private function name(): string
    {
        return $this->town['name'];
    }

    // ---- Urbanisme -------------------------------------------------------------------

    /** PLU, PLUi, CC, PSMV ou RNU ; null si inconnu. */
    public function docType(): ?string
    {
        $t = $this->f['urba']['type'] ?? null;

        return $t === 'POS' ? 'RNU' : $t; // les POS sont caducs depuis 2020 : le RNU s'applique.
    }

    public function hasPlu(): bool
    {
        return in_array($this->docType(), ['PLU', 'PLUi', 'PSMV'], true);
    }

    public function docLabel(): ?string
    {
        return match ($this->docType()) {
            'PLU' => 'PLU communal',
            'PLUi' => 'PLU intercommunal',
            'PSMV' => 'Plan de sauvegarde',
            'CC' => 'Carte communale',
            'RNU' => 'Règlement national',
            default => null,
        };
    }

    private function year(?string $date): ?string
    {
        return $date && preg_match('/^(\d{4})/', $date, $m) ? $m[1] : null;
    }

    /** Paragraphes sur le document d'urbanisme. */
    public function urbanism(): array
    {
        $u = $this->f['urba'] ?? [];
        $n = $this->name();
        $year = $this->year($u['date'] ?? null);
        $since = $year ? ', approuvé en '.$year : '';
        $p = [];

        switch ($this->docType()) {
            case 'PLU':
            case 'PSMV':
                $p[] = "{$n} a son propre plan local d'urbanisme (PLU){$since}. Le règlement de la zone de votre parcelle (U, AU, A ou N) fixe ce que vous pouvez construire : emprise au sol, hauteur, distance aux limites, aspect des façades et des clôtures.";
                break;
            case 'PLUi':
                $by = ! empty($u['porteur']) ? ' '.self::epciDe($u['porteur']) : '';
                $count = $this->sameDocCount();
                $shared = $count > 1 ? " Ce document est partagé par {$count} communes, mais chaque parcelle dépend de sa zone." : '';
                $p[] = "{$n} applique le plan local d'urbanisme intercommunal (PLUi){$by}{$since}.{$shared} Le règlement de votre zone (U, AU, A ou N) fixe l'emprise au sol, la hauteur, la distance aux limites et l'aspect extérieur.";
                break;
            case 'CC':
                $p[] = "{$n} est couverte par une carte communale".($year ? ', approuvée en '.$year : '').". Elle délimite les secteurs où l'on peut construire. Les règles de construction sont celles du règlement national d'urbanisme (RNU) : pas de règlement de zone, mais une insertion soignée dans le paysage est exigée.";
                break;
            case 'RNU':
                $p[] = "{$n} n'a pas de document d'urbanisme en vigueur : le règlement national d'urbanisme (RNU) s'applique. Une maison neuve n'est possible, en principe, que dans les parties déjà urbanisées de la commune, et c'est le maire qui décide au nom de l'État.";
                break;
            default:
                $p[] = "Avant le devis, je consulte le document d'urbanisme en vigueur {$this->a()} pour votre parcelle (PLU, carte communale ou règlement national) et je vous dis quelle démarche s'applique.";
        }

        if (in_array('PSMV', $u['autres'] ?? [], true)) {
            $p[] = "Le centre ancien est couvert en plus par un plan de sauvegarde et de mise en valeur (PSMV) : ses règles, très précises, s'appliquent à chaque immeuble, jusqu'aux menuiseries et aux enduits.";
        }

        if ($this->docType() && ! $this->hasPlu()) {
            $p[] = "Bon à savoir : sans PLU, le seuil de la déclaration préalable pour une extension reste à 20 m². Au-delà, c'est un permis de construire (la règle des 40 m² ne vaut qu'en zone urbaine d'un PLU).";
        } elseif ($this->hasPlu()) {
            $p[] = "En zone urbaine (U) du PLU, une extension accolée jusqu'à 40 m² relève de la déclaration préalable, sauf si elle porte la surface totale au-delà de 150 m² : il faut alors un permis de construire et un architecte.";
        }

        if (! empty($u['en_cours'])) {
            $p[] = "Un document d'urbanisme est en cours d'élaboration ({$u['en_cours']}). Pendant les études, la mairie peut surseoir à statuer sur un projet qui compromettrait le futur plan : je vérifie où en est la procédure.";
        }

        return $p;
    }

    /** « de la communauté de communes X », « de Bordeaux Métropole ». */
    public static function epciDe(string $epci): string
    {
        foreach (['CA ' => "de la communauté d'agglomération ", 'CC ' => 'de la communauté de communes ', 'CU ' => 'de la communauté urbaine '] as $short => $long) {
            if (str_starts_with($epci, $short)) {
                return $long.substr($epci, strlen($short));
            }
        }

        return 'de '.$epci;
    }

    /** Nombre de communes couvertes qui partagent le même PLUi. */
    private function sameDocCount(): int
    {
        $key = $this->f['urba']['porteur'] ?? null;
        if (! $key) {
            return 0;
        }

        return collect(self::data()['communes'])->filter(fn ($c) => ($c['urba']['type'] ?? null) === 'PLUi' && ($c['urba']['porteur'] ?? null) === $key)->count();
    }

    // ---- Patrimoine ------------------------------------------------------------------

    /** @return Collection<int, array{n:string,p:string}> */
    public function monuments(): Collection
    {
        // Les monuments classés d'abord, puis par ordre alphabétique.
        return collect($this->f['mh'] ?? [])->sortBy(fn ($m) => (str_contains($m['p'], 'classé') ? '0' : '1').$m['n'])->values();
    }

    public function spr(): array
    {
        return $this->f['spr'] ?? [];
    }

    /** Le dossier passe-t-il souvent par l'Architecte des Bâtiments de France ? */
    public function abfLikely(): bool
    {
        return $this->monuments()->isNotEmpty() || $this->spr() !== [];
    }

    /** null si la donnée n'a pas été collectée (on ne dit alors rien). */
    public function heritage(): ?array
    {
        if (! array_key_exists('mh', $this->f) && ! array_key_exists('spr', $this->f)) {
            return null;
        }
        $n = $this->name();
        $mh = $this->monuments();
        $p = [];

        if ($this->spr() !== []) {
            $kinds = collect($this->spr())->map(fn ($s) => preg_match('/\(([^)]+)\)\s*$/', $s, $m) ? $m[1] : null)->filter()->unique()->values()->all();
            $plan = $kinds ? ' ('.$this->listFr($kinds).')' : '';
            $p[] = "Une partie de la commune est classée site patrimonial remarquable{$plan}. Dans ce périmètre, l'Architecte des Bâtiments de France (ABF) donne son accord sur tous les travaux extérieurs, jusqu'au changement d'une fenêtre ou d'une clôture.";
        }

        if ($mh->isNotEmpty()) {
            $classes = $mh->filter(fn ($m) => str_contains($m['p'], 'classé'))->count();
            $inscrits = $mh->count() - $classes;
            $detail = collect([$classes ? $classes.' '.($classes > 1 ? 'classés' : 'classé') : null, $inscrits ? $inscrits.' '.($inscrits > 1 ? 'inscrits' : 'inscrit') : null])->filter()->implode(' et ');
            $word = $mh->count() > 1 ? 'monuments historiques protégés' : 'monument historique protégé';
            $names = $mh->map(fn ($m) => trim(\Illuminate\Support\Str::before(\Illuminate\Support\Str::before($m['n'], ' ou '), ',')))
                ->reject(fn ($x) => in_array($x, ['Maison', 'Immeuble', 'Hôtel', 'Croix'], true))->unique()->take(3)->values()->all();
            $dont = $names ? ($mh->count() > 1 ? '. Parmi eux : ' : ' : ').$this->listFr($names) : '';
            $p[] = "{$n} compte {$mh->count()} {$word} ({$detail}){$dont}. Autour de chacun, dans un rayon de 500 m ou dans son périmètre délimité des abords, un projet visible depuis le monument ou en même temps que lui demande l'accord de l'ABF. Le délai d'instruction est alors allongé d'un mois, et les matériaux, couleurs et menuiseries sont regardés de près.";
        } elseif ($this->spr() === []) {
            $p[] = "Aucun monument historique n'est protégé sur la commune. Les abords d'un monument d'une commune voisine peuvent toutefois déborder jusqu'ici : je le vérifie pour votre parcelle avant de dessiner.";
        }

        return $p;
    }

    // ---- Risques ---------------------------------------------------------------------

    private function hasRisk(string $needle): bool
    {
        foreach ($this->f['risques'] ?? [] as $r) {
            if (stripos(\Illuminate\Support\Str::ascii($r), $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Plans de prévention approuvés, filtrés par aléa : 'inond', 'feu', 'mouvement', 'industriel'.
     *
     * @return Collection<int, array>
     */
    public function ppr(?string $kind = null, string $key = 'ppr'): Collection
    {
        return collect($this->f[$key] ?? [])
            ->when($kind, fn ($c) => $c->filter(fn ($p) => stripos(\Illuminate\Support\Str::ascii(($p['t'] ?? '').' '.($p['l'] ?? '').' '.($p['a'] ?? '')), $kind) !== false))
            ->values();
    }

    private function pprNames(Collection $ppr): string
    {
        return $ppr->map(fn ($p) => $p['l'].($this->year($p['d'] ?? null) ? ', '.$this->year($p['d']) : ''))->unique()->implode(' ; ');
    }

    /** Liste [titre, texte] des risques qui comptent pour un projet de construction. */
    public function risks(): ?array
    {
        if (! array_key_exists('risques', $this->f)) {
            return null;
        }
        $out = [];

        $ppri = $this->ppr('inond');
        $sea = $this->hasRisk('submersion');
        if ($ppri->isNotEmpty()) {
            $out[] = [$sea ? 'Inondation et submersion marine' : 'Plan de prévention du risque inondation', "La commune est couverte par un plan de prévention ({$this->pprNames($ppri)}). En zone rouge, les constructions nouvelles sont en général interdites ; en zone bleue, le règlement impose par exemple une hauteur minimale de plancher. Je reporte la zone et les cotes sur le plan de masse."];
        } elseif ($this->hasRisk('inond')) {
            $out[] = ['Risque d\'inondation', "La commune est exposée aux crues".($sea ? ' et à la submersion marine' : '').", sans plan de prévention approuvé à ce jour. La mairie s'appuie alors sur l'atlas des zones inondables et peut refuser ou encadrer un projet exposé. Je vérifie la situation de votre terrain."];
        }

        if ($this->hasRisk('dunaire')) {
            $out[] = ['Littoral et dunes', "Une partie de la commune est soumise à l'avancée dunaire et à l'érosion du littoral. Près du rivage, la loi Littoral limite fortement les constructions nouvelles."];
        }

        $mvt = $this->ppr('mouvement');
        $kinds = collect([
            'carrieres' => 'd\'anciennes carrières souterraines',
            'glissement' => 'des glissements de terrain',
            'eboulement' => 'des chutes de pierres et de blocs',
        ])->filter(fn ($l, $k) => $this->hasRisk($k))->values();
        if ($mvt->isNotEmpty() || $kinds->isNotEmpty()) {
            $what = $kinds->isNotEmpty() ? 'Certains secteurs sont exposés à '.$this->listFr($kinds->all()).'.' : 'Certains secteurs sont exposés à des mouvements de terrain.';
            $out[] = ['Mouvements de terrain', $what.($mvt->isNotEmpty()
                ? " Un plan de prévention s'applique ({$this->pprNames($mvt)}) : il peut interdire ou encadrer les constructions selon la zone."
                : ' La mairie peut demander des précautions particulières, voire une étude de sol.')];
        }

        if ($this->hasRisk('tassements differentiels') || $this->hasRisk('argile')) {
            $out[] = ['Sols argileux', "Retrait-gonflement des argiles : pour une maison neuve en zone d'exposition moyenne ou forte, l'étude de sol est obligatoire."];
        }

        $pprif = $this->ppr('feu');
        if ($this->hasRisk('feu') || $pprif->isNotEmpty()) {
            $plan = $pprif->isNotEmpty() ? " Un plan de prévention des incendies de forêt s'applique ({$this->pprNames($pprif)}) et fixe des règles d'accès et de défense contre l'incendie."
                : ($this->ppr('feu', 'ppr_prescrits')->isNotEmpty() ? " Un plan de prévention des incendies de forêt est en préparation." : '');
            $out[] = ['Feux de forêt', "La commune est exposée aux feux de forêt. Près des bois, le débroussaillement autour des constructions est obligatoire, et la mairie peut demander un accès suffisant pour les secours.{$plan}"];
        }

        $pprt = collect($this->f['ppr'] ?? [])->filter(fn ($p) => ($p['t'] ?? '') === 'PPRT');
        if ($pprt->isNotEmpty()) {
            $out[] = ['Risque industriel', "Un plan de prévention des risques technologiques s'applique ({$this->pprNames($pprt)}). Près du site, il peut interdire de construire ou imposer un renforcement du bâti."];
        }

        if (($this->f['radon'] ?? null) === 3) {
            $out[] = ['Radon', "La commune est classée en potentiel radon significatif (catégorie 3). Pour une maison neuve, un plancher bas étanche et une bonne ventilation sont recommandés."];
        }

        if (($this->f['sismi'] ?? 1) >= 3) {
            $out[] = ['Séisme', "Zone de sismicité {$this->f['sismi']} : une maison neuve doit respecter les règles de construction parasismique."];
        }

        return $out;
    }

    private function listFr(array $items): string
    {
        return count($items) > 1 ? implode(', ', array_slice($items, 0, -1)).' et '.end($items) : ($items[0] ?? '');
    }

    /** Logements autorisés par an et pour 1 000 habitants, sur la période. */
    public static function housingRate(array $housing, int $pop): ?float
    {
        return $pop > 0 && $housing ? array_sum($housing) / count($housing) / $pop * 1000 : null;
    }

    /** Taux moyen du département (toutes communes). */
    public static function departmentHousingRate(string $dep): ?float
    {
        $c = Geo::communes($dep);
        $sum = $c->sum(fn ($t) => array_sum(self::raw($t['code'])['logements'] ?? []) / 5);
        $pop = $c->sum('pop');

        return $pop ? $sum / $pop * 1000 : null;
    }

    /** Logements autorisés par an (Sitadel), du plus ancien au plus récent. */
    public function housing(): array
    {
        return $this->f['logements'] ?? [];
    }

    public function catnat(): ?int
    {
        return $this->f['catnat'] ?? null;
    }

    // ---- Mairie et délais ------------------------------------------------------------

    public function mairie(): ?array
    {
        return $this->f['mairie'] ?? null;
    }

    /** Délais d'instruction [projet, délai courant, délai avec ABF]. */
    public function delays(): array
    {
        return [
            ['Déclaration préalable', '1 mois', '2 mois'],
            ['Permis de construire d\'une maison ou d\'une extension', '2 mois', '3 mois'],
            ['Autre permis de construire', '3 mois', '4 mois'],
        ];
    }

    // ---- Résumé, FAQ -----------------------------------------------------------------

    private function a(): string
    {
        return Geo::a($this->name());
    }

    /** Questions-réponses propres à la commune (affichées et balisées en FAQPage). */
    public function faq(): array
    {
        $n = $this->name();
        $a = $this->a();
        $mairie = 'la mairie '.Geo::de($n);
        $q = [];

        if ($this->docType()) {
            $u = $this->f['urba'];
            $year = $this->year($u['date'] ?? null);
            $doc = match ($this->docType()) {
                'PLUi' => 'Le plan local d\'urbanisme intercommunal (PLUi)'.(! empty($u['porteur']) ? ' '.self::epciDe($u['porteur']) : ''),
                'PLU', 'PSMV' => 'Le plan local d\'urbanisme (PLU) de la commune',
                'CC' => 'Une carte communale, complétée par le règlement national d\'urbanisme',
                'RNU' => 'Le règlement national d\'urbanisme (RNU), faute de PLU ou de carte communale',
            };
            $q[] = ["Quel document d'urbanisme s'applique {$a} ?", $doc.($year ? ", en vigueur depuis {$year}" : '').". Je consulte le règlement qui s'applique à votre parcelle sur le Géoportail de l'urbanisme avant de vous envoyer le devis."];
        }

        $abf = $this->abfLikely();
        $q[] = ["Combien de temps dure l'instruction d'une déclaration préalable {$a} ?", "Un mois à partir du dépôt d'un dossier complet. "
            .($abf ? "Si votre terrain est dans les abords d'un monument historique ou dans le site patrimonial remarquable, comptez deux mois : l'Architecte des Bâtiments de France doit donner son avis." : "Comptez deux mois si le terrain est dans les abords d'un monument historique.")
            ." Pour le permis de construire d'une maison, le délai est de deux mois (trois avec l'avis de l'ABF)."];

        $m = $this->mairie();
        $where = $m && ! empty($m['adresse']) ? "Le dossier se dépose à {$mairie} ({$m['adresse']}, {$m['cp']} {$m['ville']}) ou sur son guichet en ligne." : "Le dossier se dépose à {$mairie}, au guichet ou en ligne.";
        $q[] = ["Où déposer un permis de construire {$a} ?", "{$where} Depuis 2022, toutes les communes doivent accepter les demandes déposées par voie électronique. Je dépose le dossier en votre nom et je vous transmets le récépissé."];

        if ($this->hasPlu() || ($this->docType() && ! $this->hasPlu())) {
            $q[] = ["Faut-il un permis pour une extension de 30 m² {$a} ?", $this->hasPlu()
                ? "En zone urbaine (U) du PLU, non : une déclaration préalable suffit jusqu'à 40 m², sauf si la surface totale de la maison dépasse alors 150 m². Hors zone U, un permis est nécessaire au-delà de 20 m²."
                : "Oui. {$n} n'ayant pas de PLU, le seuil de la déclaration préalable reste à 20 m² : une extension de 30 m² demande un permis de construire."];
        }

        $ppri = $this->ppr('inond');
        if ($ppri->isNotEmpty()) {
            $q[] = ["Peut-on construire en zone inondable {$a} ?", "Cela dépend de la zone du plan de prévention ({$ppri->first()['l']}). En zone rouge, les constructions nouvelles sont en général interdites, sauf exceptions limitées. En zone bleue, c'est possible avec des prescriptions, comme une hauteur minimale de plancher. Je vérifie la zone de votre parcelle avant le devis."];
        }

        return $q;
    }

    /** Phrase courte et unique pour la meta description. */
    public function summary(): string
    {
        $bits = [];
        if ($l = $this->docLabel()) {
            $bits[] = match ($this->docType()) {
                'PLUi' => 'PLUi', 'PLU', 'PSMV' => 'PLU', 'CC' => 'carte communale', 'RNU' => 'RNU', default => $l,
            };
        }
        $mh = $this->monuments()->count();
        if ($mh) {
            $bits[] = $mh.' '.($mh > 1 ? 'monuments historiques' : 'monument historique');
        }
        if ($this->ppr('inond')->isNotEmpty()) {
            $bits[] = 'PPRI';
        }

        return $bits ? implode(', ', $bits) : '';
    }

    // ---- Département -----------------------------------------------------------------

    /** Chiffres d'un département, calculés sur toutes ses communes. null sans données. */
    public static function department(string $slug): ?array
    {
        $communes = Geo::communes($slug)->map(fn ($c) => self::for($c));
        $known = $communes->filter(fn ($f) => $f->docType());
        if ($known->isEmpty()) {
            return null;
        }

        $types = $known->groupBy(fn ($f) => $f->docType() === 'PSMV' ? 'PLU' : $f->docType())->map->count();
        $plui = $known->filter(fn ($f) => $f->docType() === 'PLUi' && ! empty($f->f['urba']['porteur']))
            ->groupBy(fn ($f) => $f->f['urba']['porteur'])->map->count()->sortDesc();

        return [
            'communes' => $communes->count(),
            'types' => $types->all(),
            'plui' => $plui->all(),
            'monuments' => $communes->sum(fn ($f) => $f->monuments()->count()),
            'mh_top' => $communes->filter(fn ($f) => $f->monuments()->count() >= 5)->sortByDesc(fn ($f) => $f->monuments()->count())->take(12)->values(),
            'spr' => $communes->filter(fn ($f) => $f->spr() !== [])->values(),
            'ppri' => $communes->filter(fn ($f) => $f->ppr('inond')->isNotEmpty())->count(),
        ];
    }
}
