<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'unit_price',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Scope untuk produk yang aktif
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // Accessor untuk format currency
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->unit_price, 0, ',', '.');
    }

    // Mutator untuk SKU uppercase
    public function setSkuAttribute($value): void
    {
        $this->attributes['sku'] = strtoupper($value);
    }
}
