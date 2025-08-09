<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'event',
        'description',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    // Relations
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    // Helper methods
    public function getCauserNameAttribute(): string
    {
        if ($this->causer) {
            return $this->causer->name ?? $this->causer->email ?? 'Unknown User';
        }

        return 'System';
    }

    public function getEventColorAttribute(): string
    {
        return match ($this->event) {
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            'password_reset' => 'info',
            'email_verified' => 'success',
            default => 'gray',
        };
    }

    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
           'created' => 'Created',
'updated' => 'Updated',
'deleted' => 'Deleted',
'password_reset' => 'Password Reset',
'email_verified' => 'Email Verified',
            default => ucfirst($this->event),
        };
    }
}
