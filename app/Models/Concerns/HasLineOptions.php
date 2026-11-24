<?php

namespace App\Models\Concerns;

use App\Support\Totals;

/** Ligne de devis ou de facture : remise éventuelle et marque « option ». */
trait HasLineOptions
{
    protected static function bootHasLineOptions(): void
    {
        static::saving(function ($line) {
            if (! in_array($line->discount_type, [Totals::PERCENT, Totals::AMOUNT], true) || (float) $line->discount_value <= 0) {
                $line->discount_type = null;
                $line->discount_value = null;
            }
            $line->total_ht = Totals::lineNet($line);
        });
    }

    public function grossAmount(): float
    {
        return Totals::lineGross($this);
    }

    public function discountAmount(): float
    {
        return round($this->grossAmount() - (float) $this->total_ht, 2);
    }

    /** « Remise 10 % » / « Remise 50,00 € », ou null sans remise. */
    public function discountLabel(): ?string
    {
        $d = Totals::describe($this->discount_type, $this->discount_value);

        return $d ? 'Remise '.$d : null;
    }
}
