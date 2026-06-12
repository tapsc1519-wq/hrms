<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $fillable = [
        'organization_id',
        'employee_profile_id',
        'leave_type_id',
        'year',
        'opening_balance',
        'credited',
        'used',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'credited' => 'decimal:2',
        'used' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function getAvailableAttribute(): float
    {
        return (float) $this->opening_balance + (float) $this->credited - (float) $this->used;
    }

    public static function ensure(EmployeeProfile $employee, LeaveType $leaveType, int $year): self
    {
        return static::firstOrCreate([
            'organization_id' => $employee->organization_id,
            'employee_profile_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => $year,
        ], [
            'opening_balance' => 0,
            'credited' => $leaveType->annual_quota,
            'used' => 0,
        ]);
    }
}
