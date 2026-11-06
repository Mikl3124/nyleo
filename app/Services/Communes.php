<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Communes de France (actuelles et anciennes) pour le lieu de naissance du CERFA.
 * Source : @etalab/decoupage-administratif (COG INSEE), dans resources/data/communes.json,
 * triées par population décroissante.
 */
class Communes
{
    private static ?array $data = null;

    /** @return list<array{nom: string, departement: string, departement_nom: string, label: string}> */
    public static function search(string $query, int $limit = 8): array
    {
        $q = self::key($query);
        if (mb_strlen($q) < 2) {
            return [];
        }
        $data = self::data();

        $starts = $contains = [];
        foreach ($data['communes'] as [$nom, $dep, $key]) {
            if (str_starts_with($key, $q)) {
                $starts[] = [$nom, $dep];
                if (count($starts) >= $limit) {
                    break;
                }
            } elseif (count($contains) < $limit && str_contains($key, ' '.$q)) {
                $contains[] = [$nom, $dep];
            }
        }

        return array_map(fn ($c) => [
            'nom' => $c[0],
            'departement' => $c[1],
            'departement_nom' => $data['departements'][$c[1]] ?? '',
            'label' => self::label($c[0], $c[1]),
        ], array_slice(array_merge($starts, $contains), 0, $limit));
    }

    /** Texte enregistré sur la fiche client, ex. « Périgueux (24 - Dordogne) ». */
    public static function label(string $nom, string $departement): string
    {
        $depNom = self::data()['departements'][$departement] ?? null;

        return $nom.' ('.$departement.($depNom ? ' - '.$depNom : '').')';
    }

    public static function key(string $value): string
    {
        $s = Str::of(Str::ascii($value))->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->toString();

        return preg_replace(['/\bst\b/', '/\bste\b/'], ['saint', 'sainte'], $s);
    }

    private static function data(): array
    {
        return self::$data ??= json_decode(file_get_contents(resource_path('data/communes.json')), true);
    }
}
