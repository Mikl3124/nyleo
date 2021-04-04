<?php

namespace App\Model;

use App\Model\Projet;
use Illuminate\Database\Eloquent\Model;

class Avantprojet extends Model
{
    public function projets()
    {
        return $this->hasOne(Projet::class);
    }
}
