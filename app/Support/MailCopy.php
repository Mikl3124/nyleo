<?php

namespace App\Support;

use App\Models\CompanySetting;
use App\Notifications\AdminAlert;
use App\Notifications\PortalLink;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Copie cachée (Cci) de chaque e-mail envoyé aux clients, à l'adresse de Mickaël.
 * Réglage : Paramètres > Automatisations. Jamais pour les liens de connexion ni les mots de passe
 * (un lien personnel ne doit exister que chez son destinataire), ni pour les alertes admin.
 */
class MailCopy
{
    /** Notifications jamais copiées. */
    public const EXCLUDED = [PortalLink::class, ResetPassword::class, VerifyEmail::class, AdminAlert::class];

    /** Adresse de copie, ou null si la copie est désactivée. */
    public static function address(): ?string
    {
        $s = rescue(fn () => CompanySetting::current(), null, false);
        if (! $s || ! $s->bcc_enabled) {
            return null;
        }
        $email = trim((string) ($s->bcc_email ?: $s->email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /** Ajoute la copie cachée au message ; renvoie true si elle a été ajoutée. */
    public static function apply(Email $message, array $data = []): bool
    {
        $notification = $data['__laravel_notification'] ?? null;
        if ($notification && collect(self::EXCLUDED)->contains(fn ($c) => is_a($notification, $c, true))) {
            return false;
        }
        if (! $address = self::address()) {
            return false;
        }

        $already = collect([...$message->getTo(), ...$message->getCc(), ...$message->getBcc()])
            ->contains(fn (Address $a) => strcasecmp($a->getAddress(), $address) === 0);
        if ($already) {
            return false;
        }

        $message->addBcc($address);

        return true;
    }
}
