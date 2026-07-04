<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $table = 'suppliers';

    protected $fillable = [
        'organization_id', 'user_id', 'partner_type', 'name', 'code', 'email', 'phone',
        'address', 'city', 'country', 'contact_person', 'contact_phone',
        'website', 'tax_number', 'bank_details', 'notes', 'logo', 'status', 'rating',
    ];

    protected $casts = ['rating' => 'decimal:2'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'vendor_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'vendor_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'vendor_id');
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class, 'vendor_id');
    }

    public function assetRepairs(): HasMany
    {
        return $this->hasMany(AssetRepair::class, 'vendor_id');
    }

    public function amcContracts(): HasMany
    {
        return $this->hasMany(AssetAmcContract::class, 'vendor_id');
    }
}
