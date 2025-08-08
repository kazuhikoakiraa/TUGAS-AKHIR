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
use Filament\Tables\Columns\Layout\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
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
        'email_verified_at', // Tambahkan ini untuk manual verification
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
            'subject_id' => $model->id, // Tambahkan subject_id
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
    // public function penawaran(): HasMany
    // {
    //     return $this->hasMany(Penawaran::class, 'id_user');
    // }

    // public function poCustomers(): HasMany
    // {
    //     return $this->hasMany(PoCustomer::class, 'id_user');
    // }

    // public function poSuppliers(): HasMany
    // {
    //     return $this->hasMany(PoSupplier::class, 'id_user');
    // }

    // public function suratJalan(): HasMany
    // {
    //     return $this->hasMany(SuratJalan::class, 'id_user');
    // }

    // public function invoices(): HasMany
    // {
    //     return $this->hasMany(Invoice::class, 'id_user');
    // }

    // Activity log relation - Perbaiki relation
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

    // Method untuk mengirim email verification
    public function sendEmailVerificationNotification()
    {
        try {
            $this->notify(new \App\Notifications\CustomVerifyEmail);

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

    public function canAccessPanel(Panel $panel): bool
{
    return $this->hasAnyRole(['super_admin','user', 'manager', 'staff']);
}

    // Method untuk generate password reset token - Perbaiki dengan logging
    public function sendPasswordResetNotification($token)
    {
        try {
            $this->notify(new \App\Notifications\CustomResetPassword($token));

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

            throw $e; // Re-throw untuk error handling di controller
        }
    }
}
