<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareComplianceAction extends Model
{
    protected $fillable = [
        'organization_id',
        'software_id',
        'software_discovery_id',
        'user_id',
        'asset_id',
        'action_type',
        'status',
        'quantity',
        'due_date',
        'owner_id',
        'created_by',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function discovery(): BelongsTo
    {
        return $this->belongsTo(SoftwareDiscovery::class, 'software_discovery_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActionTypeLabelAttribute(): string
    {
        return match ($this->action_type) {
            'allocate_license' => 'Allocate License',
            'purchase_license' => 'Purchase License',
            'approve_exception' => 'Approve Exception',
            'uninstall_reclaim' => 'Uninstall / Reclaim',
            default => ucfirst(str_replace('_', ' ', $this->action_type)),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'cancelled' => 'secondary',
            default => 'warning',
        };
    }
}
