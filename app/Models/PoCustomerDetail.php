<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoCustomerDetail extends Model
{
    use HasFactory;

    protected $table = 'po_customer_detail';

    protected $fillable = [
        'id_po_customer',
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

    public function poCustomer(): BelongsTo
    {
        return $this->belongsTo(PoCustomer::class, 'id_po_customer');
    }

    protected static function boot()
    {
        parent::boot();

        // PERBAIKAN: Hitung total saat saving
        static::saving(function ($detail) {
            $detail->total = $detail->jumlah * $detail->harga_satuan;
        });

        // PERBAIKAN: Update parent totals lebih aman
        static::saved(function ($detail) {
            // Pastikan relasi ada dan bukan dalam proses batch update
            if ($detail->poCustomer && !static::$updating_batch) {
                $detail->poCustomer->updateTotalsWithoutEvents();
            }
        });

        static::deleted(function ($detail) {
            if ($detail->poCustomer && !static::$updating_batch) {
                $detail->poCustomer->updateTotalsWithoutEvents();
            }
        });
    }

    // TAMBAHAN: Property untuk mencegah update berulang
    protected static $updating_batch = false;

    // TAMBAHAN: Method untuk batch operations
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

    // TAMBAHAN: Scope untuk filtering
    public function scopeForPoCustomer($query, $poCustomerId)
    {
        return $query->where('id_po_customer', $poCustomerId);
    }
}