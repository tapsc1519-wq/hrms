<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'organization_id', 'employee_profile_id', 'user_id', 'shift_id', 'holiday_id', 'attendance_date', 'day_type',
        'sign_in_at', 'sign_out_at', 'work_minutes', 'late_minutes', 'early_leave_minutes', 'overtime_minutes', 'status',
        'sign_in_ip', 'sign_out_ip', 'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'sign_in_at' => 'datetime',
        'sign_out_at' => 'datetime',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function shift(): BelongsTo { return $this->belongsTo(HrShift::class, 'shift_id'); }
    public function holiday(): BelongsTo { return $this->belongsTo(HrHoliday::class, 'holiday_id'); }
    public function sessions(): HasMany { return $this->hasMany(AttendanceSession::class)->orderBy('sign_in_at'); }

    public function getDayTypeLabelAttribute(): string
    {
        return match ($this->day_type) {
            'holiday' => 'Holiday',
            'weekly_off' => 'Weekly Off',
            default => 'Workday',
        };
    }

    public function getWorkDurationAttribute(): string
    {
        $hours = intdiv((int) $this->work_minutes, 60);
        $minutes = (int) $this->work_minutes % 60;
        return "{$hours}h {$minutes}m";
    }
}
