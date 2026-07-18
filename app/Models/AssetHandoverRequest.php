<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetHandoverRequest extends Model
{
    protected $fillable = [
        'asset_assignment_id', 'asset_id', 'from_user_id', 'to_user_id',
        'handover_date', 'condition_in', 'notes', 'response_notes',
        'approved_by', 'approved_at', 'approval_notes', 'status', 'responded_at',
    ];

    protected $casts = [
        'handover_date' => 'date',
        'approved_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(AssetAssignment::class, 'asset_assignment_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending', 'pending_admin' => 'Pending IT Approval',
            'approved' => 'Waiting for Recipient',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected by Recipient',
            'admin_rejected' => 'Rejected by IT',
            'cancelled' => 'Cancelled',
            default => ucwords(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending', 'pending_admin' => 'warning text-dark',
            'approved' => 'primary',
            'accepted' => 'success',
            'rejected', 'admin_rejected' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }
}
