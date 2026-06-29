<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwarePolicyException extends Model
{
    protected $fillable = [
        'organization_id', 'software_id', 'software_discovery_id', 'user_id', 'asset_id',
        'status', 'valid_from', 'expires_at', 'reason', 'conditions',
        'approved_by', 'revoked_by', 'revoked_at',
    ];

    protected $casts = [
        'valid_from' => 'date', 'expires_at' => 'date', 'revoked_at' => 'datetime',
    ];

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function software(): BelongsTo { return $this->belongsTo(Software::class); }
    public function discovery(): BelongsTo { return $this->belongsTo(SoftwareDiscovery::class, 'software_discovery_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function revokedBy(): BelongsTo { return $this->belongsTo(User::class, 'revoked_by'); }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'approved')
            ->whereDate('valid_from', '<=', today())
            ->whereDate('expires_at', '>=', today());
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'approved'
            && $this->valid_from->lte(today())
            && $this->expires_at->gte(today());
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'revoked') return 'Revoked';
        if ($this->expires_at->lt(today())) return 'Expired';
        if ($this->valid_from->gt(today())) return 'Scheduled';
        return 'Active';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status_label) {
            'Active' => 'success', 'Scheduled' => 'info', 'Expired' => 'secondary', default => 'danger',
        };
    }

    public function getDaysToExpiryAttribute(): int
    {
        return (int) today()->diffInDays($this->expires_at, false);
    }

    public function getExpiryLabelAttribute(): string
    {
        if ($this->status === 'revoked') return 'Revoked';
        if ($this->days_to_expiry < 0) return 'Expired';
        if ($this->days_to_expiry === 0) return 'Expires Today';
        if ($this->days_to_expiry <= 14) return 'Expiring Soon';
        return 'Current';
    }

    public function getExpiryBadgeAttribute(): string
    {
        return match ($this->expiry_label) {
            'Expired', 'Revoked' => 'danger',
            'Expires Today', 'Expiring Soon' => 'warning',
            default => 'success',
        };
    }
}
