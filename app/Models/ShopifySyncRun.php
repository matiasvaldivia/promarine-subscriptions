<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ShopifySyncRun extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_at'    => 'datetime',
        'finished_at'   => 'datetime',
        'metadata_json' => 'array',
        'is_mock'       => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(fn($m) => $m->uuid ??= (string) Str::uuid());
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShopifySyncItem::class, 'sync_run_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at || !$this->finished_at) return null;
        $secs = $this->started_at->diffInSeconds($this->finished_at);
        return $secs < 60 ? "{$secs}s" : round($secs / 60, 1).'m';
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['completed', 'completed_with_errors']);
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('created_at');
    }
}
