<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quote()
    {
        return $this->belongsTo(User::class);
    }
}
