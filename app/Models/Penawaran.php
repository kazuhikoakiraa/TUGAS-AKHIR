<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected $casts = [
        'tanggal' => 'date',
        'harga' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($penawaran) {
            if (empty($penawaran->nomor_penawaran)) {
                $penawaran->nomor_penawaran = 'PNW-' . date('Ymd') . '-' . str_pad(
                    static::whereDate('created_at', today())->count() + 1,
                    3,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }
}