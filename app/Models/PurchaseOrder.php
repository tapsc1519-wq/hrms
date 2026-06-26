<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'organization_id', 'vendor_id', 'created_by', 'approved_by',
        'po_number', 'order_date', 'expected_delivery_date', 'actual_delivery_date',
        'status', 'subtotal', 'tax_amount', 'discount_amount', 'total_amount',
        'shipping_address', 'terms_conditions', 'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'vendor_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'draft'              => 'secondary',
            'sent'               => 'info',
            'confirmed'          => 'primary',
            'partially_received' => 'warning',
            'received'           => 'success',
            'cancelled'          => 'danger',
            default              => 'secondary',
        };
    }
}
