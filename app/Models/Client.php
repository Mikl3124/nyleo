<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Client extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = ['remember_token'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // L'adresse du client change : les terrains marqués « identique » suivent.
        static::saved(function (Client $client) {
            if ($client->wasChanged(['address', 'postal_code', 'city'])) {
                $client->dossiers()->where('site_same_as_client', true)->get()->each->save();
            }
            // Partenaire choisi après coup : ses dossiers sans partenaire lui sont rattachés.
            if ($client->partner_id && $client->wasChanged('partner_id')) {
                $client->dossiers()->whereNull('partner_id')->update(['partner_id' => $client->partner_id]);
            }
        });
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class);
    }

    /** Le dossier le plus récent : son adresse de projet s'affiche sur la fiche client. */
    public function currentDossier(): HasOne
    {
        return $this->hasOne(Dossier::class)->latestOfMany();
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /** Prospect : contact arrivé par la demande de devis du site, sans devis signé pour l'instant. */
    public function isProspect(): bool
    {
        if ($this->source !== 'site') {
            return false;
        }

        $signed = $this->getAttribute('has_signed_quote')
            ?? $this->quotes()->where('status', QuoteStatus::Signe->value)->exists();

        return ! $signed;
    }

    public function scopeProspects(Builder $query): Builder
    {
        return $query->where('source', 'site')
            ->whereDoesntHave('quotes', fn (Builder $query) => $query->where('status', QuoteStatus::Signe->value));
    }

    public function scopeWithSignedQuoteFlag(Builder $query): Builder
    {
        return $query->withExists(['quotes as has_signed_quote' => fn (Builder $query) => $query->where('status', QuoteStatus::Signe->value)]);
    }

    public function getFullNameAttribute(): string
    {
        $name = trim(($this->civility ? $this->civility.' ' : '').$this->first_name.' '.$this->last_name);

        return $this->type === 'professionnel' && $this->company_name
            ? $this->company_name.($name ? ' ('.$name.')' : '')
            : $name;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->type === 'professionnel' && $this->company_name
            ? $this->company_name
            : trim($this->first_name.' '.$this->last_name);
    }

    public function fullAddress(): string
    {
        return trim(collect([$this->address, trim($this->postal_code.' '.$this->city)])->filter()->implode("\n"));
    }

    public function snapshot(): array
    {
        return [
            'name' => $this->full_name,
            'company_name' => $this->company_name,
            'siret' => $this->siret,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'email' => $this->email,
            'phone' => $this->phone,
            'type' => $this->type,
        ];
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
