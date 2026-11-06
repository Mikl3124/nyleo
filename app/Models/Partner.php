<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Partenaire (installateur, prescripteur) qui apporte des dossiers, commissionné ou non. */
class Partner extends Model
{
    protected $guarded = ['id'];

    public const COMMISSION_TYPES = ['percent' => '% du montant HT', 'fixed' => 'Montant fixe par dossier'];

    protected function casts(): array
    {
        return [
            'commission_value' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->company ? $this->name.' ('.$this->company.')' : $this->name;
    }

    /** Commission prévue par défaut pour un dossier de ce montant HT (null si le partenaire n'a pas de règle). */
    public function defaultCommission(?float $amount): ?float
    {
        if (! $this->commission_value) {
            return null;
        }

        return match ($this->commission_type) {
            'percent' => $amount === null ? null : round($amount * (float) $this->commission_value / 100, 2),
            'fixed' => (float) $this->commission_value,
            default => null,
        };
    }

    public function commissionRuleLabel(): ?string
    {
        if (! $this->commission_value) {
            return null;
        }

        return match ($this->commission_type) {
            'percent' => rtrim(rtrim(number_format((float) $this->commission_value, 2, ',', ' '), '0'), ',').' % du montant HT',
            'fixed' => money($this->commission_value).' par dossier',
            default => null,
        };
    }

    /** Totaux de suivi : nombre de dossiers, montants, commissions dues, payées et restant à payer. */
    public function totals(): array
    {
        $dossiers = $this->dossiers()->with(['quotes', 'partner'])->get();
        $commissioned = $dossiers->filter(fn (Dossier $d) => $d->commissionable);
        $paid = $commissioned->filter(fn (Dossier $d) => $d->commission_paid);

        return [
            'count' => $dossiers->count(),
            'commissioned_count' => $commissioned->count(),
            'amount' => $dossiers->sum(fn (Dossier $d) => $d->amountHt() ?? 0),
            'commission' => $commissioned->sum(fn (Dossier $d) => $d->commissionDue() ?? 0),
            'paid' => $paid->sum(fn (Dossier $d) => $d->commissionDue() ?? 0),
            'to_pay' => $commissioned->reject(fn (Dossier $d) => $d->commission_paid)->sum(fn (Dossier $d) => $d->commissionDue() ?? 0),
        ];
    }
}
