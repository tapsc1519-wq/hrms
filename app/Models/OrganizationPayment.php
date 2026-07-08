<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrganizationPayment extends Model
{
    protected $fillable = [
        'organization_id',
        'amount',
        'payment_date',
        'period_start',
        'period_end',
        'payment_method',
        'reference_no',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function partnerCommission(): HasOne
    {
        return $this->hasOne(PartnerCommission::class, 'organization_payment_id');
    }
}
