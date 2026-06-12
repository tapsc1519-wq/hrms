<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLock extends Model
{
    protected $fillable = [
        'organization_id',
        'month',
        'locked_by',
        'locked_at',
        'notes',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
    ];

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public static function isLocked(int $organizationId, string $month): bool
    {
        return static::where('organization_id', $organizationId)->where('month', $month)->exists();
    }
}
