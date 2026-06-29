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

    public function getIsOpenAttribute(): bool
    {
        return in_array($this->status, ['pending', 'approved'], true);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->is_open && $this->needed_by && $this->needed_by->isPast();
    }

    public function getIsAgingAttribute(): bool
    {
        return $this->is_open && $this->created_at->lt(now()->subDays(7));
    }

    public function getDaysOpenAttribute(): int
    {
        return $this->created_at ? (int) $this->created_at->diffInDays(now()) : 0;
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->is_open || ! $this->needed_by || ! $this->needed_by->lt(today())) {
            return 0;
        }

        return (int) $this->needed_by->diffInDays(today());
    }

    public function getSlaLabelAttribute(): string
    {
        if (! $this->is_open) return 'Closed';
        if ($this->is_overdue) return 'Overdue';
        if ($this->needed_by && $this->needed_by->lte(today()->addDays(7))) return 'Due Soon';
        if ($this->is_aging) return 'Aging';
        return 'On Track';
    }

    public function getSlaBadgeAttribute(): string
    {
        return match ($this->sla_label) {
            'Overdue' => 'danger',
            'Due Soon', 'Aging' => 'warning',
            'Closed' => 'secondary',
            default => 'success',
        };
    }

    public function getSlaIssueAttribute(): string
    {
        if (! $this->is_open) return 'Closed request';

        $issues = collect([
            $this->is_overdue ? 'Needed-by date missed' : null,
            $this->needed_by && $this->needed_by->gte(today()) && $this->needed_by->lte(today()->addDays(7)) ? 'Needed within 7 days' : null,
            $this->is_aging ? 'Open for more than 7 days' : null,
            $this->purchase_order_item_id && $this->status === 'approved' ? 'Awaiting allocation after procurement link' : null,
        ])->filter();

        return $issues->isNotEmpty() ? $issues->implode('; ') : 'On track';
    }
}
