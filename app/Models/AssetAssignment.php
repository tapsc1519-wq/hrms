<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetAssignment extends Model
{
    protected $fillable = [
        'asset_id', 'user_id', 'department_id', 'assigned_by',
        'assigned_date', 'expected_return_date', 'actual_return_date',
        'status', 'condition_out', 'condition_in',
        'purpose', 'notes', 'acknowledgment_signature',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'expected_return_date' => 'date',
        'actual_return_date' => 'date',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function issueReports(): HasMany
    {
        return $this->hasMany(AssetIssueReport::class);
    }
}
