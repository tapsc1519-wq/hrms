<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryStructureComponent extends Model
{
    protected $fillable = [
        'employee_salary_structure_id',
        'payroll_component_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(EmployeeSalaryStructure::class, 'employee_salary_structure_id');
    }

    public function payrollComponent(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class);
    }
}
