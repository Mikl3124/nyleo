<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Un e-mail envoyé à un client, avec ses ouvertures et ses clics (voir App\Support\MailTracking). */
class EmailMessage extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'links' => 'array',
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'last_opened_at' => 'datetime',
            'clicked_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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

    public function activities(): HasMany
    {
        return $this->hasMany(ClientActivity::class);
    }

    /** « Ouvert 2 fois, dernière le 06/11 à 14 h 02 », « Pas encore ouvert »… */
    public function statusLabel(): string
    {
        if ($this->clicked_at) {
            return 'Lien cliqué le '.ClientActivity::when($this->clicked_at);
        }
        if ($this->opened_at) {
            return 'Ouvert le '.ClientActivity::when($this->last_opened_at ?? $this->opened_at)
                .($this->open_count > 1 ? " ({$this->open_count} fois)" : '');
        }

        return $this->sent_at ? 'Pas encore ouvert' : 'Envoi en cours';
    }
}
