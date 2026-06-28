<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceAgentCredential extends Model
{
    protected $fillable = ['organization_id', 'device_agent_id', 'key_prefix', 'key_hash', 'issued_at', 'last_used_at', 'revoked_at'];
    protected $hidden = ['key_hash'];
    protected $casts = ['issued_at' => 'datetime', 'last_used_at' => 'datetime', 'revoked_at' => 'datetime'];
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function deviceAgent(): BelongsTo { return $this->belongsTo(DeviceAgent::class); }
    public function getIsActiveAttribute(): bool { return ! $this->revoked_at; }
}
