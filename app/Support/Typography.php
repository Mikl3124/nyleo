<?php

namespace App\Support;

/**
 * Typographie française appliquée à tout ce qui est rendu (pages, PDF, e-mails).
 *
 * Règle de Mickaël : un signe de ponctuation ne doit jamais se retrouver seul en début de ligne.
 * Les espaces devant « : ; ! ? » et à l'intérieur des guillemets « » deviennent insécables, comme
 * celles entre un nombre et son unité (400 €, 20 m², 1 500) et dans un numéro de téléphone.
 * Le contenu des balises script, style, textarea, pre et code n'est jamais modifié.
 */
class Typography
{
    public const NBSP = "\u{00A0}";

    private const SKIP = ['script', 'style', 'textarea', 'pre', 'code'];

    /** Corrige un texte brut (sans balises). */
    public static function text(string $text): string
    {
        if ($text === '' || ! preg_match('/[:;!?»«€%\d]/u', $text)) {
            return $text;
        }
        $n = self::NBSP;

        $text = preg_replace([
            '/(?<=\S)[ \t]+(?=[:;!?»])/u',                                  // mot : … / mot ? / mot »
            '/«[ \t]+/u',                                                     // « mot
            '/(?<=\d)[ \t]+(?=(?:€|%|m²|m2|km|kWc|ha)(?![\p{L}\d]))/u',     // 400 € · 20 m²
        ], [$n, '«'.$n, $n], $text) ?? $text;

        // Milliers : 1 500 ou 12 000 000 (pas les blocs d'un IBAN ou d'une référence).
        $text = preg_replace_callback('/(?<![\p{L}\d])\d{1,3}(?:[ \t]\d{3})+(?![\p{L}\d])/u', fn ($m) => str_replace([' ', "\t"], $n, $m[0]), $text) ?? $text;

        // Numéro de téléphone : 07 85 65 91 65 ne se coupe pas.
        return preg_replace_callback('/(?<!\d)0\d(?:[ \t]\d\d){4}(?!\d)/', fn ($m) => str_replace([' ', "\t"], $n, $m[0]), $text) ?? $text;
    }

    /** Corrige un document HTML : seuls les nœuds de texte hors balises techniques sont touchés. */
    public static function html(string $html): string
    {
        if ($html === '') {
            return $html;
        }
        $parts = preg_split('/(<!--.*?-->|<[^>]*>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $html;
        }
        $skip = null;
        foreach ($parts as $i => $part) {
            if ($part === '') {
                continue;
            }
            if ($part[0] === '<') {
                if ($skip === null && preg_match('/^<(' . implode('|', self::SKIP) . ')\b/i', $part, $m) && ! str_ends_with($part, '/>')) {
                    $skip = strtolower($m[1]);
                } elseif ($skip !== null && preg_match('/^<\/' . $skip . '\s*>/i', $part)) {
                    $skip = null;
                }

                continue;
            }
            if ($skip === null) {
                $parts[$i] = self::text($part);
            }
        }

        return implode('', $parts);
    }
}
