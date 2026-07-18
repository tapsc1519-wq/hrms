<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'item_type', 'category_id', 'asset_brand_id', 'asset_model_id', 'software_id',
        'license_type', 'subscription_period', 'item_name', 'brand', 'model',
        'description', 'specifications', 'ordered_specs', 'quantity', 'received_quantity',
        'unit_price', 'tax_rate', 'total_price',
    ];

    protected $casts = [
        'ordered_specs' => 'array',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function assetBrand(): BelongsTo
    {
        return $this->belongsTo(AssetBrand::class, 'asset_brand_id');
    }

    public function assetModel(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class, 'asset_model_id');
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function softwareRequests(): HasMany
    {
        return $this->hasMany(SoftwareRequest::class);
    }

    public function softwareLicenses(): HasMany
    {
        return $this->hasMany(SoftwareLicense::class);
    }

    public function receiptItems(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function getPendingQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->received_quantity);
    }

    public function getLinkedSoftwareRequestCountAttribute(): int
    {
        return $this->softwareRequests->count();
    }

    public function getFulfilledSoftwareRequestCountAttribute(): int
    {
        return $this->softwareRequests->where('status', 'fulfilled')->count();
    }

    public function getOpenSoftwareRequestCountAttribute(): int
    {
        return $this->softwareRequests->whereIn('status', ['pending', 'approved'])->count();
    }

    public function getReceivedSoftwareSeatCountAttribute(): int
    {
        return (int) $this->softwareLicenses->sum('seats');
    }

    public function getUnfulfilledSoftwareRequestCountAttribute(): int
    {
        return max(0, $this->linked_software_request_count - $this->fulfilled_software_request_count);
    }
}
