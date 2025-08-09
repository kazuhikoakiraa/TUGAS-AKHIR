<?php

namespace App\Models;

use App\Enums\PoStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PoSupplier extends Model
{
    use HasFactory;

    // FIXED: Konsisten lowercase
    protected $table = 'po_supplier';

    protected $fillable = [
        'id_supplier',
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
        'status_po' => PoStatus::class,
    ];

    // FIXED: Method name lowercase
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // FIXED: Foreign key konsisten
    public function details(): HasMany
    {
        return $this->hasMany(PoSupplierDetail::class, 'id_po_supplier');
    }

    public function suratJalan(): HasOne
    {
        return $this->hasOne(SuratJalan::class, 'id_po_supplier');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'id_po_supplier');
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

        static::saved(function ($po) {
            if (!$po->isDirty(['total_sebelum_pajak', 'total_pajak']) && !$po->updating_totals) {
                $po->updateTotalsWithoutEvents();
            }
        });
    }

    public static function generateNomorPo(): string
    {
        $today = now()->format('Ymd');
        $count = static::whereDate('created_at', today())->count() + 1;

        return 'POS-' . $today . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    public function updateTotalsWithoutEvents(): void
    {
        $totalSebelumPajak = $this->details()->sum('total');
        $totalPajak = $totalSebelumPajak * 0.11;

        $this->updating_totals = true;

        static::where('id', $this->id)->update([
            'total_sebelum_pajak' => $totalSebelumPajak,
            'total_pajak' => $totalPajak,
            'updated_at' => now(),
        ]);

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

    public function canBeEdited(): bool
    {
        return in_array($this->status_po, [PoStatus::DRAFT, PoStatus::PENDING]);
    }

    public function canBeDeleted(): bool
    {
        return $this->status_po === PoStatus::DRAFT;
    }

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
}
