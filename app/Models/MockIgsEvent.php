<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MockIgsEvent extends Model
{
    protected $table = 'mock_igs_events';

    protected $guarded = [];

    protected $casts = [
        'is_mock'      => 'boolean',
        'payload_json' => 'array',
        'reversed_at'  => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(MockOrder::class, 'mock_order_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'recorded');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeReversed($query)
    {
        return $query->whereNotNull('reversed_at');
    }
}
