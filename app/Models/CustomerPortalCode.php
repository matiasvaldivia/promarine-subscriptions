<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPortalCode extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];
}
