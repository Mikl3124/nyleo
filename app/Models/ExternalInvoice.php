<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalInvoice extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'synced_at' => 'immutable_datetime'];
    }
}
