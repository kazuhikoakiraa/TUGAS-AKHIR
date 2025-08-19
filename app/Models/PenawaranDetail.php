<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenawaranDetail extends Model
{
    use HasFactory;

    protected $table = 'penawaran_details';

    protected $fillable = [
        'penawaran_id',
        'product_id',
        'nama_produk',
        'deskripsi',
        'jumlah',
        'satuan',
        'harga_satuan',
        'total',
        'keterangan',
    ];

    protected $attributes = [
        'nama_produk' => '',
        'jumlah' => 1,
        'satuan' => 'pcs',
        'harga_satuan' => 0,
        'total' => 0,
    ];

    protected $casts = [
        'jumlah' => 'integer',
        'harga_satuan' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function penawaran(): BelongsTo
    {
        return $this->belongsTo(Penawaran::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
