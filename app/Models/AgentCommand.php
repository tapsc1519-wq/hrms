<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentCommand extends Model
{
    protected $fillable = ['organization_id', 'device_agent_id', 'command_uuid', 'command_type', 'payload', 'priority', 'status', 'available_at', 'expires_at', 'delivered_at', 'executed_at', 'result', 'error_message', 'created_by'];
    protected $casts = ['payload' => 'array', 'result' => 'array', 'available_at' => 'datetime', 'expires_at' => 'datetime', 'delivered_at' => 'datetime', 'executed_at' => 'datetime'];
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function deviceAgent(): BelongsTo { return $this->belongsTo(DeviceAgent::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'success', 'failed' => 'danger', 'delivered' => 'info',
            'cancelled', 'expired' => 'secondary', default => 'warning',
        };
    }
}
