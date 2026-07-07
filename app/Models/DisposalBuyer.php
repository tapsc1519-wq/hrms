<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisposalBuyer extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'contact_person',
        'email',
        'phone',
        'address',
        'tax_number',
        'status',
        'notes',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function disposals(): HasMany
    {
        return $this->hasMany(AssetDisposal::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'employee' => 'Employee Buyer',
            'external_buyer' => 'External Buyer',
            'vendor_recycler' => 'Vendor / Recycler',
            'auction_buyer' => 'Auction Buyer',
            'donation_recipient' => 'Donation Recipient',
            default => ucwords(str_replace('_', ' ', $this->type)),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'inactive' => 'secondary',
            'blacklisted' => 'danger',
            default => 'secondary',
        };
    }
}
