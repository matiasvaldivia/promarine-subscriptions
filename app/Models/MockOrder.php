<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MockOrder extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'is_mock'        => 'boolean',
        'transmitted_at' => 'datetime',
        'confirmed_at'   => 'datetime',
        'dispatched_at'  => 'datetime',
        'delivered_at'   => 'datetime',
        'cancelled_at'   => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(MockSubscription::class, 'mock_subscription_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(MockPayment::class, 'mock_payment_id');
    }

    public function fulfillment(): HasOne
    {
        return $this->hasOne(Fulfillment::class, 'mock_order_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'mock_order_id')->orderBy('created_at');
    }

    public function igsEvents(): HasMany
    {
        return $this->hasMany(MockIgsEvent::class, 'mock_order_id');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(AdministrativeNote::class, 'notable');
    }

    public function scopeByInternalStatus($query, string $status)
    {
        return $query->where('internal_status', $status);
    }

    public function scopeDelivered($query)
    {
        return $query->where('internal_status', 'delivered');
    }

    public function scopeTransmitted($query)
    {
        return $query->whereIn('internal_status', ['transmitted', 'confirmed_by_shopify']);
    }

    public function scopeWithError($query)
    {
        return $query->where('internal_status', 'sync_error');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->internal_status) {
            'delivered'                        => 'badge-success',
            'shipped', 'ready_to_ship'         => 'badge-info',
            'transmitted', 'confirmed_by_shopify', 'preparing' => 'badge-primary',
            'payment_approved', 'ready_to_transmit' => 'badge-warning',
            'cancelled', 'failed'              => 'badge-error',
            'sync_error'                       => 'badge-error',
            default                            => 'badge-neutral',
        };
    }
}
