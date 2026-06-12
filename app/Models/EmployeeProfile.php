<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeProfile extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'reporting_manager_id',
        'facility_id',
        'location_id',
        'shift_id',
        'employee_code',
        'joining_date',
        'date_of_birth',
        'gender',
        'employment_type',
        'employment_status',
        'personal_email',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'ifsc_code',
        'pan_number',
        'uan_number',
        'pf_number',
        'esi_number',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'exit_date',
        'notes',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'date_of_birth' => 'date',
        'exit_date' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporting_manager_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(HrShift::class, 'shift_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class)->latest();
    }

    public function documentRequests(): HasMany
    {
        return $this->hasMany(EmployeeDocumentRequest::class)->latest();
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function attendanceRegularizationRequests(): HasMany
    {
        return $this->hasMany(AttendanceRegularizationRequest::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function salaryStructures(): HasMany
    {
        return $this->hasMany(EmployeeSalaryStructure::class);
    }

    public function getEmploymentTypeLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->employment_type));
    }

    public function getEmploymentStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->employment_status));
    }

    public function getEmploymentStatusBadgeAttribute(): string
    {
        return match ($this->employment_status) {
            'active' => 'success',
            'probation' => 'info',
            'notice' => 'warning',
            'resigned' => 'secondary',
            'terminated' => 'danger',
            default => 'secondary',
        };
    }
}
