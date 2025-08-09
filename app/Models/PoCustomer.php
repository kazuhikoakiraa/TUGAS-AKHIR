<?php

namespace App\Models;

use App\Enums\PoStatus; // Import enum
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PoCustomer extends Model
{
    use HasFactory;

    protected $table = 'po_customer';

    protected $fillable = [
        'id_customer',
        'id_user',
        'nomor_po',
        'tanggal_po',
        'jenis_po',
        'status_po',
        'total_sebelum_pajak',
        'total_pajak',
    ];

    protected $casts = [
        'tanggal_po' => 'date',
        'total_sebelum_pajak' => 'decimal:2',
        'total_pajak' => 'decimal:2',
        'status_po' => PoStatus::class, // TAMBAHKAN INI - Cast enum
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PoCustomerDetail::class, 'id_po_customer');
    }

    public function suratJalan(): HasOne
    {
        return $this->hasOne(SuratJalan::class, 'id_po_customer');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'id_po_customer');
    }

    public function getTotalAttribute()
    {
        return $this->total_sebelum_pajak + $this->total_pajak;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($po) {
            if (empty($po->nomor_po)) {
                $po->nomor_po = self::generateNomorPo();
            }
        });

        // PERBAIKAN: Lebih aman untuk menghindari infinite loop
        static::saved(function ($po) {
            // Hanya update jika bukan dari proses update totals itu sendiri
            if (!$po->isDirty(['total_sebelum_pajak', 'total_pajak']) && !$po->updating_totals) {
                $po->updateTotalsWithoutEvents();
            }
        });
    }

     // Accessor untuk total keseluruhan
    protected function total(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total_sebelum_pajak + $this->total_pajak,
        );
    }

    // Event untuk recalculate total saat model di-save
    protected static function booted()
    {
        static::saving(function ($poSupplier) {
            // Hitung ulang total dari details jika ada
            if ($poSupplier->exists) {
                $totalSebelumPajak = $poSupplier->details()->sum(DB::raw('jumlah * harga_satuan'));
                $poSupplier->total_sebelum_pajak = $totalSebelumPajak;
                $poSupplier->total_pajak = $totalSebelumPajak * 0.11;
            }
        });
    }


    public static function generateNomorPo(): string
    {
        $today = now()->format('Ymd');
        $count = static::whereDate('created_at', today())->count() + 1;

        return 'POC-' . $today . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    // PERBAIKAN: Method untuk update totals tanpa trigger events
    public function updateTotalsWithoutEvents(): void
    {
        $totalSebelumPajak = $this->details()->sum('total');
        $totalPajak = $totalSebelumPajak * 0.11;

        // Set flag untuk mencegah loop
        $this->updating_totals = true;

        // Update menggunakan query builder untuk avoid events
        static::where('id', $this->id)->update([
            'total_sebelum_pajak' => $totalSebelumPajak,
            'total_pajak' => $totalPajak,
            'updated_at' => now(),
        ]);

        // Refresh model attributes
        $this->total_sebelum_pajak = $totalSebelumPajak;
        $this->total_pajak = $totalPajak;

        unset($this->updating_totals);
    }

    public function updateTotals(): void
    {
        $this->updateTotalsWithoutEvents();
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status_po', $status);
    }

    public function scopeByJenis($query, $jenis)
    {
        return $query->where('jenis_po', $jenis);
    }

    // PERBAIKAN: Gunakan enum untuk comparison
    public function canBeEdited(): bool
    {
        return in_array($this->status_po, [PoStatus::DRAFT, PoStatus::PENDING]);
    }

    public function canBeDeleted(): bool
    {
        return $this->status_po === PoStatus::DRAFT;
    }

    // TAMBAHAN: Helper methods untuk status
    public function isDraft(): bool
    {
        return $this->status_po === PoStatus::DRAFT;
    }

    public function isPending(): bool
    {
        return $this->status_po === PoStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status_po === PoStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status_po === PoStatus::REJECTED;
    }


/**
 * Check if this PO has surat jalan.
 */
public function hasSuratJalan(): bool
{
    return $this->suratJalan()->exists();
}
}
