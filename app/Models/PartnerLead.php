<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerLead extends PlatformModel
{
    protected $fillable = [
        'partner_id',
        'product_id',
        'converted_organization_id',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'expected_monthly_value',
        'commission_percent',
        'stage',
        'expected_close_date',
        'converted_at',
        'notes',
    ];

    protected $casts = [
        'expected_monthly_value' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'expected_close_date' => 'date',
        'converted_at' => 'datetime',
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
        return $this->belongsTo(Organization::class, 'converted_organization_id');
    }

    public function getStageBadgeAttribute(): string
    {
        return match ($this->stage) {
            'won' => 'success',
            'lost' => 'secondary',
            'proposal' => 'warning',
            'demo' => 'info',
            'contacted' => 'primary',
            default => 'light text-dark',
        };
    }
}
