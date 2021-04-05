<?php

namespace App\Model;

use App\Model\Paiement;
use App\Notifications\ResetPassword;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function projets()
    {
        return $this->hasOne(Projet::class);
    }


    public function quotes()
    {
        return $this->hasOne(Projet::class);
    }

    public function simplequotes()
    {
        return $this->hasOne(Projet::class);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }

    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }
}
