<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Models\Concerns\HasDiscount;
use App\Models\Concerns\SyncsDossierStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasDiscount;
    use SoftDeletes;
    use SyncsDossierStatus;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'issue_date' => 'date',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'signed_at' => 'datetime',
            'early_start_requested' => 'boolean',
            'terms_accepted_at' => 'datetime',
            'refused_at' => 'datetime',
            'last_reminder_at' => 'datetime',
            'client_snapshot' => 'array',
            'company_snapshot' => 'array',
            'total_ht' => 'decimal:2',
            'total_vat' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'deposit_percent' => 'decimal:2',
        ];
    }

    /** Le client reste lisible même s'il a été supprimé (corbeille) : le document ne doit jamais planter. */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuoteLine::class)->orderBy('position');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isEditable(): bool
    {
        return $this->status === QuoteStatus::Brouillon;
    }

    public function isSignable(): bool
    {
        return $this->status === QuoteStatus::Envoye && (! $this->valid_until || $this->valid_until->endOfDay()->isFuture());
    }

    public function recalculate(): void
    {
        $t = $this->computeTotals();
        $this->forceFill([
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'discount_amount' => $t['discount'],
            'total_ht' => $t['ht'],
            'total_vat' => $t['vat'],
            'total_ttc' => $t['ttc'],
        ])->saveQuietly();
    }

    public function depositAmount(): float
    {
        return round((float) $this->total_ttc * (float) $this->deposit_percent / 100, 2);
    }

    public function displayNumber(): string
    {
        return $this->number ?: 'Brouillon #'.$this->id;
    }

    public function emailMessages(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ClientActivity::class);
    }
}
