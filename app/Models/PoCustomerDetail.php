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
        'product_id',        // Untuk Product PO (nullable untuk service)
        'nama_produk',       // Nama produk/service
        'deskripsi',
        'jumlah',
        'satuan',
        'harga_satuan',
        'total',
        'keterangan',
    ];

    protected $casts = [
        'jumlah' => 'integer',
        'harga_satuan' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relasi ke PO Customer
    public function poCustomer(): BelongsTo
    {
        return $this->belongsTo(PoCustomer::class, 'id_po_customer');
    }

    // Relasi ke Product
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    protected static function boot()
    {
        parent::boot();

        // Auto calculate total dan fill data saat saving
        static::saving(function ($detail) {
            // Auto fill dari product jika ada (untuk Product PO)
            if ($detail->product_id && !$detail->nama_produk) {
                $product = Product::find($detail->product_id);
                if ($product) {
                    $detail->nama_produk = $product->name;
                    $detail->satuan = $product->unit;
                    $detail->harga_satuan = $product->unit_price;
                    $detail->deskripsi = $product->description;
                }
            }

            // Pastikan quantity minimal 1 dan bertipe integer
            $detail->jumlah = max(1, (int) $detail->jumlah);

            // Pastikan harga_satuan adalah numeric
            $detail->harga_satuan = (float) $detail->harga_satuan;

            // Calculate total - selalu recalculate untuk memastikan konsistensi
            $detail->total = $detail->jumlah * $detail->harga_satuan;

            // Untuk service items, pastikan data konsisten
            if (is_null($detail->product_id)) {
                // Ini adalah service item
                if (empty($detail->satuan)) {
                    $detail->satuan = 'service';
                }
                // Service biasanya quantity = 1, tapi bisa diubah jika dibutuhkan
                // Tapi untuk consistency, kita set ke 1
                if ($detail->poCustomer && $detail->poCustomer->jenis_po === 'Service') {
                    $detail->jumlah = 1;
                    $detail->total = $detail->harga_satuan; // Recalculate
                }
            }
        });

        // Update PO totals setelah save/delete - hanya jika tidak sedang batch update
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

    // Flag untuk batch updates
    protected static $updating_batch = false;

    /**
     * Prevent automatic total updates during batch operations
     */
    public static function withoutUpdatingTotals(callable $callback)
    {
        static::$updating_batch = true;

        try {
            return $callback();
        } finally {
            static::$updating_batch = false;
        }
    }

    // Accessor untuk format currency
    public function getFormattedHargaSatuanAttribute(): string
    {
        return 'Rp ' . number_format($this->harga_satuan, 0, ',', '.');
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total, 0, ',', '.');
    }

    // Scope untuk filter berdasarkan PO
    public function scopeForPoCustomer($query, $poCustomerId)
    {
        return $query->where('id_po_customer', $poCustomerId);
    }

    // Check apakah ini product atau service
    public function isProduct(): bool
    {
        return !is_null($this->product_id);
    }

    public function isService(): bool
    {
        return is_null($this->product_id);
    }

    // Helper untuk mendapatkan type
    public function getTypeAttribute(): string
    {
        return $this->isProduct() ? 'Product' : 'Service';
    }

    // Helper untuk display name dengan type indicator
    public function getDisplayNameAttribute(): string
    {
        $prefix = $this->isProduct() ? '📦' : '🛠️';
        return $prefix . ' ' . $this->nama_produk;
    }

    // Helper untuk quantity display (hide qty untuk service yang selalu 1)
    public function getQuantityDisplayAttribute(): string
    {
        if ($this->isService() && $this->jumlah == 1) {
            return '1 ' . $this->satuan;
        }
        return $this->jumlah . ' ' . $this->satuan;
    }

    /**
     * Mutator untuk memastikan jumlah selalu integer dan minimal 1
     */
    public function setJumlahAttribute($value)
    {
        $this->attributes['jumlah'] = max(1, (int) $value);
    }

    /**
     * Mutator untuk memastikan harga_satuan selalu float
     */
    public function setHargaSatuanAttribute($value)
    {
        $this->attributes['harga_satuan'] = (float) $value;
    }

    /**
     * Mutator untuk memastikan total selalu float
     */
    public function setTotalAttribute($value)
    {
        $this->attributes['total'] = (float) $value;
    }

    /**
     * Method untuk recalculate total
     */
    public function recalculateTotal(): void
    {
        $this->total = $this->jumlah * $this->harga_satuan;
    }

    /**
     * Method untuk validate data sebelum save
     */
    public function validateData(): array
    {
        $errors = [];

        if (empty($this->nama_produk)) {
            $errors[] = 'Product/Service name is required';
        }

        if ($this->harga_satuan <= 0) {
            $errors[] = 'Unit price must be greater than 0';
        }

        if ($this->jumlah < 1) {
            $errors[] = 'Quantity must be at least 1';
        }

        // Validate product_id for Product PO
        if ($this->poCustomer && $this->poCustomer->jenis_po === 'Product' && !$this->product_id) {
            $errors[] = 'Product selection is required for Product PO';
        }

        return $errors;
    }
}
