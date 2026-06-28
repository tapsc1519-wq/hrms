<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareUsageReview extends Model
{
    protected $fillable = [
        'organization_id', 'software_assignment_id', 'software_discovery_id',
        'status', 'inactivity_days', 'last_used_date', 'estimated_annual_savings',
        'due_date', 'owner_id', 'created_by', 'decided_by', 'decided_at',
        'notes', 'decision_notes',
    ];

    protected $casts = [
        'last_used_date' => 'date',
        'due_date' => 'date',
        'decided_at' => 'datetime',
        'estimated_annual_savings' => 'decimal:2',
    ];

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function assignment(): BelongsTo { return $this->belongsTo(SoftwareAssignment::class, 'software_assignment_id'); }
    public function discovery(): BelongsTo { return $this->belongsTo(SoftwareDiscovery::class, 'software_discovery_id'); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function decidedBy(): BelongsTo { return $this->belongsTo(User::class, 'decided_by'); }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending_user' => 'Waiting for Employee',
            'retained' => 'Employee Still Needs It',
            'reclaimed' => 'License Reclaimed',
            'cancelled' => 'Review Cancelled',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'retained' => 'info',
            'reclaimed' => 'success',
            'cancelled' => 'secondary',
            default => 'warning',
        };
    }
}
