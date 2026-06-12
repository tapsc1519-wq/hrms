<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoftwareLicense extends Model
{
    protected $fillable = [
        'software_id', 'organization_id', 'vendor_id',
        'license_type', 'license_key', 'seats',
        'purchase_date', 'expiry_date', 'purchase_price',
        'po_number', 'notes', 'status',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'expiry_date'   => 'date',
    ];

    // ── Relationships ───────────────────────────────────────────────────────

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'vendor_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SoftwareAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->hasMany(SoftwareAssignment::class)->where('status', 'active');
    }

    // ── Accessors ───────────────────────────────────────────────────────────

    public function getLicenseTypeLabelAttribute(): string
    {
        return match($this->license_type) {
            'perpetual'    => 'Perpetual',
            'subscription' => 'Subscription',
            'concurrent'   => 'Concurrent',
            'per_seat'     => 'Per Seat',
            'per_device'   => 'Per Device',
            'oem'          => 'OEM',
            'volume'       => 'Volume',
            'open_source'  => 'Open Source',
            'freeware'     => 'Freeware',
            default        => ucfirst($this->license_type),
        };
    }

    public function getUsedSeatsAttribute(): int
    {
        return $this->activeAssignments()->count();
    }

    public function getAvailableSeatsAttribute(): int
    {
        return max(0, $this->seats - $this->used_seats);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->expiry_date
            && !$this->is_expired
            && $this->expiry_date->lte(now()->addDays(30));
    }

    public function getIsOverLicensedAttribute(): bool
    {
        return $this->used_seats > $this->seats;
    }

    public function getComplianceStatusAttribute(): string
    {
        if ($this->is_over_licensed)   return 'over';
        if ($this->used_seats === 0)   return 'unused';
        if ($this->available_seats > 0) return 'under';
        return 'full';
    }

    public function getComplianceBadgeAttribute(): string
    {
        return match($this->compliance_status) {
            'over'   => 'danger',
            'unused' => 'secondary',
            'full'   => 'success',
            'under'  => 'info',
            default  => 'secondary',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        if ($this->is_expired) return 'danger';
        if ($this->is_expiring_soon) return 'warning';
        return match($this->status) {
            'active'    => 'success',
            'cancelled' => 'secondary',
            default     => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->is_expired)      return 'Expired';
        if ($this->is_expiring_soon) return 'Expiring Soon';
        return ucfirst($this->status);
    }
}
