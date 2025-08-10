<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoice'; // Sesuaikan dengan nama table di migration Anda

    protected $fillable = [
        'id_po_customer',
        'id_user',
        'id_rekening_bank',
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

    // Relationships
    public function poCustomer(): BelongsTo
    {
        return $this->belongsTo(PoCustomer::class, 'id_po_customer');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function rekeningBank(): BelongsTo
    {
        return $this->belongsTo(RekeningBank::class, 'id_rekening_bank');
    }

    // Boot method for auto-generating invoice number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->nomor_invoice)) {
                $invoice->nomor_invoice = self::generateNomorInvoice();
            }

            // Set default tanggal if not provided
            if (empty($invoice->tanggal)) {
                $invoice->tanggal = now();
            }
        });
    }

    // Generate unique invoice number
    public static function generateNomorInvoice(): string
    {
        $today = now()->format('Ymd');
        $count = static::whereDate('created_at', today())->count() + 1;

        return 'INV-' . $today . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('created_at', now()->year);
    }

    // Helper methods
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue';
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft']);
    }

    public function canBeDeleted(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeSent(): bool
    {
        return $this->status === 'draft';
    }

    public function canBePaid(): bool
    {
        return in_array($this->status, ['sent', 'overdue']);
    }

    // Formatted attributes
    public function getFormattedTotalSebelumPajakAttribute(): string
    {
        return 'Rp ' . number_format($this->total_sebelum_pajak, 0, ',', '.');
    }

    public function getFormattedTotalPajakAttribute(): string
    {
        return 'Rp ' . number_format($this->total_pajak, 0, ',', '.');
    }

    public function getFormattedGrandTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->grand_total, 0, ',', '.');
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'secondary',
            'sent' => 'warning',
            'paid' => 'success',
            'overdue' => 'danger',
            default => 'secondary'
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'sent' => 'Terkirim',
            'paid' => 'Dibayar',
            'overdue' => 'Terlambat',
            default => 'Unknown'
        };
    }

    // Calculate totals from PO Customer
    public function calculateTotalsFromPO(): void
    {
        if ($this->poCustomer) {
            $this->total_sebelum_pajak = $this->poCustomer->total_sebelum_pajak;
            $this->total_pajak = $this->poCustomer->total_pajak;
            $this->grand_total = $this->total_sebelum_pajak + $this->total_pajak;
        }
    }

    // Update status methods
    public function markAsSent(): bool
    {
        if ($this->canBeSent()) {
            return $this->update(['status' => 'sent']);
        }
        return false;
    }

    public function markAsPaid(): bool
    {
        if ($this->canBePaid()) {
            return $this->update(['status' => 'paid']);
        }
        return false;
    }

    public function markAsOverdue(): bool
    {
        if ($this->status === 'sent') {
            return $this->update(['status' => 'overdue']);
        }
        return false;
    }
}
