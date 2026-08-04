<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fulfillment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'prepared_at'  => 'datetime',
        'shipped_at'   => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at'    => 'datetime',
        'is_mock'      => 'boolean',
        'metadata_json'=> 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(MockOrder::class, 'mock_order_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'delivered'   => 'badge-success',
            'shipped', 'in_transit' => 'badge-info',
            'failed'      => 'badge-error',
            'returned'    => 'badge-warning',
            default       => 'badge-neutral',
        };
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeInTransit($query)
    {
        return $query->whereIn('status', ['shipped', 'in_transit']);
    }
}
