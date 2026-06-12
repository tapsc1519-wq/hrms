<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetRequest extends Model
{
    protected $fillable = [
        'organization_id', 'requester_id', 'asset_id', 'category_id',
        'request_type', 'quantity', 'reviewed_by', 'fulfilled_asset_id', 'request_number',
        'request_date', 'required_date', 'reason', 'priority',
        'status', 'reviewed_at', 'review_notes', 'fulfilled_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'required_date' => 'date',
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

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function fulfilledAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'fulfilled_asset_id');
    }

    public function getPriorityBadgeAttribute(): string
    {
        return match($this->priority) {
            'low'    => 'secondary',
            'normal' => 'info',
            'high'   => 'warning',
            'urgent' => 'danger',
            default  => 'secondary',
        };
    }

    public function getRequestTypeLabelAttribute(): string
    {
        return match($this->request_type) {
            'new_asset'   => 'New Asset',
            'replacement' => 'Replacement',
            'additional'  => 'Additional Asset',
            'temporary'   => 'Temporary Use',
            default       => ucwords(str_replace('_', ' ', (string) $this->request_type)),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending'   => 'warning',
            'approved'  => 'success',
            'rejected'  => 'danger',
            'fulfilled' => 'primary',
            'cancelled' => 'secondary',
            default     => 'secondary',
        };
    }
}
