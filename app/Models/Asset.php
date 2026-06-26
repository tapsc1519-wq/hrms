<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asset extends Model
{
    protected $fillable = [
        'organization_id', 'acquisition_source', 'purchase_order_id',
        'purchase_order_item_id', 'goods_receipt_id',
        'category_id', 'vendor_id', 'location_id',
        'asset_brand_id', 'asset_model_id',
        'name', 'asset_tag', 'serial_number', 'model', 'brand',
        'specifications', 'specs', 'description', 'purchase_date', 'purchase_price',
        'warranty_expiry_date', 'warranty_terms', 'status', 'condition',
        'image', 'qr_code', 'notes',
    ];

    protected $casts = [
        'purchase_date'        => 'date',
        'warranty_expiry_date' => 'date',
        'purchase_price'       => 'decimal:2',
        'specs'                => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'vendor_id');
    }

    public function assetBrand(): BelongsTo
    {
        return $this->belongsTo(AssetBrand::class, 'asset_brand_id');
    }

    public function assetModel(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class, 'asset_model_id');
    }

    /** Resolved display name for brand (catalog or free-text fallback) */
    public function getBrandDisplayAttribute(): string
    {
        return $this->assetBrand?->name ?? $this->brand ?? '—';
    }

    /** Resolved display name for model (catalog or free-text fallback) */
    public function getModelDisplayAttribute(): string
    {
        return $this->assetModel?->name ?? $this->model ?? '—';
    }

    /** All non-null specs as [label => value] pairs */
    public function getSpecsDisplayAttribute(): array
    {
        return array_filter($this->specs ?? []);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(AssetAssignment::class)->where('status', 'active');
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    public function depreciationRecord(): HasOne
    {
        return $this->hasOne(DepreciationRecord::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(AssetRequest::class);
    }

    public function disposals(): HasMany
    {
        return $this->hasMany(AssetDisposal::class);
    }

    public function activeDisposal(): HasOne
    {
        return $this->hasOne(AssetDisposal::class)->whereIn('status', ['pending', 'approved'])->latestOfMany();
    }

    public function getCurrentValueAttribute(): float
    {
        if (!$this->purchase_price || !$this->purchase_date) return 0;
        $dep = $this->depreciationRecord;
        if (!$dep) return (float) $this->purchase_price;

        $years = now()->diffInYears($this->purchase_date);
        $annualDep = ($this->purchase_price - $dep->salvage_value) / $dep->useful_life_years;
        $currentValue = $this->purchase_price - ($annualDep * $years);
        return max($currentValue, $dep->salvage_value);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'available'   => 'success',
            'assigned'    => 'primary',
            'maintenance' => 'warning',
            'repair'      => 'info',
            'retired'     => 'secondary',
            'disposed'    => 'dark',
            'lost'        => 'danger',
            default       => 'secondary',
        };
    }
}
