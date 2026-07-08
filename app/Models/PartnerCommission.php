<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerCommission extends PlatformModel
{
    protected $fillable = [
        'partner_id',
        'product_id',
        'organization_id',
        'organization_payment_id',
        'organization_product_subscription_id',
        'payment_amount',
        'commission_percent',
        'commission_amount',
        'payment_date',
        'period_start',
        'period_end',
        'status',
        'approved_at',
        'approved_by',
        'paid_at',
        'paid_by',
        'cancelled_at',
        'cancelled_by',
        'notes',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'payment_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(OrganizationPayment::class, 'organization_payment_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(OrganizationProductSubscription::class, 'organization_product_subscription_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'primary',
            'paid' => 'success',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }
}
