<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Communes de la Dordogne et de ses départements voisins, et tracés des cartes (resources/data/geo.json,
 * généré par tools/geo/build.py à partir des données ouvertes IGN / Etalab).
 * Les coordonnées sont en kilomètres (projection équirectangulaire centrée sur 45° N).
 */
class Geo
{
    private static ?array $data = null;

    public static function data(): array
    {
        return self::$data ??= json_decode(file_get_contents(resource_path('data/geo.json')), true);
    }

    public static function department(string $slug): ?array
    {
        $d = self::data()['departments'][$slug] ?? null;

        return $d ? $d + (SiteContent::DEPARTMENTS[$slug] ?? []) + ['slug' => $slug] : null;
    }

    /** Slugs des départements couverts, dans l'ordre du site (la Dordogne d'abord). */
    public static function departmentSlugs(): array
    {
        return array_keys(SiteContent::DEPARTMENTS);
    }

    /** « en Dordogne », « dans le Lot ». */
    public static function inDep(string $slug): string
    {
        return SiteContent::DEPARTMENTS[$slug]['in'] ?? 'en '.(self::data()['departments'][$slug]['name'] ?? $slug);
    }

    /** « de la Dordogne », « du Lot ». */
    public static function ofDep(string $slug): string
    {
        return SiteContent::DEPARTMENTS[$slug]['of'] ?? 'de '.(self::data()['departments'][$slug]['name'] ?? $slug);
    }

    /** Liste lisible des départements : « la Dordogne, la Gironde, … et le Lot-et-Garonne ». */
    public static function departmentNames(): string
    {
        $names = array_map(fn ($d) => $d['name'], SiteContent::DEPARTMENTS);
        $last = array_pop($names);

        return implode(', ', $names).' et '.$last;
    }

    /** @return Collection<int, array> */
    public static function communes(?string $dep = null): Collection
    {
        return collect(self::data()['communes'])->when($dep, fn ($c) => $c->where('dep', $dep))->values();
    }

    /** Communes qui ont leur propre page, de la plus peuplée à la moins peuplée. */
    public static function pages(?string $dep = null): Collection
    {
        return self::communes($dep)->where('page', true)->sortByDesc('pop')->values();
    }

    public static function find(string $dep, string $slug): ?array
    {
        return self::communes($dep)->firstWhere('slug', $slug);
    }

    public static function base(): array
    {
        return self::data()['communes'][self::data()['base']];
    }

    public static function region(): array
    {
        return self::data()['region'];
    }

    public static function france(): array
    {
        return self::data()['france'];
    }

    /** Les communes avec page les plus proches. */
    public static function nearby(array $commune, int $n = 8): Collection
    {
        return self::pages()->reject(fn ($c) => $c['code'] === $commune['code'])
            ->sortBy(fn ($c) => hypot($c['x'] - $commune['x'], $c['y'] - $commune['y']))
            ->take($n)->values();
    }

    /** Les autres communes avec page de la même intercommunalité. */
    public static function sameEpci(array $commune): Collection
    {
        return $commune['epci'] ? self::pages()->where('epci', $commune['epci'])->reject(fn ($c) => $c['code'] === $commune['code'])->sortBy('name')->values() : collect();
    }

    public static function distance(array $a, array $b): int
    {
        return (int) round(hypot($a['x'] - $b['x'], $a['y'] - $b['y']));
    }

    /** Direction de $to vue depuis $from (« au nord-ouest »). */
    public static function direction(array $from, array $to): string
    {
        $deg = rad2deg(atan2(-($to['y'] - $from['y']), $to['x'] - $from['x'])); // 0 = est, 90 = nord
        $dirs = ["à l'est", 'au nord-est', 'au nord', 'au nord-ouest', "à l'ouest", 'au sud-ouest', 'au sud', 'au sud-est'];

        return $dirs[(int) round(fmod($deg + 360, 360) / 45) % 8];
    }

    /**
     * Garde les communes dans l'ordre donné en sautant celles trop proches d'une
     * commune déjà retenue, pour que les étiquettes d'une carte ne se chevauchent pas.
     */
    public static function spread(iterable $communes, float $minKm, ?array $avoid = null, float $wide = 2.2): \Illuminate\Support\Collection
    {
        $kept = $avoid ? [$avoid] : [];
        foreach ($communes as $c) {
            foreach ($kept as $k) {
                if (abs($c['y'] - $k['y']) < $minKm * .45 && abs($c['x'] - $k['x']) < $minKm * $wide) {
                    continue 2;
                }
            }
            $kept[] = $c;
        }

        return collect($avoid ? array_slice($kept, 1) : $kept);
    }

    /** viewBox carré centré sur une commune, pour un zoom local. */
    public static function zoomBox(array $c, float $half = 18): string
    {
        return sprintf('%.1f %.1f %.1f %.1f', $c['x'] - $half, $c['y'] - $half, 2 * $half, 2 * $half);
    }

    /** « à Bordeaux », « au Bouscat », « aux Eyzies » (Le et Les se contractent, pas La). */
    public static function a(string $name): string
    {
        return match (true) {
            str_starts_with($name, 'Les ') => 'aux '.substr($name, 4),
            str_starts_with($name, 'Le ') => 'au '.substr($name, 3),
            default => 'à '.$name,
        };
    }

    /** « de Bordeaux », « du Bouscat », « des Eyzies ». */
    public static function de(string $name): string
    {
        return match (true) {
            str_starts_with($name, 'Les ') => 'des '.substr($name, 4),
            str_starts_with($name, 'Le ') => 'du '.substr($name, 3),
            (bool) preg_match('/^[AEIOUYÉÈÊÎÔÂ]/u', $name) => "d'".$name,
            default => 'de '.$name,
        };
    }

    /** Page d'une commune : 'pc' (permis de construire) ou 'dp' (déclaration préalable). */
    public static function url(array $c, string $kind = 'pc'): string
    {
        return route('site.'.self::kind($kind).'.town', [$c['dep'], $c['slug']]);
    }

    /** Page d'un département pour une démarche. */
    public static function areaUrl(string $slug, string $kind = 'pc'): string
    {
        return route('site.'.self::kind($kind).'.area', $slug);
    }

    public static function kind(?string $kind): string
    {
        return $kind === 'dp' ? 'dp' : 'pc';
    }
}
