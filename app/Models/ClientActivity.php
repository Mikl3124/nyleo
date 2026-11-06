<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ce que le client a fait : ouvert un e-mail, cliqué un bouton, consulté une page, téléchargé un PDF.
 * Les actions de Mickaël (copie cachée, admin connecté) ne sont jamais enregistrées.
 */
class ClientActivity extends Model
{
    public const OPENED = 'ouverture';

    public const CLICKED = 'clic';

    public const VIEWED = 'consultation';

    public const DOWNLOADED = 'telechargement';

    protected $guarded = ['id'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function emailMessage(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class);
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /** Une vraie lecture (clic, page, PDF) et pas seulement l'ouverture de l'e-mail. */
    public function isRead(): bool
    {
        return $this->type !== self::OPENED;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::OPENED => 'E-mail ouvert',
            self::CLICKED => 'Clic',
            self::VIEWED => 'Consultation',
            self::DOWNLOADED => 'Téléchargement',
            default => ucfirst($this->type),
        };
    }

    public function color(): string
    {
        return match ($this->type) {
            self::OPENED => 'info',
            self::DOWNLOADED => 'success',
            default => 'primary',
        };
    }

    /** « 06/11 à 14 h 02 » (l'année n'est ajoutée que si ce n'est pas l'année en cours). */
    public static function when(CarbonInterface $date): string
    {
        $date = $date->copy()->timezone(config('app.timezone'));

        // Espaces insécables : « 14 h 02 » ne se coupe jamais en fin de ligne.
        return $date->format($date->isCurrentYear() ? 'd/m' : 'd/m/Y')."\u{00A0}à\u{00A0}".$date->format('G')."\u{00A0}h\u{00A0}".$date->format('i');
    }
}
