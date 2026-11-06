<?php

namespace App\Models;

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
