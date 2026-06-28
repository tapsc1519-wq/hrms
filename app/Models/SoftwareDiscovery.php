<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoftwareDiscovery extends Model
{
    protected $fillable = [
        'organization_id',
        'device_agent_id',
        'asset_id',
        'user_id',
        'software_id',
        'raw_name',
        'raw_publisher',
        'raw_version',
        'raw_edition',
        'raw_build_number',
        'executable',
        'product_code',
        'install_path',
        'uninstall_string',
        'install_date',
        'last_used_date',
        'usage_count',
        'total_runtime_minutes',
        'source',
        'status',
        'is_installed',
        'first_seen_at',
        'last_seen_at',
        'uninstalled_at',
        'confidence_score',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'install_date' => 'date',
        'last_used_date' => 'date',
        'reviewed_at' => 'datetime',
        'is_installed' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'uninstalled_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function deviceAgent(): BelongsTo
    {
        return $this->belongsTo(DeviceAgent::class);
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

    public function complianceActions(): HasMany
    {
        return $this->hasMany(SoftwareComplianceAction::class);
    }

    public function policyExceptions(): HasMany
    {
        return $this->hasMany(SoftwarePolicyException::class);
    }

    public function activePolicyException()
    {
        return $this->hasOne(SoftwarePolicyException::class)->active()->latestOfMany();
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
