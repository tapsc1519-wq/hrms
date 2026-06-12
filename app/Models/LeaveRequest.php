<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'organization_id', 'employee_profile_id', 'user_id', 'reviewed_by', 'leave_type_id',
        'leave_type', 'from_date', 'to_date', 'total_days', 'reason',
        'status', 'review_notes', 'reviewed_at',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'reviewed_at' => 'datetime',
        'total_days' => 'decimal:2',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function leaveType(): BelongsTo { return $this->belongsTo(LeaveType::class); }

    public function getLeaveTypeLabelAttribute(): string
    {
        return $this->leaveType?->name ?? ucwords(str_replace('_', ' ', $this->leave_type));
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }
}
