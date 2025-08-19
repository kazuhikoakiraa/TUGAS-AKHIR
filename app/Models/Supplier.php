<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'alamat',
        'telepon',
        'email',
        'npwp',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship with PoSupplier
     */
    public function poSuppliers(): HasMany
    {
        return $this->hasMany(PoSupplier::class, 'id_supplier');
    }

    /**
     * Get supplier display name (for select options, etc.)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->nama . ' (' . $this->email . ')';
    }

    /**
     * Get supplier short address (first 50 characters)
     */
    public function getShortAddressAttribute(): string
    {
        return strlen($this->alamat) > 50
            ? substr($this->alamat, 0, 50) . '...'
            : $this->alamat;
    }

    /**
     * Get formatted phone number
     */
    public function getFormattedPhoneAttribute(): string
    {
        $phone = preg_replace('/[^0-9]/', '', $this->telepon);

        if (substr($phone, 0, 1) === '0') {
            return '+62' . substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Get formatted NPWP
     */
    public function getFormattedNpwpAttribute(): string
    {
        if (!$this->npwp) {
            return '-';
        }

        $npwp = preg_replace('/[^0-9]/', '', $this->npwp);

        if (strlen($npwp) === 15) {
            return substr($npwp, 0, 2) . '.' .
                   substr($npwp, 2, 3) . '.' .
                   substr($npwp, 5, 3) . '.' .
                   substr($npwp, 8, 1) . '-' .
                   substr($npwp, 9, 3) . '.' .
                   substr($npwp, 12, 3);
        }

        return $npwp;
    }

    /**
     * Check if supplier is active (has purchase orders)
     */
    public function isActive(): bool
    {
        return $this->poSuppliers()->exists();
    }

    /**
     * Get total purchase orders count
     */
    public function getTotalPoCount(): int
    {
        return $this->poSuppliers()->count();
    }

    /**
     * Get latest purchase order
     */
    public function getLatestPurchaseOrder()
    {
        return $this->poSuppliers()->latest()->first();
    }

    /**
     * Search scope for multiple fields
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search) {
            $query->where('nama', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->orWhere('telepon', 'like', '%' . $search . '%')
                ->orWhere('alamat', 'like', '%' . $search . '%')
                ->orWhere('npwp', 'like', '%' . $search . '%');
        });
    }

    /**
     * Scope for active suppliers (has purchase orders)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('poSuppliers');
    }

    /**
     * Scope for inactive suppliers (no purchase orders)
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->whereDoesntHave('poSuppliers');
    }

    /**
     * Scope for new suppliers within specified days
     */
    public function scopeNewSuppliers(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get supplier activity status badge color
     */
    public function getActivityStatusColor(): string
    {
        if ($this->isActive()) {
            return 'success';
        }

        return 'gray';
    }

    /**
     * Get supplier activity status text
     */
    public function getActivityStatusText(): string
    {
        if ($this->isActive()) {
            return 'Aktif';
        }

        return 'Tidak Aktif';
    }

    /**
     * Get supplier registration period (in days)
     */
    public function getRegistrationDaysAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Check if supplier is new (registered within 30 days)
     */
    public function isNew(int $days = 30): bool
    {
        return $this->created_at->isAfter(now()->subDays($days));
    }

    /**
     * Get supplier's last activity date
     */
    public function getLastActivityDate()
    {
        $latestPo = $this->poSuppliers()->latest()->first();

        if (!$latestPo) {
            return $this->created_at;
        }

        return $latestPo->created_at;
    }

    /**
     * Validate NPWP format
     */
    public static function validateNpwp(string $npwp): bool
    {
        // Remove all non-numeric characters
        $cleanNpwp = preg_replace('/[^0-9]/', '', $npwp);

        // NPWP must be exactly 15 digits
        if (strlen($cleanNpwp) !== 15) {
            return false;
        }

        // Basic validation: check if it's not all zeros or same digit
        if (preg_match('/^0{15}$/', $cleanNpwp) || preg_match('/^(\d)\1{14}$/', $cleanNpwp)) {
            return false;
        }

        return true;
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($supplier) {
            // Additional logic when creating supplier
            $supplier->email = strtolower($supplier->email);

            // Clean NPWP format
            if ($supplier->npwp) {
                $supplier->npwp = preg_replace('/[^0-9]/', '', $supplier->npwp);
            }
        });

        static::updating(function ($supplier) {
            // Additional logic when updating supplier
            $supplier->email = strtolower($supplier->email);

            // Clean NPWP format
            if ($supplier->npwp) {
                $supplier->npwp = preg_replace('/[^0-9]/', '', $supplier->npwp);
            }
        });
    }
}
