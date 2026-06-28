<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareRenewalDecision extends Model
{
    protected $fillable = [
        'organization_id', 'software_license_id', 'decision', 'status', 'target_seats',
        'projected_cost', 'due_date', 'owner_id', 'rationale', 'created_by',
        'actual_seats', 'actual_cost', 'new_expiry_date', 'new_renewal_date',
        'completion_notes', 'completed_by', 'completed_at',
    ];

    protected $casts = [
        'projected_cost' => 'decimal:2', 'actual_cost' => 'decimal:2',
        'due_date' => 'date', 'new_expiry_date' => 'date', 'new_renewal_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function license(): BelongsTo { return $this->belongsTo(SoftwareLicense::class, 'software_license_id'); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function completedBy(): BelongsTo { return $this->belongsTo(User::class, 'completed_by'); }

    public function getDecisionLabelAttribute(): string
    {
        return match ($this->decision) {
            'renew' => 'Renew', 'reduce' => 'Renew with Fewer Seats',
            'cancel' => 'Do Not Renew', default => 'Manual Review',
        };
    }

    public function getDecisionBadgeAttribute(): string
    {
        return match ($this->decision) {
            'renew' => 'success', 'reduce' => 'info', 'cancel' => 'danger', default => 'warning',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'success', 'cancelled' => 'secondary', default => 'warning',
        };
    }
}
