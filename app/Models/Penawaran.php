<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Penawaran extends Model
{
    use HasFactory;

    protected $table = 'penawaran';

    protected $fillable = [
        'id_user',
        'nomor_penawaran',
        'id_customer',
        'tanggal',
        'deskripsi',
        'harga',
        'status',
        'terms_conditions',
        'total_sebelum_pajak',
        'total_pajak',
        'tax_rate',
    ];

    protected $attributes = [
        'harga' => 0,
        'total_sebelum_pajak' => 0,
        'total_pajak' => 0,
        'tax_rate' => 11.00,
        'status' => 'draft',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'harga' => 'decimal:2',
        'total_sebelum_pajak' => 'decimal:2',
        'total_pajak' => 'decimal:2',
        'tax_rate' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PenawaranDetail::class, 'penawaran_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($penawaran) {
            if (empty($penawaran->nomor_penawaran)) {
                $penawaran->nomor_penawaran = 'QTN-' . date('Ymd') . '-' . str_pad(
                    static::whereDate('created_at', today())->count() + 1,
                    3,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }
}   