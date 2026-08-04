<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopifySyncItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload_json' => 'array',
        'processed_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(ShopifySyncRun::class, 'sync_run_id');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
