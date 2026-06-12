<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollComponent extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'type',
        'status',
        'is_statutory',
        'description',
    ];

    protected $casts = [
        'is_statutory' => 'boolean',
    ];

    public function salaryComponents(): HasMany
    {
        return $this->hasMany(EmployeeSalaryStructureComponent::class);
    }
}
