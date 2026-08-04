<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSubscriptionMatrix extends Model
{
    protected $table = 'product_subscription_matrix';
    protected $guarded = [];

    protected $casts = [
        'shipping_included'    => 'boolean',
        'pause_allowed'        => 'boolean',
        'cancellation_allowed' => 'boolean',
        'metadata_json'        => 'array',
        'valid_from'           => 'date',
        'valid_until'          => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /** Precio final calculado según tipo de descuento */
    public function calculatedPrice(): float
    {
        if ($this->discount_type === 'percentage') {
            return round($this->base_price * (1 - $this->discount_value / 100), 2);
        }
        return max(0, $this->base_price - $this->discount_value);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
