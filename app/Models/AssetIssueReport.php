<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetIssueReport extends Model
{
    public const ACTIVE_STATUSES = ['open', 'under_review', 'converted_to_disposal'];

    protected $fillable = [
        'organization_id',
        'asset_id',
        'asset_assignment_id',
        'reported_by',
        'reviewed_by',
        'asset_disposal_id',
        'issue_type',
        'severity',
        'status',
        'reported_date',
        'reviewed_at',
        'description',
        'review_notes',
    ];

    protected $casts = [
        'reported_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(AssetAssignment::class, 'asset_assignment_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function disposal(): BelongsTo
    {
        return $this->belongsTo(AssetDisposal::class, 'asset_disposal_id');
    }

    public function getIssueTypeLabelAttribute(): string
    {
        return match ($this->issue_type) {
            'not_working' => 'Not Working',
            default => ucwords(str_replace('_', ' ', $this->issue_type)),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'open' => 'warning',
            'under_review' => 'primary',
            'converted_to_disposal' => 'info',
            'resolved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }

    public function getSeverityBadgeAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'primary',
            'low' => 'secondary',
            default => 'secondary',
        };
    }
}
