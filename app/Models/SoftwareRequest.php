<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareRequest extends Model
{
    protected $fillable = [
        'organization_id', 'requester_id', 'software_id', 'status', 'urgency',
        'needed_by', 'business_justification', 'reviewed_by', 'reviewed_at',
        'review_notes', 'software_license_id', 'software_assignment_id',
        'purchase_order_item_id', 'fulfilled_by', 'fulfilled_at',
    ];

    protected $casts = [
        'needed_by' => 'date',
        'reviewed_at' => 'datetime',
        'fulfilled_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(SoftwareLicense::class, 'software_license_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(SoftwareAssignment::class, 'software_assignment_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function fulfiller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'Approved - Awaiting Allocation',
            'fulfilled' => 'Software Allocated',
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'info',
            'fulfilled' => 'success',
            'rejected', 'cancelled' => 'secondary',
            default => 'warning',
        };
    }

    public function getUrgencyBadgeAttribute(): string
    {
        return match ($this->urgency) {
            'critical' => 'danger',
            'high' => 'warning',
            'low' => 'secondary',
            default => 'primary',
        };
    }
}
