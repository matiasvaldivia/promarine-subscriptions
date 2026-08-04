<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    protected $table   = 'order_status_history';
    protected $guarded = [];

    protected $casts = [
        'metadata_json' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(MockOrder::class, 'mock_order_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
