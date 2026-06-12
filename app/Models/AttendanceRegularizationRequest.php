<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRegularizationRequest extends Model
{
    protected $fillable = [
        'organization_id',
        'employee_profile_id',
        'user_id',
        'reviewed_by',
        'attendance_date',
        'request_type',
        'requested_sign_in_at',
        'requested_sign_out_at',
        'reason',
        'status',
        'review_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'requested_sign_in_at' => 'datetime',
        'requested_sign_out_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getRequestTypeLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->request_type));
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
