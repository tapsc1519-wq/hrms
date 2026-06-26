<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AssetDisposal extends Model
{
    protected $fillable = [
        'organization_id',
        'asset_id',
        'requested_by',
        'approved_by',
        'completed_by',
        'method',
        'status',
        'requested_date',
        'approved_date',
        'disposed_date',
        'expected_value',
        'recovered_value',
        'disposal_cost',
        'recipient_name',
        'certificate_number',
        'reason',
        'approval_notes',
        'completion_notes',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'approved_date' => 'date',
        'disposed_date' => 'date',
        'expected_value' => 'decimal:2',
        'recovered_value' => 'decimal:2',
        'disposal_cost' => 'decimal:2',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function issueReport(): HasOne
    {
        return $this->hasOne(AssetIssueReport::class);
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            'return_to_supplier' => 'Return to Supplier',
            default => ucwords(str_replace('_', ' ', $this->method)),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'primary',
            'completed' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }

    public function getNetRecoveryAttribute(): float
    {
        return (float) ($this->recovered_value ?? 0) - (float) ($this->disposal_cost ?? 0);
    }
}
