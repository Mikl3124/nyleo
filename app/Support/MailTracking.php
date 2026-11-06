<?php

namespace App\Support;

use App\Models\Document;
use App\Models\EmailMessage;
use App\Models\Invoice;
use App\Models\Quote;
use App\Services\ClientTracking;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Suivi des e-mails envoyés aux clients : un pixel invisible pour l'ouverture et des liens de redirection
 * pour les clics sur les boutons du site. Appliqué au moment de l'envoi (écouteur MessageSending)
 * aux e-mails marqués par tag().
 *
 * Copie cachée : elle n'est pas suivie. Les destinataires en Cci (et la copie automatique de Mickaël)
 * reçoivent un exemplaire séparé, sans pixel ni lien suivi, pour que leurs ouvertures ne comptent pas comme celles du client.
 */
class MailTracking
{
    public const HEADER = 'X-Nyleo-Track';

    public const REF_HEADER = 'X-Nyleo-Ref';

    /** Durée de validité des liens « Télécharger le PDF ». */
    public const DOWNLOAD_DAYS = 120;

    /**
     * Contexte à poser sur l'e-mail (MailMessage ou Mailable) : ce qu'il concerne.
     *
     * @param  array{client?: ?int, dossier?: ?int, quote?: ?int, invoice?: ?int, document?: ?int}  $context
     */
    public static function tag(object $mail, array $context): object
    {
        $json = json_encode(array_filter($context));

        return $mail->withSymfonyMessage(fn (Email $m) => $m->getHeaders()->addTextHeader(self::HEADER, $json));
    }

    /** Lien signé « Télécharger le PDF », sans connexion à l'espace client. */
    public static function downloadUrl(Quote|Invoice|Document $file): string
    {
        $kind = match (true) {
            $file instanceof Quote => 'devis',
            $file instanceof Invoice => 'facture',
            default => 'document',
        };

        return URL::temporarySignedRoute('files.download', now()->addDays(self::DOWNLOAD_DAYS), ['kind' => $kind, 'id' => $file->id]);
    }

    /** Appelé juste avant l'envoi. Renvoie true si l'e-mail est suivi. */
    public static function process(Email $message, array $data = []): bool
    {
        $headers = $message->getHeaders();
        if (! $header = $headers->get(self::HEADER)) {
            return false;
        }
        $context = (array) json_decode($header->getBodyAsString(), true);
        $headers->remove(self::HEADER);

        $to = $message->getTo()[0] ?? null;
        $record = EmailMessage::create([
            'token' => Str::random(40),
            'client_id' => $context['client'] ?? null,
            'dossier_id' => $context['dossier'] ?? null,
            'quote_id' => $context['quote'] ?? null,
            'invoice_id' => $context['invoice'] ?? null,
            'document_id' => $context['document'] ?? null,
            'to' => $to?->getAddress(),
            'subject' => mb_substr((string) $message->getSubject(), 0, 250),
        ]);
        $headers->addTextHeader(self::REF_HEADER, $record->token);

        self::sendUntrackedCopy($message, $data);

        $links = [];
        if (is_string($html = $message->getHtmlBody())) {
            $message->html(self::trackHtml($html, $record->token, $links));
        }
        if (is_string($text = $message->getTextBody()) && $links) {
            foreach ($links as $i => $link) {
                $text = str_replace($link['url'], route('mail.click', [$record->token, $i]), $text);
            }
            $message->text($text);
        }
        $record->forceFill(['links' => $links])->save();

        return true;
    }

    /** Après l'envoi : date d'envoi et identifiant du fournisseur (Amazon SES). */
    public static function sent(Email $message, ?string $providerId = null): void
    {
        if ($ref = $message->getHeaders()->get(self::REF_HEADER)) {
            EmailMessage::where('token', $ref->getBodyAsString())->update(['sent_at' => now(), 'provider_id' => $providerId]);
        }
    }

    /**
     * Retire les Cci du message suivi et leur envoie un exemplaire séparé, sans suivi.
     * La copie automatique (MailCopy) y est ajoutée.
     */
    private static function sendUntrackedCopy(Email $message, array $data): void
    {
        $recipients = array_map(fn (Address $a) => $a->getAddress(), $message->getBcc());
        $message->getHeaders()->remove('Bcc');

        $copy = MailCopy::addressFor($message, $data);
        if ($copy && ! in_array(mb_strtolower($copy), array_map('mb_strtolower', $recipients), true)) {
            $recipients[] = $copy;
        }
        if (! $recipients) {
            return;
        }

        $clone = clone $message;
        $clone->getHeaders()->remove(self::REF_HEADER);
        $clone->getHeaders()->remove('Cc');
        $clone->to(...$recipients);
        if (is_string($html = $clone->getHtmlBody())) {
            $clone->html(self::markAsCopy($html));
        }

        rescue(fn () => Mail::mailer()->getSymfonyTransport()->send($clone));
    }

    /** Les liens du site dans la copie de Mickaël portent ?copie=1 : ses visites ne sont pas comptées. */
    private static function markAsCopy(string $html): string
    {
        $base = preg_quote(rtrim((string) config('app.url'), '/'), '/');

        return preg_replace_callback('/href="('.$base.'[^"]*)"/i', function ($m) {
            $url = $m[1];
            $sep = str_contains($url, '?') ? '&amp;' : '?';

            return 'href="'.$url.$sep.ClientTracking::COPY_PARAM.'=1"';
        }, $html);
    }

    /** Remplace les liens du site par des liens de redirection suivis et ajoute le pixel d'ouverture. */
    private static function trackHtml(string $html, string $token, array &$links): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $index = [];

        $html = preg_replace_callback('/<a\b([^>]*?)href="([^"]+)"([^>]*)>(.*?)<\/a>/is', function ($m) use ($base, $token, &$links, &$index) {
            $url = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5);
            if (! str_starts_with($url, $base.'/') && $url !== $base) {
                return $m[0];
            }
            $label = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($m[4]), ENT_QUOTES | ENT_HTML5)));
            if (! isset($index[$url])) {
                $index[$url] = count($links);
                $links[] = ['url' => $url, 'label' => ($label === '' || str_starts_with($label, 'http')) ? 'Lien' : mb_substr($label, 0, 120)];
            } elseif ($links[$index[$url]]['label'] === 'Lien' && $label !== '' && ! str_starts_with($label, 'http')) {
                $links[$index[$url]]['label'] = mb_substr($label, 0, 120);
            }

            return '<a'.$m[1].'href="'.e(route('mail.click', [$token, $index[$url]])).'"'.$m[3].'>'.$m[4].'</a>';
        }, $html);

        $pixel = '<img src="'.e(route('mail.open', $token)).'" width="1" height="1" alt="" style="display:block;width:1px;height:1px;border:0;margin:0;padding:0;">';

        return stripos($html, '</body>') !== false
            ? preg_replace('/<\/body>/i', $pixel.'</body>', $html, 1)
            : $html.$pixel;
    }
}
