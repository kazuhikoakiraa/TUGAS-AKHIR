<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoSupplierDetail extends Model
{
    use HasFactory;

    // FIXED: Konsisten lowercase
    protected $table = 'po_supplier_detail';

    // FIXED: Foreign key konsisten lowercase
    protected $fillable = [
        'id_po_supplier',
        'deskripsi',
        'jumlah',
        'harga_satuan',
        'total',
    ];

    protected $casts = [
        'jumlah' => 'integer',
        'harga_satuan' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // FIXED: Foreign key konsisten
    public function poSupplier(): BelongsTo
    {
        return $this->belongsTo(PoSupplier::class, 'id_po_supplier');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($detail) {
            $detail->total = $detail->jumlah * $detail->harga_satuan;
        });

        static::saved(function ($detail) {
            if ($detail->poSupplier && !static::$updating_batch) {
                $detail->poSupplier->updateTotalsWithoutEvents();
            }
        });

        static::deleted(function ($detail) {
            if ($detail->poSupplier && !static::$updating_batch) {
                $detail->poSupplier->updateTotalsWithoutEvents();
            }
        });
    }

    protected static $updating_batch = false;

    public static function withoutUpdatingTotals(callable $callback)
    {
        static::$updating_batch = true;

        try {
            return $callback();
        } finally {
            static::$updating_batch = false;
        }
    }

    public function getFormattedHargaSatuanAttribute(): string
    {
        return 'Rp ' . number_format($this->harga_satuan, 0, ',', '.');
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total, 0, ',', '.');
    }

    // FIXED: Foreign key konsisten
    public function scopeForPoSupplier($query, $poSupplierId)
    {
        return $query->where('id_po_supplier', $poSupplierId);
    }
}