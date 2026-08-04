<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipSubscription extends Model
{
    protected $guarded = [];

    protected $casts = [
        'benefits_json' => 'array',
        'community_updates' => 'boolean',
        'consent_terms_at' => 'datetime',
        'is_mock' => 'boolean',
    ];
}
