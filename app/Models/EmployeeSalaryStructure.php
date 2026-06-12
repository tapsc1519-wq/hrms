<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeSalaryStructure extends Model
{
    protected $fillable = [
        'organization_id',
        'employee_profile_id',
        'effective_from',
        'gross_earnings',
        'total_deductions',
        'net_salary',
        'status',
        'notes',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'gross_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(EmployeeSalaryStructureComponent::class);
    }
}
