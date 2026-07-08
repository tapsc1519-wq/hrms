<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends PlatformModel
{
    protected $fillable = [
        'name',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'type',
        'status',
        'default_commission_percent',
        'payout_method',
        'payout_details',
        'notes',
    ];

    protected $casts = [
        'default_commission_percent' => 'decimal:2',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(OrganizationProductSubscription::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(PartnerCommission::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(PartnerLead::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'suspended' => 'danger',
            'inactive' => 'secondary',
            default => 'secondary',
        };
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?: $this->name;
    }
}
