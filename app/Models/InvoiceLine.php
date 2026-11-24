<?php

namespace App\Models;

use App\Models\Concerns\HasLineOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    use HasLineOptions;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'unit_price' => 'decimal:2', 'vat_rate' => 'decimal:2', 'total_ht' => 'decimal:2', 'is_option' => 'boolean', 'discount_value' => 'decimal:2'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
