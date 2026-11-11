<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Concerns\SyncsDossierStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;
    use SyncsDossierStatus;

    protected $guarded = ['id'];

    /** Paiement par carte proposé par défaut (Mickaël, 2026-11-11) ; décochable facture par facture. */
    protected $attributes = ['card_payment' => true];

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'issued_at' => 'datetime',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'last_reminder_at' => 'datetime',
            'client_snapshot' => 'array',
            'company_snapshot' => 'array',
            'total_ht' => 'decimal:2',
            'total_vat' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'deposits_deducted' => 'decimal:2',
            'amount_due' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'card_payment' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Une facture émise est figée : on ne la supprime pas, on l'annule par un avoir.
        static::deleting(function (Invoice $invoice) {
            if ($invoice->number) {
                throw new \LogicException('Une facture émise ne peut pas être supprimée. Créez un avoir.');
            }
        });
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

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'parent_invoice_id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(Invoice::class, 'parent_invoice_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('position');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_at');
    }

    public function isDraft(): bool
    {
        return $this->status === InvoiceStatus::Brouillon;
    }

    public function isCreditNote(): bool
    {
        return $this->type === InvoiceType::Avoir;
    }

    public function remaining(): float
    {
        return max(0, round((float) $this->amount_due - (float) $this->amount_paid, 2));
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
        $ttc = $ht + $vat;
        $this->forceFill([
            'total_ht' => $ht,
            'total_vat' => $vat,
            'total_ttc' => $ttc,
            'amount_due' => max(0, $ttc - (float) $this->deposits_deducted),
        ])->saveQuietly();
    }

    public function displayNumber(): string
    {
        return $this->number ?: 'Brouillon #'.$this->id;
    }

    public function title(): string
    {
        return $this->type->label().' '.$this->displayNumber();
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
