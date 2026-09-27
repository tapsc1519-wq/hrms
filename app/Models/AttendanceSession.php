<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSession extends Model
{
    protected $fillable = [
        'attendance_record_id',
        'organization_id',
        'employee_profile_id',
        'user_id',
        'sign_in_at',
        'sign_out_at',
        'work_minutes',
        'sign_in_ip',
        'sign_out_ip',
        'work_location_id',
        'sign_in_latitude',
        'sign_in_longitude',
        'sign_in_accuracy_metres',
        'sign_in_distance_metres',
        'sign_in_geofence_status',
        'sign_out_latitude',
        'sign_out_longitude',
        'sign_out_accuracy_metres',
        'sign_out_distance_metres',
        'sign_out_geofence_status',
    ];

    protected $casts = [
        'sign_in_at' => 'datetime',
        'sign_out_at' => 'datetime',
        'sign_in_latitude' => 'decimal:7',
        'sign_in_longitude' => 'decimal:7',
        'sign_out_latitude' => 'decimal:7',
        'sign_out_longitude' => 'decimal:7',
    ];

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'work_location_id');
    }

    public function getDurationAttribute(): string
    {
        $hours = intdiv((int) $this->work_minutes, 60);
        $minutes = (int) $this->work_minutes % 60;

        return "{$hours}h {$minutes}m";
    }
}
