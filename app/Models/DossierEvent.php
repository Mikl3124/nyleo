<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DossierEvent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['visible_to_client' => 'boolean', 'meta' => 'array'];
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }
}
