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
        'nama_produk',        // TAMBAH FIELD INI
        'deskripsi',
        'jumlah',
        'satuan',            // TAMBAH FIELD INI
        'harga_satuan',
        'total',
        'keterangan',        // TAMBAH FIELD INI
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

        static::saving(function ($detail) {
            $detail->total = $detail->jumlah * $detail->harga_satuan;
        });

        static::saved(function ($detail) {
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

    public function scopeForPoCustomer($query, $poCustomerId)
    {
        return $query->where('id_po_customer', $poCustomerId);
    }
}
