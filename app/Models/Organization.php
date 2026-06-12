<?php

namespace App\Models;

use App\Support\ModuleRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = [
        'name', 'slug', 'email', 'phone', 'address', 'city', 'country',
        'logo', 'website', 'tax_number', 'status',
        'trial_months', 'trial_started_at', 'trial_ends_at',
        'billing_status', 'billing_cycle', 'monthly_amount',
        'subscription_ends_at', 'last_payment_at',
    ];

    protected $casts = [
        'trial_started_at' => 'datetime',
        'trial_ends_at' => 'date',
        'monthly_amount' => 'decimal:2',
        'subscription_ends_at' => 'date',
        'last_payment_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function assetCategories(): HasMany
    {
        return $this->hasMany(AssetCategory::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(OrganizationModule::class);
    }

    public function enabledModules(): HasMany
    {
        return $this->hasMany(OrganizationModule::class)->where('is_enabled', true);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrganizationPayment::class);
    }

    public function hasModule(string $module): bool
    {
        if ($this->relationLoaded('modules')) {
            $record = $this->modules->firstWhere('module_key', $module);
            return $record ? (bool) $record->is_enabled : true;
        }

        $record = $this->modules()->where('module_key', $module)->first();
        return $record ? (bool) $record->is_enabled : true;
    }

    public function syncModules(array $enabledKeys, ?int $updatedBy = null): void
    {
        $enabledKeys = ModuleRegistry::dependenciesFor($enabledKeys);
        $now = now();

        foreach (ModuleRegistry::keys() as $moduleKey) {
            $isEnabled = in_array($moduleKey, $enabledKeys, true);
            $this->modules()->updateOrCreate(
                ['module_key' => $moduleKey],
                [
                    'is_enabled' => $isEnabled,
                    'enabled_at' => $isEnabled ? $now : null,
                    'disabled_at' => $isEnabled ? null : $now,
                    'updated_by' => $updatedBy,
                    'monthly_price' => ModuleRegistry::monthlyPrice($moduleKey),
                ]
            );
        }

        $this->unsetRelation('modules');
        $this->load('modules');
        $this->refreshMonthlyAmount();
    }

    public function refreshMonthlyAmount(): void
    {
        $amount = $this->modules()
            ->where('is_enabled', true)
            ->sum('monthly_price');

        $this->forceFill(['monthly_amount' => $amount])->save();
    }

    public function getBillingStatusBadgeAttribute(): string
    {
        return match ($this->billing_status) {
            'active' => 'success',
            'overdue' => 'warning',
            'suspended' => 'danger',
            'cancelled' => 'secondary',
            default => 'primary',
        };
    }

    public function isTrialExpired(): bool
    {
        return $this->billing_status === 'trial'
            && $this->trial_ends_at
            && $this->trial_ends_at->isPast();
    }

    public function hasBillingAccess(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if (in_array($this->billing_status, ['suspended', 'cancelled', 'overdue'], true)) {
            return false;
        }

        if ($this->billing_status === 'active' && $this->subscription_ends_at && $this->subscription_ends_at->isPast()) {
            return false;
        }

        return !$this->isTrialExpired();
    }

    public function billingAccessMessage(): string
    {
        if ($this->status !== 'active') {
            return 'Your organization account is not active. Please contact the platform administrator.';
        }

        if ($this->billing_status === 'suspended') {
            return 'Your organization subscription is suspended. Please contact the platform administrator to restore access.';
        }

        if ($this->billing_status === 'cancelled') {
            return 'Your organization subscription is cancelled. Please contact the platform administrator to reactivate access.';
        }

        if ($this->billing_status === 'overdue') {
            return 'Your organization subscription payment is overdue. Please complete payment to continue using paid modules.';
        }

        if ($this->billing_status === 'active' && $this->subscription_ends_at && $this->subscription_ends_at->isPast()) {
            return 'Your paid subscription has expired. Please renew the subscription to continue using paid modules.';
        }

        if ($this->isTrialExpired()) {
            return 'Your free trial has expired. Please activate a paid subscription to continue using paid modules.';
        }

        return 'Your organization subscription does not allow access to this module.';
    }

    public function trialDaysRemaining(): ?int
    {
        if ($this->billing_status !== 'trial' || !$this->trial_ends_at) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->trial_ends_at->copy()->startOfDay(), false);
    }

    public function subscriptionDaysRemaining(): ?int
    {
        if ($this->billing_status !== 'active' || !$this->subscription_ends_at) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->subscription_ends_at->copy()->startOfDay(), false);
    }
}
