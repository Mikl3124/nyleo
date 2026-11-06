<?php

namespace App\Models;

use App\Enums\WaitingOn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Une demande de pièces complémentaires de la mairie. */
class PieceRequest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'requested_at' => 'date',
            'deadline' => 'date',
            'sent_at' => 'date',
            'waiting_on' => WaitingOn::class,
        ];
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }

    public function isOpen(): bool
    {
        return $this->sent_at === null;
    }

    public function isLate(): bool
    {
        return $this->isOpen() && $this->deadline !== null && $this->deadline->isPast();
    }
}
