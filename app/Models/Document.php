<?php

namespace App\Models;

use App\Enums\DocumentCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'visible_to_client' => 'boolean',
            'requires_validation' => 'boolean',
            'validated_at' => 'datetime',
            'change_requested_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Document $doc) {
            if ($doc->category === DocumentCategory::Plan && ! $doc->version) {
                $doc->version = (int) static::where('dossier_id', $doc->dossier_id)
                    ->where('category', DocumentCategory::Plan->value)->max('version') + 1;
            }
            if ($doc->path && ! $doc->size && Storage::disk('local')->exists($doc->path)) {
                $doc->size = Storage::disk('local')->size($doc->path);
                $doc->mime_type ??= Storage::disk('local')->mimeType($doc->path);
            }
        });

        static::deleted(function (Document $doc) {
            Storage::disk('local')->delete($doc->path);
        });
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }

    public function isAwaitingValidation(): bool
    {
        return $this->requires_validation && ! $this->validated_at && ! $this->change_requested_at;
    }

    public function displayName(): string
    {
        return $this->name.($this->version ? ' (V'.$this->version.')' : '');
    }
}
