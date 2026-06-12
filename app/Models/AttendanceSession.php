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
    ];

    protected $casts = [
        'sign_in_at' => 'datetime',
        'sign_out_at' => 'datetime',
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

    public function getDurationAttribute(): string
    {
        $hours = intdiv((int) $this->work_minutes, 60);
        $minutes = (int) $this->work_minutes % 60;

        return "{$hours}h {$minutes}m";
    }
}
