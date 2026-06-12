<?php

namespace App\Support;

use App\Models\HrShift;
use Carbon\Carbon;

class AttendanceCalculator
{
    public static function calculate(?HrShift $shift, Carbon $date, ?Carbon $signIn, ?Carbon $signOut): array
    {
        $workMinutes = ($signIn && $signOut) ? max(0, $signIn->diffInMinutes($signOut)) : 0;

        if (!$shift) {
            return [
                'work_minutes' => $workMinutes,
                'late_minutes' => 0,
                'early_leave_minutes' => 0,
                'overtime_minutes' => 0,
                'status' => $workMinutes > 0 ? 'present' : 'absent',
            ];
        }

        $shiftStart = Carbon::parse($date->toDateString().' '.substr((string) $shift->start_time, 0, 5));
        $shiftEnd = Carbon::parse($date->toDateString().' '.substr((string) $shift->end_time, 0, 5));

        if ($shift->is_night_shift || $shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd->addDay();
        }

        $lateMinutes = 0;
        if ($signIn) {
            $allowedStart = (clone $shiftStart)->addMinutes((int) $shift->grace_minutes);
            $lateMinutes = $signIn->greaterThan($allowedStart) ? $allowedStart->diffInMinutes($signIn) : 0;
        }

        $earlyLeaveMinutes = 0;
        $overtimeMinutes = 0;
        if ($signOut) {
            $earlyLeaveMinutes = $signOut->lessThan($shiftEnd) ? $signOut->diffInMinutes($shiftEnd) : 0;
            $overtimeMinutes = $signOut->greaterThan($shiftEnd) ? $shiftEnd->diffInMinutes($signOut) : 0;
        }

        $status = match (true) {
            $workMinutes <= 0 => 'absent',
            $workMinutes < (int) $shift->half_day_minutes => 'absent',
            $workMinutes < (int) $shift->full_day_minutes => 'half_day',
            default => 'present',
        };

        return [
            'work_minutes' => $workMinutes,
            'late_minutes' => $lateMinutes,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'status' => $status,
        ];
    }
}
