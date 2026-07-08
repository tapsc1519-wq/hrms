<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationProductSubscription extends PlatformModel
{
    protected $fillable = [
        'organization_id',
        'product_id',
        'partner_id',
        'status',
        'plan_name',
        'billing_cycle',
        'monthly_amount',
        'commission_percent',
        'trial_started_at',
        'trial_ends_at',
        'subscription_started_at',
        'subscription_ends_at',
        'last_payment_at',
        'product_database',
        'product_domain',
        'notes',
    ];

    protected $casts = [
        'monthly_amount' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'trial_started_at' => 'datetime',
        'trial_ends_at' => 'date',
        'subscription_started_at' => 'datetime',
        'subscription_ends_at' => 'date',
        'last_payment_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'trial' => 'primary',
            'active' => 'success',
            'overdue' => 'warning',
            'suspended' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }
}
