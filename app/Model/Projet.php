<?php

namespace App\Model;

use App\Model\Avantprojet;
use Illuminate\Database\Eloquent\Model;

class Projet extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function avantprojet()
    {
        return $this->belongsTo(Avantprojet::class);
    }
}
