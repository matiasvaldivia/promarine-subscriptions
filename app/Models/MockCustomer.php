<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MockCustomer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_mock' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(MockSubscription::class, 'customer_id');
    }
}
