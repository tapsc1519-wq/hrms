<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareAssignment extends Model
{
    protected $fillable = [
        'software_license_id', 'user_id', 'assigned_by',
        'assigned_date', 'returned_date', 'notes', 'status',
    ];

    protected $casts = [
        'assigned_date'  => 'date',
        'returned_date'  => 'date',
    ];

    // ── Relationships ───────────────────────────────────────────────────────

    public function license(): BelongsTo
    {
        return $this->belongsTo(SoftwareLicense::class, 'software_license_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
