<?php

namespace App\Mail;

use App\Models\CompanySetting;
use App\Support\MailTracking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * E-mail rédigé à la main dans l'admin (devis, facture) : texte libre, bouton « Consulter » optionnel,
 * liens « Télécharger le PDF » ($downloads : [['label', 'url'], ...]), pièces jointes, suivi ($tracking, voir MailTracking::tag).
 */
class DocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{data?: string, disk?: string, path?: string, name: string, mime?: string}>  $files
     */
    public function __construct(
        public string $mailSubject,
        public string $body,
        public array $files = [],
        public ?string $buttonLabel = null,
        public ?string $buttonUrl = null,
        public ?string $replyAddress = null,
        public array $downloads = [],
        array $tracking = [],
    ) {
        if ($tracking) {
            MailTracking::tag($this, $tracking);
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
            replyTo: $this->replyAddress ? [$this->replyAddress] : [],
        );
    }

    public function content(): Content
    {
        $settings = CompanySetting::current();

        return new Content(markdown: 'mail.document', with: [
            'paragraphs' => self::toMarkdown($this->body),
            'signature' => $settings->email_signature ?: $settings->displayName(),
            'preheader' => self::preheader($this->body),
        ]);
    }

    public function attachments(): array
    {
        return array_map(fn (array $f) => isset($f['data'])
            ? Attachment::fromData(fn () => $f['data'], $f['name'])->withMime($f['mime'] ?? 'application/pdf')
            : Attachment::fromStorageDisk($f['disk'] ?? 'local', $f['path'])->as($f['name']),
            $this->files);
    }

    /** Aperçu dans la boîte de réception : le premier paragraphe après « Bonjour, ». */
    public static function preheader(string $text): ?string
    {
        $blocks = array_values(array_filter(array_map('trim', preg_split("/\n\s*\n/", str_replace("\r\n", "\n", $text)))));
        if ($blocks && preg_match('/^(bonjour|bonsoir|madame|monsieur|cher|chère)\b[^\n]{0,60}$/iu', $blocks[0])) {
            array_shift($blocks);
        }
        $first = preg_replace('/\s+/u', ' ', $blocks[0] ?? '');

        return $first !== '' ? Str::limit($first, 140) : null;
    }

    /** Texte saisi → Markdown sûr : HTML échappé, retours à la ligne conservés, lignes « - » en liste. */
    public static function toMarkdown(string $text): string
    {
        $blocks = preg_split("/\n\s*\n/", trim(str_replace("\r\n", "\n", $text)));

        return collect($blocks)->map(function (string $block) {
            $lines = array_map(fn ($l) => e(rtrim($l)), explode("\n", $block));
            $lines = array_map(fn ($l) => preg_replace('/^\s*[•\-]\s+/u', '- ', ltrim($l)), $lines);

            return implode("\n", array_map(
                fn ($l, $i) => $l.(! str_starts_with($l, '- ') && $i < count($lines) - 1 && ! str_starts_with($lines[$i + 1], '- ') ? '  ' : ''),
                $lines, array_keys($lines)
            ));
        })->implode("\n\n");
    }
}
