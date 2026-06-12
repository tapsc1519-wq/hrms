<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRunItemComponent extends Model
{
    protected $fillable = [
        'payroll_run_item_id',
        'payroll_component_id',
        'name',
        'code',
        'type',
        'monthly_amount',
        'payable_amount',
    ];

    protected $casts = [
        'monthly_amount' => 'decimal:2',
        'payable_amount' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PayrollRunItem::class, 'payroll_run_item_id');
    }

    public function payrollComponent(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class);
    }
}
