<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeviceAgent extends Model
{
    protected $fillable = ['organization_id', 'asset_id', 'user_id', 'device_uuid', 'hostname', 'serial_number', 'os_name', 'os_version', 'architecture', 'agent_version', 'ip_address', 'status', 'enrolled_at', 'last_seen_at', 'last_inventory_at', 'hardware_info', 'network_info', 'security_info', 'user_info', 'sync_interval_minutes', 'last_error', 'last_error_at'];
    protected $casts = ['enrolled_at' => 'datetime', 'last_seen_at' => 'datetime', 'last_inventory_at' => 'datetime', 'last_error_at' => 'datetime', 'hardware_info' => 'array', 'network_info' => 'array', 'security_info' => 'array', 'user_info' => 'array'];

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function discoveries(): HasMany { return $this->hasMany(SoftwareDiscovery::class); }
    public function commands(): HasMany { return $this->hasMany(AgentCommand::class); }
    public function credential(): HasOne { return $this->hasOne(DeviceAgentCredential::class); }

    public function getHealthStatusAttribute(): string
    {
        if (! $this->last_seen_at || $this->last_seen_at->lt(now()->subDays(7))) return 'offline';
        if ($this->last_seen_at->lt(now()->subHours(24))) return 'stale';
        return 'healthy';
    }
}
