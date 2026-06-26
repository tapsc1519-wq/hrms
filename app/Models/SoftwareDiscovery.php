<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareDiscovery extends Model
{
    protected $fillable = [
        'organization_id',
        'asset_id',
        'user_id',
        'software_id',
        'raw_name',
        'raw_publisher',
        'raw_version',
        'executable',
        'install_path',
        'install_date',
        'last_used_date',
        'usage_count',
        'total_runtime_minutes',
        'source',
        'status',
        'confidence_score',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'install_date' => 'date',
        'last_used_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'mapped' => 'success',
            'ignored' => 'secondary',
            default => 'warning',
        };
    }
}
