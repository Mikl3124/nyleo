<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
