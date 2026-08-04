<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryLevel extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_mock'        => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    // Disponible = shopify_stock - reservado - comprometido
    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->available_quantity - $this->reserved_quantity - $this->committed_quantity);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'variant_id', 'variant_id');
    }

    public function recalculateSyncStatus(): void
    {
        $available = $this->available_stock;
        $threshold = $this->variant->low_stock_threshold ?? 10;

        $this->sync_status = match (true) {
            $available <= 0             => 'out_of_stock',
            $available <= $threshold    => 'low_stock',
            default                     => 'in_stock',
        };
        $this->last_synced_at = now();
        $this->saveQuietly();
    }

    public function scopeInStock($query)
    {
        return $query->where('sync_status', 'in_stock');
    }

    public function scopeLowStock($query)
    {
        return $query->where('sync_status', 'low_stock');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('sync_status', 'out_of_stock');
    }
}
