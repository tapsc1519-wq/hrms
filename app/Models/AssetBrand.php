<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetBrand extends Model
{
    protected $fillable = [
        'organization_id', 'name', 'website', 'logo', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function models(): HasMany
    {
        return $this->hasMany(AssetModel::class, 'brand_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'asset_brand_id');
    }
}
