<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetHandoverRequest extends Model
{
    protected $fillable = [
        'asset_assignment_id', 'asset_id', 'from_user_id', 'to_user_id',
        'handover_date', 'condition_in', 'notes', 'response_notes',
        'status', 'responded_at',
    ];

    protected $casts = [
        'handover_date' => 'date',
        'responded_at' => 'datetime',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(AssetAssignment::class, 'asset_assignment_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
