<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SoftwareLicense extends Model
{
    protected $fillable = [
        'software_id', 'organization_id', 'vendor_id',
        'license_type', 'license_key', 'purchase_batch', 'seats',
        'purchase_date', 'expiry_date', 'renewal_date', 'purchase_price',
        'unit_cost', 'po_number', 'invoice_number', 'agreement_number',
        'subscription_period', 'evidence_document', 'notes', 'status',
        'purchase_order_id', 'purchase_order_item_id', 'goods_receipt_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'expiry_date'   => 'date',
        'renewal_date'  => 'date',
        'purchase_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
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

    public function vendor(): BelongsTo
    {
        return $this->supplier();
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SoftwareAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->hasMany(SoftwareAssignment::class)->where('status', 'active');
    }

    public function renewalDecisions(): HasMany
    {
        return $this->hasMany(SoftwareRenewalDecision::class);
    }

    public function activeRenewalDecision(): HasOne
    {
        return $this->hasOne(SoftwareRenewalDecision::class)->where('status', 'planned')->latest('id');
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

    public function getTotalCostAttribute(): float
    {
        if ($this->purchase_price) {
            return (float) $this->purchase_price;
        }

        return (float) ($this->unit_cost ?? 0) * (int) $this->seats;
    }

    public function getUtilizationPercentageAttribute(): int
    {
        if ((int) $this->seats === 0) {
            return 0;
        }

        return min(100, (int) round(($this->used_seats / $this->seats) * 100));
    }

    public function getRenewalRecommendationAttribute(): string
    {
        if ($this->software?->criticality === 'critical' && $this->utilization_percentage < 80) {
            return 'manual_review';
        }

        return match (true) {
            $this->utilization_percentage >= 80 => 'renew',
            $this->utilization_percentage >= 30 => 'reduce',
            default => 'cancel_review',
        };
    }

    public function getRenewalRecommendationLabelAttribute(): string
    {
        return match($this->renewal_recommendation) {
            'renew' => 'Renew',
            'reduce' => 'Reduce',
            'cancel_review' => 'Cancel Review',
            default => 'Manual Review',
        };
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
