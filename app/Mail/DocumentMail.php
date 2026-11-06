<?php

namespace App\Mail;

use App\Models\CompanySetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** E-mail rédigé à la main dans l'admin (devis, facture) : texte libre, bouton optionnel, pièces jointes. */
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
    ) {}

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
        ]);
    }

    public function attachments(): array
    {
        return array_map(fn (array $f) => isset($f['data'])
            ? Attachment::fromData(fn () => $f['data'], $f['name'])->withMime($f['mime'] ?? 'application/pdf')
            : Attachment::fromStorageDisk($f['disk'] ?? 'local', $f['path'])->as($f['name']),
            $this->files);
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
