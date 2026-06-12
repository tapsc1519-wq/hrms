<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrmsSetting extends Model
{
    protected $fillable = [
        'organization_id', 'working_days', 'office_start_time', 'office_end_time',
        'grace_minutes', 'half_day_minutes', 'full_day_minutes', 'allow_weekend_attendance',
    ];

    protected $casts = [
        'working_days' => 'array',
        'allow_weekend_attendance' => 'boolean',
    ];

    public static function forOrganization(int $organizationId): self
    {
        return static::firstOrCreate(['organization_id' => $organizationId], [
            'working_days' => ['mon', 'tue', 'wed', 'thu', 'fri'],
            'office_start_time' => '09:30:00',
            'office_end_time' => '18:30:00',
            'grace_minutes' => 15,
            'half_day_minutes' => 240,
            'full_day_minutes' => 480,
            'allow_weekend_attendance' => false,
        ]);
    }
}
