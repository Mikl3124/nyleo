<?php

namespace App\Model;

use App\Model\Paiement;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function option()
    {
        return $this->belongsToMany(Option::class);
    }

    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }
}
