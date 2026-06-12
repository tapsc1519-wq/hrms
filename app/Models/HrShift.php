<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrShift extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'start_time',
        'end_time',
        'grace_minutes',
        'half_day_minutes',
        'full_day_minutes',
        'working_days',
        'is_night_shift',
        'status',
        'description',
    ];

    protected $casts = [
        'working_days' => 'array',
        'is_night_shift' => 'boolean',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(EmployeeProfile::class, 'shift_id');
    }

    public function getTimeRangeAttribute(): string
    {
        return substr((string) $this->start_time, 0, 5).' - '.substr((string) $this->end_time, 0, 5);
    }

    public function getWorkingDaysLabelAttribute(): string
    {
        $labels = [
            'mon' => 'Mon',
            'tue' => 'Tue',
            'wed' => 'Wed',
            'thu' => 'Thu',
            'fri' => 'Fri',
            'sat' => 'Sat',
            'sun' => 'Sun',
        ];

        return collect($this->working_days ?? [])
            ->map(fn($day) => $labels[$day] ?? ucfirst($day))
            ->implode(', ');
    }
}
