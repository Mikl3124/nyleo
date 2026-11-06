<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use SoftDeletes;

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
            'deposit_percent' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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
        $this->load('lines');
        $ht = 0;
        $vat = 0;
        foreach ($this->lines as $line) {
            $lineHt = round((float) $line->quantity * (float) $line->unit_price, 2);
            if ((float) $line->total_ht !== $lineHt) {
                $line->updateQuietly(['total_ht' => $lineHt]);
            }
            $ht += $lineHt;
            $vat += round($lineHt * (float) $line->vat_rate / 100, 2);
        }
        $this->forceFill(['total_ht' => $ht, 'total_vat' => $vat, 'total_ttc' => $ht + $vat])->saveQuietly();
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
