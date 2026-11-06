<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class);
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
}
