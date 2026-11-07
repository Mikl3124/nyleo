<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Protection invisible du formulaire de demande de devis contre les robots.
 *
 * Rien à faire pour un humain : pas de case à cocher, pas d'image à déchiffrer.
 *  - pot de miel : un champ caché que seul un robot remplit ;
 *  - jeton horodaté chiffré : un envoi moins de MIN_SECONDS après l'affichage est trop rapide pour un humain ;
 *  - preuve d'interaction : le navigateur recopie le jeton dans un second champ au premier clic ou à la première touche ;
 *  - contenu : liens dans le nom, liens en série, alphabets cyrillique ou asiatique ;
 *  - Cloudflare Turnstile en mode invisible, si les clés sont configurées (si Cloudflare ne répond pas, la demande passe).
 */
class BotGuard
{
    public const MIN_SECONDS = 4;

    /** Jeton horodaté à placer dans le formulaire. */
    public static function token(?int $time = null): string
    {
        return Crypt::encryptString((string) ($time ?? time()));
    }

    /** Champs qu'un vrai navigateur envoie (utile aux tests). */
    public static function fields(int $secondsAgo = 30): array
    {
        $token = self::token(time() - $secondsAgo);

        return ['_t' => $token, '_h' => strrev($token)];
    }

    /**
     * Raison du blocage, ou null si l'envoi vient d'un humain.
     * « nojs » n'est pas silencieux : le navigateur sans JavaScript reçoit un message avec le téléphone.
     */
    public static function check(Request $request): ?string
    {
        if (filled($request->input('website'))) {
            return 'pot de miel';
        }

        $token = $request->input('_t');
        if (! is_string($token) || $token === '') {
            return 'jeton absent';
        }
        try {
            $shownAt = (int) Crypt::decryptString($token);
        } catch (DecryptException) {
            return 'jeton invalide';
        }
        if (time() - $shownAt < self::MIN_SECONDS) {
            return 'trop rapide';
        }

        $proof = $request->input('_h');
        if (! is_string($proof) || $proof === '') {
            return 'nojs';
        }
        if ($proof !== strrev($token)) {
            return 'preuve invalide';
        }

        return self::spamContent($request) ?? self::turnstile($request);
    }

    public static function turnstileEnabled(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key'));
    }

    /** « nojs » si le jeton Turnstile manque (script bloqué : l'humain est prévenu), raison si Cloudflare le refuse. */
    private static function turnstile(Request $request): ?string
    {
        if (! self::turnstileEnabled()) {
            return null;
        }
        $response = $request->input('cf-turnstile-response');
        if (! is_string($response) || $response === '') {
            return 'nojs';
        }

        try {
            $result = Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $response,
                'remoteip' => $request->header('CF-Connecting-IP') ?: $request->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Turnstile injoignable, demande acceptée : '.$e->getMessage());

            return null;
        }
        if (! $result->successful()) {
            return null; // panne côté Cloudflare : on ne perd pas un vrai client
        }

        return $result->json('success') === true ? null : 'turnstile';
    }

    private static function spamContent(Request $request): ?string
    {
        $link = '~(https?://|www\.|\[url|<a\s|\.(ru|cn|xyz|top|click|online)\b)~iu';
        foreach (['last_name', 'first_name', 'site_city', 'phone'] as $field) {
            $value = $request->input($field);
            if (is_string($value) && preg_match($link, $value)) {
                return "lien dans « {$field} »";
            }
        }

        $text = implode(' ', array_filter(array_map(
            fn ($f) => is_string($v = $request->input($f)) ? $v : null,
            ['last_name', 'first_name', 'site_city', 'description'],
        )));
        if (preg_match('/[\p{Cyrillic}\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}\p{Thai}]/u', $text)) {
            return 'alphabet étranger';
        }

        $description = (string) (is_string($request->input('description')) ? $request->input('description') : '');
        if (preg_match_all('~https?://|(?<!//)www\.~i', $description) >= 2) {
            return 'liens en série';
        }

        return null;
    }

    /** Trace d'un envoi bloqué (aucun client, aucun dossier, aucun e-mail). */
    public static function log(Request $request, string $reason): void
    {
        Log::warning('Demande de devis bloquée (robot) : '.$reason, [
            'ip' => $request->ip(),
            'email' => is_string($request->input('email')) ? $request->input('email') : null,
            'agent' => substr((string) $request->userAgent(), 0, 200),
        ]);
    }
}
