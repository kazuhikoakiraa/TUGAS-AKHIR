<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoice';

    protected $fillable = [
        'id_po_customer',
        'id_user',
        'nomor_invoice',
        'tanggal',
        'status',
        'total_sebelum_pajak',
        'total_pajak',
        'grand_total',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total_sebelum_pajak' => 'decimal:2',
        'total_pajak' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function poCustomer(): BelongsTo
    {
        return $this->belongsTo(PoCustomer::class, 'id_po_customer');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->nomor_invoice)) {
                $invoice->nomor_invoice = 'INV-' . date('Ymd') . '-' . str_pad(
                    static::whereDate('created_at', today())->count() + 1,
                    3,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });

        static::saving(function ($invoice) {
            $invoice->grand_total = $invoice->total_sebelum_pajak + $invoice->total_pajak;
        });
    }
}