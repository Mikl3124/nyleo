<?php

namespace App\Notifications;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Dossier;
use App\Services\DocumentMailer;
use App\Support\MailTracking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

abstract class ClientNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /** Lien de connexion directe (sans mot de passe) valable 30 jours. */
    public static function portalUrl(Client $client, ?string $to = null): string
    {
        return URL::temporarySignedRoute('portal.magic', now()->addDays(30), array_filter([
            'client' => $client->id,
            'to' => $to,
        ]));
    }

    /** « Bonjour Monsieur Martin, », « Bonjour Jean Martin, » ou « Bonjour, ». */
    public static function greeting(Client $client): string
    {
        $name = $client->civility
            ? trim($client->civility.' '.$client->last_name)
            : trim($client->first_name.' '.$client->last_name);

        return 'Bonjour'.($name !== '' ? ' '.$name : '').',';
    }

    /**
     * Base commune : objet, titre, salutation, signature, ligne d'aide et adresse de réponse
     * (les réponses vont à l'adresse des paramètres, mickael@nyleo.fr).
     */
    protected function message(Client $client, string $subject, ?string $title = null, ?string $eyebrow = null): MailMessage
    {
        $settings = CompanySetting::current();
        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting(self::greeting($client))
            ->salutation(new HtmlString(
                'Cordialement,<br>'."\n".nl2br(e($settings->email_signature ?: $settings->displayName()))
            ));
        $mail->viewData = [
            'title' => $title,
            'eyebrow' => $eyebrow,
            'help' => $settings->phone && ! str_contains(preg_replace('/\D/', '', (string) $settings->email_signature), preg_replace('/\D/', '', $settings->phone))
                ? "Une question ? Appelez-moi au {$settings->phone}, ou répondez simplement à cet e-mail."
                : 'Une question ? Répondez simplement à cet e-mail ou appelez-moi.',
        ];
        if ($reply = DocumentMailer::replyAddress()) {
            $mail->replyTo($reply);
        }

        return $mail;
    }

    /** Récapitulatif affiché sous le texte (libellé => valeur ; les valeurs vides sont masquées). */
    protected function recap(MailMessage $mail, array $rows): MailMessage
    {
        $mail->viewData['recap'] = $rows;

        return $mail;
    }

    protected function preheader(MailMessage $mail, string $text): MailMessage
    {
        $mail->viewData['preheader'] = $text;

        return $mail;
    }

    /** Boutons « Télécharger le PDF » sous le bouton principal : [['label' => ..., 'url' => ...], ...]. */
    protected function downloads(MailMessage $mail, array $items): MailMessage
    {
        $mail->viewData['downloads'] = array_values(array_filter($items));

        return $mail;
    }

    /** Suivi de l'e-mail (ouverture, clics) rattaché au client et au document concerné. */
    protected function track(MailMessage $mail, object $notifiable, array $context): MailMessage
    {
        return MailTracking::tag($mail, $context + ['client' => $notifiable->id ?? null]);
    }

    /** Encadré ocre (moyens de paiement…). */
    /** Bouton ocre « Payer par carte » au-dessus du bouton principal, qui passe alors en bleu. */
    protected function payButton(MailMessage $mail, ?string $url, string $label): MailMessage
    {
        if ($url) {
            $mail->viewData['payUrl'] = $url;
            $mail->viewData['payLabel'] = $label;
            $mail->viewData['buttonColor'] = 'secondary';
        }

        return $mail;
    }

    protected function panel(MailMessage $mail, string $title, array $lines): MailMessage
    {
        $mail->viewData['panelTitle'] = $title;
        $mail->viewData['panelLines'] = array_values(array_filter($lines));

        return $mail;
    }

    protected static function project(?Dossier $dossier): ?string
    {
        return $dossier ? trim($dossier->title.($dossier->reference ? " ({$dossier->reference})" : '')) : null;
    }

    /** Lignes « par virement… / par chèque… » reprises dans les factures et les rappels. */
    protected static function paymentLines(CompanySetting $s, string $reference, ?string $cardUrl = null): array
    {
        return [
            $cardUrl ? '**Par carte bancaire** : en ligne, avec le bouton « Payer par carte » ci-dessous (paiement sécurisé).' : null,
            $s->iban ? '**Par virement** : IBAN '.$s->iban.($s->bic ? ' (BIC '.$s->bic.')' : '').", en indiquant la référence **{$reference}**." : null,
            $s->cheque_order ? "**Par chèque** à l'ordre de ".$s->cheque_order.($s->cheque_address ? ', à envoyer à : '.str_replace("\n", ', ', $s->cheque_address) : '').'.' : null,
        ];
    }
}
