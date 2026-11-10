<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReceiptEntry extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['received_on' => 'immutable_date', 'evidence' => 'array', 'gross_minor' => 'integer', 'vat_minor' => 'integer'];
    }

    public function getDeclarationCategoryAttribute(): ?string
    {
        return DB::table('receipt_classifications')->where('receipt_entry_id', $this->id)->value('category') ?: $this->category;
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Une recette enregistrée ne peut pas être modifiée.'));
        static::deleting(fn () => throw new \LogicException('Une recette enregistrée ne peut pas être supprimée.'));
    }
}
