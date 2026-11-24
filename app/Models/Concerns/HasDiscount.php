<?php

namespace App\Models\Concerns;

use App\Support\Totals;

/** Devis ou facture : remise globale et totaux (options non comprises). */
trait HasDiscount
{
    /** Recalcule les totaux à partir des lignes ; renvoie le détail (sous-total, remise, options…). */
    protected function computeTotals(): array
    {
        $this->load('lines');
        foreach ($this->lines as $line) {
            $net = Totals::lineNet($line);
            if ((float) $line->total_ht !== $net) {
                $line->updateQuietly(['total_ht' => $net]);
            }
        }
        if (! in_array($this->discount_type, [Totals::PERCENT, Totals::AMOUNT], true) || (float) $this->discount_value <= 0) {
            $this->discount_type = null;
            $this->discount_value = null;
        }

        return Totals::compute($this->lines, $this->discount_type, $this->discount_value);
    }

    /** Somme des lignes avant remise globale (options exclues). */
    public function subtotal(): float
    {
        return round((float) $this->total_ht + (float) $this->discount_amount, 2);
    }

    public function optionsTotal(): float
    {
        return round($this->lines->filter(fn ($l) => $l->is_option)->sum(fn ($l) => (float) $l->total_ht), 2);
    }

    /** « Remise (10 %) » ou « Remise ». */
    public function discountLabel(): string
    {
        return $this->discount_type === Totals::PERCENT ? 'Remise ('.Totals::describe($this->discount_type, $this->discount_value).')' : 'Remise';
    }
}
