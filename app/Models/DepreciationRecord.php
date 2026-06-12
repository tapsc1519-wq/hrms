<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepreciationRecord extends Model
{
    protected $fillable = [
        'asset_id', 'method', 'useful_life_years', 'salvage_value', 'depreciation_rate', 'start_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'salvage_value' => 'decimal:2',
        'depreciation_rate' => 'decimal:2',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function getAnnualDepreciationAttribute(): float
    {
        $asset = $this->asset;
        if (!$asset || !$asset->purchase_price) return 0;
        return ($asset->purchase_price - $this->salvage_value) / $this->useful_life_years;
    }

    public function getCurrentValueAttribute(): float
    {
        $asset = $this->asset;
        if (!$asset || !$asset->purchase_price) return 0;
        $years = now()->diffInYears($this->start_date);
        $currentValue = $asset->purchase_price - ($this->annual_depreciation * $years);
        return max($currentValue, $this->salvage_value);
    }
}
