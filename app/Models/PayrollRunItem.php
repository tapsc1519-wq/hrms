<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRunItem extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'organization_id',
        'employee_profile_id',
        'user_id',
        'employee_salary_structure_id',
        'days_in_month',
        'present_days',
        'leave_days',
        'holiday_days',
        'weekly_off_days',
        'payable_days',
        'work_minutes',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'gross_earnings',
        'total_deductions',
        'net_salary',
        'remarks',
    ];

    protected $casts = [
        'present_days' => 'decimal:2',
        'leave_days' => 'decimal:2',
        'holiday_days' => 'decimal:2',
        'weekly_off_days' => 'decimal:2',
        'payable_days' => 'decimal:2',
        'gross_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(EmployeeSalaryStructure::class, 'employee_salary_structure_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(PayrollRunItemComponent::class);
    }
}
