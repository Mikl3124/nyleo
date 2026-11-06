<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteLine extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'unit_price' => 'decimal:2', 'vat_rate' => 'decimal:2', 'total_ht' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::saving(fn (QuoteLine $l) => $l->total_ht = round((float) $l->quantity * (float) $l->unit_price, 2));
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
