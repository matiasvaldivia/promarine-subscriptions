<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MockPayment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_mock'      => 'boolean',
        'payload_json' => 'array',
        'approved_at'  => 'datetime',
        'rejected_at'  => 'datetime',
        'refunded_at'  => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(MockSubscription::class, 'mock_subscription_id');
    }

    public function order(): HasOne
    {
        return $this->hasOne(MockOrder::class, 'mock_payment_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }
}
