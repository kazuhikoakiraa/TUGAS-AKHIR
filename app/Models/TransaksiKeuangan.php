<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class TransaksiKeuangan extends Model
{
    use HasFactory;

    protected $table = 'transaksi_keuangan';

    protected $fillable = [
        'id_po_supplier',
        'id_rekening',
        'tanggal',
        'jenis',
        'jumlah',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:2',
    ];

    public function poSupplier(): BelongsTo
    {
        return $this->belongsTo(PoSupplier::class, 'id_po_supplier');
    }

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(RekeningBank::class, 'id_rekening');
    }

    // Scope untuk filter jenis transaksi
    public function scopePemasukan(Builder $query): Builder
    {
        return $query->where('jenis', 'pemasukan');
    }

    public function scopePengeluaran(Builder $query): Builder
    {
        return $query->where('jenis', 'pengeluaran');
    }

    // Scope untuk filter periode
    public function scopeMingguIni(Builder $query): Builder
    {
        return $query->whereBetween('tanggal', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeBulanIni(Builder $query): Builder
    {
        return $query->whereMonth('tanggal', now()->month)
                    ->whereYear('tanggal', now()->year);
    }

    public function scopeTahunIni(Builder $query): Builder
    {
        return $query->whereYear('tanggal', now()->year);
    }
}
