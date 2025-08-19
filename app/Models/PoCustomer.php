<?php

namespace App\Models;

use App\Enums\PoStatus;
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
        'tax_rate',
        'attachment_path', // TAMBAH - Path file attachment
        'attachment_name', // TAMBAH - Original filename
        'keterangan', // TAMBAH - Notes/remarks
    ];

    protected $casts = [
        'tanggal_po' => 'date',
        'total_sebelum_pajak' => 'decimal:2',
        'total_pajak' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'status_po' => PoStatus::class,
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

    // Accessor untuk total keseluruhan
    public function getTotalAttribute()
    {
        return $this->total_sebelum_pajak + $this->total_pajak;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($po) {
            // Set default tax rate jika tidak ada
            if (empty($po->tax_rate)) {
                $po->tax_rate = 11.00; // Default 11% PPN
            }
        });

        static::saved(function ($po) {
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

    // Method untuk update totals dengan dynamic tax rate
    public function updateTotalsWithoutEvents(): void
    {
        $totalSebelumPajak = $this->details()->sum('total');
        $taxRate = $this->tax_rate / 100; // Convert persen ke desimal
        $totalPajak = $totalSebelumPajak * $taxRate;

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

    // Helper method untuk mendapatkan tax rate dalam format display
    public function getFormattedTaxRateAttribute(): string
    {
        return $this->tax_rate . '%';
    }

    // Helper methods untuk attachment
    public function hasAttachment(): bool
    {
        return !empty($this->attachment_path);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if ($this->hasAttachment()) {
            return asset('storage/' . $this->attachment_path);
        }
        return null;
    }

    // Scope methods
    public function scopeByStatus($query, $status)
    {
        return $query->where('status_po', $status);
    }

    public function scopeByJenis($query, $jenis)
    {
        return $query->where('jenis_po', $jenis);
    }

    // Status check methods
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

    // Check methods untuk related documents
    public function hasSuratJalan(): bool
    {
        return $this->suratJalan()->exists();
    }

    public function hasInvoice(): bool
    {
        return $this->invoice()->exists();
    }

    public function canGenerateInvoice(): bool
    {
        return $this->status_po === PoStatus::APPROVED && !$this->hasInvoice();
    }

    // Helper method untuk check jenis PO
    public function isProductPo(): bool
    {
        return $this->jenis_po === 'Product';
    }

    public function isServicePo(): bool
    {
        return $this->jenis_po === 'Service';
    }
}
