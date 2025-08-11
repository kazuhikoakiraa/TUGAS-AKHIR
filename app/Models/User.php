<?php

namespace App\Models;

use App\Models\Invoice;
use App\Models\Penawaran;
use App\Models\PoCustomer;
use App\Models\PoSupplier;
use App\Models\SuratJalan;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\Notifiable;
use Filament\Panel;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use App\Notifications\CustomVerifyEmail;
use App\Notifications\CustomResetPassword;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Boot method untuk audit trail
    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            self::logActivity($model, 'created', 'User account created');
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            unset($changes['updated_at']); // Remove updated_at from changes

            if (!empty($changes)) {
                $description = 'User account updated: ' . implode(', ', array_keys($changes));
                self::logActivity($model, 'updated', $description, $model->getOriginal(), $changes);
            }
        });

        static::deleted(function ($model) {
            self::logActivity($model, 'deleted', 'User account deleted');
        });
    }

    // Method untuk logging activity
    private static function logActivity($model, $event, $description, $oldData = null, $newData = null)
    {
        ActivityLog::create([
            'subject_type' => get_class($model),
            'subject_id' => $model->id,
            'causer_type' => Auth::check() ? get_class(Auth::user()) : null,
            'causer_id' => Auth::id(),
            'event' => $event,
            'description' => $description,
            'properties' => json_encode([
                'old' => $oldData,
                'attributes' => $newData ?? $model->toArray(),
            ]),
        ]);
    }

    // Relations
    public function penawaran(): HasMany
    {
        return $this->hasMany(Penawaran::class, 'id_user');
    }

    public function poCustomers(): HasMany
    {
        return $this->hasMany(PoCustomer::class, 'id_user');
    }

    public function poSuppliers(): HasMany
    {
        return $this->hasMany(PoSupplier::class, 'id_user');
    }

    public function suratJalan(): HasMany
    {
        return $this->hasMany(SuratJalan::class, 'id_user');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'id_user');
    }

    // Activity log relations
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'subject_id')
                    ->where('subject_type', self::class)
                    ->orderBy('created_at', 'desc');
    }

    public function causedActivities(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'causer_id')
                    ->where('causer_type', self::class)
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Check if user can access Filament panel
     * Only verified users can access
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Allow access only if email is verified
        if (!$this->hasVerifiedEmail()) {
            return false;
        }

        return $this->hasAnyRole(['super_admin', 'user', 'manager', 'staff']);
    }

    /**
     * Method untuk mengirim email verification
     */
    public function sendEmailVerificationNotification()
    {
        try {
            $this->notify(new CustomVerifyEmail);

            // Log activity
            ActivityLog::create([
                'subject_type' => get_class($this),
                'subject_id' => $this->id,
                'causer_type' => Auth::check() ? get_class(Auth::user()) : null,
                'causer_id' => Auth::id(),
                'event' => 'verification_email_sent',
                'description' => 'Email verification sent to user',
                'properties' => json_encode([
                    'email' => $this->email,
                    'sent_by' => Auth::user()?->name ?? 'System',
                    'sent_at' => now(),
                ]),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send verification email: ' . $e->getMessage(), [
                'user_id' => $this->id,
                'email' => $this->email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Method untuk generate password reset token dengan logging
     * DIPERBAIKI: Menggunakan CustomResetPasswordNotification
     */
    public function sendPasswordResetNotification($token)
    {
        try {
            // Gunakan custom notification dengan template email yang sudah dibuat
            $this->notify(new CustomResetPassword($token));

            // Log activity untuk password reset
            ActivityLog::create([
                'subject_type' => get_class($this),
                'subject_id' => $this->id,
                'causer_type' => Auth::check() ? get_class(Auth::user()) : null,
                'causer_id' => Auth::id(),
                'event' => 'password_reset_sent',
                'description' => 'Password reset email sent to user',
                'properties' => json_encode([
                    'email' => $this->email,
                    'sent_by' => Auth::user()?->name ?? 'System',
                    'sent_at' => now(),
                    'token_expires_at' => now()->addMinutes(config('auth.passwords.users.expire', 60)),
                ]),
            ]);

            Log::info('Password reset notification sent', [
                'user_id' => $this->id,
                'email' => $this->email,
                'sent_at' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send password reset notification: ' . $e->getMessage(), [
                'user_id' => $this->id,
                'email' => $this->email,
                'error' => $e->getMessage()
            ]);

            // Log failed attempt
            ActivityLog::create([
                'subject_type' => get_class($this),
                'subject_id' => $this->id,
                'causer_type' => Auth::check() ? get_class(Auth::user()) : null,
                'causer_id' => Auth::id(),
                'event' => 'password_reset_failed',
                'description' => 'Failed to send password reset email',
                'properties' => json_encode([
                    'email' => $this->email,
                    'error' => $e->getMessage(),
                    'attempted_at' => now(),
                ]),
            ]);

            throw $e;
        }
    }

    /**
     * Manual email verification by admin
     */
    public function markEmailAsVerified()
    {
        if ($this->hasVerifiedEmail()) {
            return false;
        }

        $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();

        // Log activity
        ActivityLog::create([
            'subject_type' => get_class($this),
            'subject_id' => $this->id,
            'causer_type' => Auth::check() ? get_class(Auth::user()) : null,
            'causer_id' => Auth::id(),
            'event' => 'email_verified_manually',
            'description' => 'Email verified manually by admin',
            'properties' => json_encode([
                'email' => $this->email,
                'verified_by' => Auth::user()?->name ?? 'System',
                'verified_at' => now(),
            ]),
        ]);

        return true;
    }

    /**
     * Get user theme preference
     */
    public function getThemeAttribute($value)
    {
        return $value ?? 'default';
    }

    /**
     * Scope untuk filter verified users
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope untuk filter unverified users
     */
    public function scopeUnverified($query)
    {
        return $query->whereNull('email_verified_at');
    }
}
