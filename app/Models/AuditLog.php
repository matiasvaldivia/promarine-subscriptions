<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'changes_json' => 'array',
        'before_json'  => 'array',
        'after_json'   => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByEntity($query, string $type, ?int $id = null)
    {
        $query->where('auditable_type', $type);
        if ($id) $query->where('auditable_id', $id);
        return $query;
    }
}
