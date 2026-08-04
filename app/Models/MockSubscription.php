<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MockSubscription extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'metadata_json'  => 'array',
        'is_mock'        => 'boolean',
        'next_billing_at'=> 'datetime',
        'started_at'     => 'datetime',
        'paused_at'      => 'datetime',
        'cancelled_at'   => 'datetime',
        'resumed_at'     => 'datetime',
        'expired_at'     => 'datetime',
    ];


    public function payments(): HasMany
    {
        return $this->hasMany(MockPayment::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(MockOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(MockCustomer::class, 'customer_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(AdministrativeNote::class, 'notable');
    }

    public function latestPayment()
    {
        return $this->payments()->latest()->first();
    }

    public function latestOrder()
    {
        return $this->orders()->latest()->first();
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['payment_approved', 'authorized', 'active']);
    }

    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
}
