<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalBillingAccount extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
