<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetRepairPart extends Model
{
    protected $fillable = [
        'asset_repair_id',
        'part_name',
        'part_number',
        'quantity',
        'unit_cost',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function repair(): BelongsTo
    {
        return $this->belongsTo(AssetRepair::class, 'asset_repair_id');
    }
}
