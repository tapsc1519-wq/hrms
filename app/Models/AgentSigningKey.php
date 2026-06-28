<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentSigningKey extends Model
{
    protected $fillable = ['organization_id', 'encrypted_private_key', 'public_key_xml', 'fingerprint', 'rotated_at'];
    protected $hidden = ['encrypted_private_key'];
    protected $casts = ['rotated_at' => 'datetime'];
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
}
