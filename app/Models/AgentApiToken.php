<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentApiToken extends Model
{
    protected $fillable = ['organization_id', 'name', 'token_prefix', 'token_hash', 'created_by', 'assigned_user_id', 'purpose', 'last_used_at', 'expires_at', 'revoked_at'];
    protected $hidden = ['token_hash'];
    protected $casts = ['last_used_at' => 'datetime', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function assignedUser(): BelongsTo { return $this->belongsTo(User::class, 'assigned_user_id'); }

    public function getIsActiveAttribute(): bool
    {
        return ! $this->revoked_at && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
