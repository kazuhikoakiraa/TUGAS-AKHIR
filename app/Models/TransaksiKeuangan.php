<?php

namespace App\Models;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'referensi_type',
        'referensi_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:2',
    ];

    // Relationships
    public function poSupplier(): BelongsTo
    {
        return $this->belongsTo(PoSupplier::class, 'id_po_supplier');
    }

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(RekeningBank::class, 'id_rekening');
    }

    /**
     * Relationship untuk invoice menggunakan referensi system
     */
    public function invoice(): ?Invoice
    {
        if ($this->referensi_type === 'invoice' && $this->referensi_id) {
            return Invoice::find($this->referensi_id);
        }
        return null;
    }

    /**
     * Accessor untuk mendapatkan invoice (backward compatibility)
     */
    public function getInvoiceAttribute(): ?Invoice
    {
        return $this->invoice();
    }

    // Scopes untuk filter jenis transaksi
    public function scopePemasukan(Builder $query): Builder
    {
        return $query->where('jenis', 'pemasukan');
    }

    public function scopePengeluaran(Builder $query): Builder
    {
        return $query->where('jenis', 'pengeluaran');
    }

    // Scopes untuk filter periode
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

    public function scopePeriode(Builder $query, $start, $end): Builder
    {
        return $query->whereBetween('tanggal', [$start, $end]);
    }

    // Scope untuk filter rekening
    public function scopeByRekening(Builder $query, $rekeningId): Builder
    {
        return $query->where('id_rekening', $rekeningId);
    }

    // Scope untuk filter berdasarkan referensi
    public function scopeByReferensi(Builder $query, string $type, $id): Builder
    {
        return $query->where('referensi_type', $type)
                    ->where('referensi_id', $id);
    }

    // Accessor untuk format jumlah
    protected function formattedJumlah(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp ' . number_format($this->jumlah, 0, ',', '.')
        );
    }

    // Accessor untuk mendapatkan tipe icon
    protected function jenisIcon(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->jenis) {
                'pemasukan' => 'heroicon-m-arrow-trending-up',
                'pengeluaran' => 'heroicon-m-arrow-trending-down',
                default => 'heroicon-o-currency-dollar'
            }
        );
    }

    // Accessor untuk mendapatkan warna badge
    protected function jenisColor(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->jenis) {
                'pemasukan' => 'success',
                'pengeluaran' => 'danger',
                default => 'gray'
            }
        );
    }

    // Method untuk mendapatkan referensi lengkap
    public function getReferensiLengkap(): ?string
    {
        // Untuk PO Supplier
        if ($this->poSupplier) {
            return "PO Supplier: {$this->poSupplier->nomor_po} - {$this->poSupplier->supplier->nama}";
        }

        // Untuk Invoice menggunakan referensi system
        if ($this->referensi_type === 'invoice' && $this->referensi_id) {
            $invoice = Invoice::find($this->referensi_id);
            if ($invoice) {
                $customerName = $invoice->poCustomer?->customer?->nama ?? 'Customer tidak ditemukan';
                return "Invoice: {$invoice->nomor_invoice} - {$customerName}";
            }
        }

        return null;
    }

    // Method untuk validasi
    public function isValidTransaction(): bool
    {
        // Validasi basic
        if (!$this->tanggal || !$this->jumlah || $this->jumlah <= 0) {
            return false;
        }

        // Validasi jenis transaksi
        if (!in_array($this->jenis, ['pemasukan', 'pengeluaran'])) {
            return false;
        }

        // Validasi rekening exists
        if (!$this->rekening) {
            return false;
        }

        return true;
    }

    // Method untuk mendapatkan saldo running
    public function getSaldoRunning(): float
    {
        $previousTransactions = self::where('tanggal', '<=', $this->tanggal)
            ->where('id', '<=', $this->id)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $saldo = 0;
        foreach ($previousTransactions as $trans) {
            if ($trans->jenis === 'pemasukan') {
                $saldo += $trans->jumlah;
            } else {
                $saldo -= $trans->jumlah;
            }
        }

        return $saldo;
    }

    // Static method untuk membuat transaksi dari Invoice
    public static function createFromInvoice(Invoice $invoice): self
    {
        // Get default bank account atau dari invoice jika ada
        $account = $invoice->rekeningBank ?? RekeningBank::first();

        if (!$account) {
            throw new \Exception('No bank account available to record transaction');
        }

        $customerName = $invoice->poCustomer?->customer?->nama ?? 'Customer tidak ditemukan';

        return self::create([
            'id_rekening' => $account->id,
            'tanggal' => $invoice->tanggal,
            'jenis' => 'pemasukan',
            'jumlah' => $invoice->grand_total,
            'keterangan' => "Payment for Invoice {$invoice->nomor_invoice} - {$customerName}",
            'referensi_type' => 'invoice',
            'referensi_id' => $invoice->id,
        ]);
    }

    // Static method untuk membuat transaksi dari PO Supplier
    public static function createFromPoSupplier(PoSupplier $poSupplier): self
    {
        $account = RekeningBank::first();

        if (!$account) {
            throw new \Exception('No bank account available to record transaction');
        }

        return self::create([
            'id_po_supplier' => $poSupplier->id,
            'id_rekening' => $account->id,
            'tanggal' => $poSupplier->tanggal_po,
            'jenis' => 'pengeluaran',
            'jumlah' => $poSupplier->total,
            'keterangan' => "Payment for Supplier PO {$poSupplier->nomor_po} - {$poSupplier->supplier->nama}",
            'referensi_type' => 'po_supplier',
            'referensi_id' => $poSupplier->id,
        ]);
    }

    // Boot method untuk validasi dan logging
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaksi) {
            // Validasi sebelum create
            if (!$transaksi->isValidTransaction()) {
                throw new \Exception('Data transaksi tidak valid');
            }
        });

        static::created(function ($transaksi) {
            // Log Activity for created transaction
            ActivityLog::create([
                'log_name' => 'transaksi_keuangan',
                'description' => "Transaksi {$transaksi->jenis} sebesar {$transaksi->formatted_jumlah} dicatat",
                'subject_id' => $transaksi->id,
                'subject_type' => self::class,
                'causer_id' => optional(\Illuminate\Support\Facades\Auth::user())->id,
                'causer_type' => \Illuminate\Support\Facades\Auth::user() ? get_class(\Illuminate\Support\Facades\Auth::user()) : null,
                'properties' => json_encode($transaksi->toArray()),
                'event' => 'created',
            ]);
        });

        static::deleted(function ($transaksi) {
            // Log Activity untuk delete
            ActivityLog::create([
                'log_name' => 'transaksi_keuangan',
                'description' => "Transaksi {$transaksi->jenis} sebesar {$transaksi->formatted_jumlah} dihapus",
                'subject_id' => $transaksi->id,
                'subject_type' => self::class,
                'causer_id' => optional(\Illuminate\Support\Facades\Auth::user())->id,
                'causer_type' => \Illuminate\Support\Facades\Auth::user() ? get_class(\Illuminate\Support\Facades\Auth::user()) : null,
                'properties' => json_encode($transaksi->toArray()),
                'event' => 'deleted',
            ]);
        });
    }
}
