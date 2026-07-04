<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetAmcContract extends Model
{
    protected $fillable = [
        'organization_id',
        'vendor_id',
        'contract_number',
        'title',
        'coverage_type',
        'start_date',
        'end_date',
        'response_sla_hours',
        'resolution_sla_hours',
        'parts_included',
        'onsite_support',
        'document_path',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'response_sla_hours' => 'integer',
        'resolution_sla_hours' => 'integer',
        'parts_included' => 'boolean',
        'onsite_support' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'vendor_id');
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'asset_amc_contract_assets')->withTimestamps();
    }

    public function repairs(): HasMany
    {
        return $this->hasMany(AssetRepair::class, 'amc_contract_id');
    }
}
