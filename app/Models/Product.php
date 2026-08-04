<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'enabled'     => 'boolean',
        'featured'    => 'boolean',
        'is_imported' => 'boolean',
        'is_mock'     => 'boolean',
    ];

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function matrixRows(): HasMany
    {
        return $this->hasMany(ProductSubscriptionMatrix::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
