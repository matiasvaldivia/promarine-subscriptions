<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'enabled'          => 'boolean',
        'allow_backorder'  => 'boolean',
        'last_synced_at'   => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(SubscriptionPlan::class, 'product_variant_id');
    }

    public function matrixRows(): HasMany
    {
        return $this->hasMany(ProductSubscriptionMatrix::class, 'variant_id');
    }

    public function inventoryLevels(): HasMany
    {
        return $this->hasMany(InventoryLevel::class, 'variant_id');
    }

    public function primaryInventoryLevel(): HasOne
    {
        return $this->hasOne(InventoryLevel::class, 'variant_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }
}
