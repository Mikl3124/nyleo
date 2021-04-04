<?php

namespace App\Model;

use App\Model\Quote;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    protected $guarded = [];

    public function quote()
    {
        return $this->belongsToMany(Quote::class);
    }
}
