<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'alamat',
        'telepon',
        'email',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship with PoCustomer
     */
    public function poCustomers(): HasMany
    {
        return $this->hasMany(PoCustomer::class, 'id_customer');
    }

    /**
     * Relationship with Penawaran
     */
    public function penawaran(): HasMany
    {
        return $this->hasMany(Penawaran::class, 'id_customer');
    }

    /**
     * Scope for active customers (has purchase orders)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('poCustomers');
    }

    /**
     * Scope for inactive customers (no purchase orders)
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->whereDoesntHave('poCustomers');
    }

    /**
     * Scope for customers with offers
     */
    public function scopeWithOffers(Builder $query): Builder
    {
        return $query->whereHas('penawaran');
    }

    /**
     * Scope for new customers within specified days
     */
    public function scopeNewCustomers(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get customer display name (for select options, etc.)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->nama . ' (' . $this->email . ')';
    }

    /**
     * Get customer short address (first 50 characters)
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
     * Check if customer is active (has purchase orders)
     */
    public function isActive(): bool
    {
        return $this->poCustomers()->exists();
    }

    /**
     * Check if customer has offers
     */
    public function hasOffers(): bool
    {
        return $this->penawaran()->exists();
    }

    /**
     * Get total purchase orders count
     */
    public function getTotalPoCount(): int
    {
        return $this->poCustomers()->count();
    }

    /**
     * Get total offers count
     */
    public function getTotalOffersCount(): int
    {
        return $this->penawaran()->count();
    }

    /**
     * Get latest purchase order
     */
    public function getLatestPurchaseOrder()
    {
        return $this->poCustomers()->latest()->first();
    }

    /**
     * Get latest offer
     */
    public function getLatestOffer()
    {
        return $this->penawaran()->latest()->first();
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
                ->orWhere('alamat', 'like', '%' . $search . '%');
        });
    }

    /**
     * Get customer activity status badge color
     */
    public function getActivityStatusColor(): string
    {
        if ($this->isActive()) {
            return 'success';
        }

        if ($this->hasOffers()) {
            return 'warning';
        }

        return 'gray';
    }

    /**
     * Get customer activity status text
     */
    public function getActivityStatusText(): string
    {
        if ($this->isActive()) {
            return 'Aktif';
        }

        if ($this->hasOffers()) {
            return 'Prospek';
        }

        return 'Tidak Aktif';
    }

    /**
     * Get customer registration period (in days)
     */
    public function getRegistrationDaysAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Check if customer is new (registered within 30 days)
     */
    public function isNew(int $days = 30): bool
    {
        return $this->created_at->isAfter(now()->subDays($days));
    }

    /**
     * Get customer's last activity date
     */
    public function getLastActivityDate()
    {
        $latestPo = $this->poCustomers()->latest()->first();
        $latestOffer = $this->penawaran()->latest()->first();

        if (!$latestPo && !$latestOffer) {
            return $this->created_at;
        }

        if (!$latestPo) {
            return $latestOffer->created_at;
        }

        if (!$latestOffer) {
            return $latestPo->created_at;
        }

        return $latestPo->created_at->isAfter($latestOffer->created_at)
            ? $latestPo->created_at
            : $latestOffer->created_at;
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            // Additional logic when creating customer
            $customer->email = strtolower($customer->email);
        });

        static::updating(function ($customer) {
            // Additional logic when updating customer
            $customer->email = strtolower($customer->email);
        });
    }
}