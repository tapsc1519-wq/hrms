<?php

namespace App\Support;

use App\Models\EmployeeProfile;
use App\Models\HrHoliday;
use Carbon\Carbon;

class AttendanceDayResolver
{
    public static function resolve(EmployeeProfile $employee, Carbon $date): array
    {
        $holiday = HrHoliday::where('organization_id', $employee->organization_id)
            ->whereDate('holiday_date', $date->toDateString())
            ->first();

        if ($holiday) {
            return [
                'day_type' => 'holiday',
                'holiday_id' => $holiday->id,
                'label' => $holiday->name,
            ];
        }

        $shift = $employee->shift;
        if ($shift && is_array($shift->working_days) && count($shift->working_days) > 0) {
            $dayKey = strtolower($date->format('D'));
            $dayKey = match ($dayKey) {
                'mon' => 'mon',
                'tue' => 'tue',
                'wed' => 'wed',
                'thu' => 'thu',
                'fri' => 'fri',
                'sat' => 'sat',
                'sun' => 'sun',
                default => $dayKey,
            };

            if (!in_array($dayKey, $shift->working_days, true)) {
                return [
                    'day_type' => 'weekly_off',
                    'holiday_id' => null,
                    'label' => 'Weekly Off',
                ];
            }
        }

        return [
            'day_type' => 'workday',
            'holiday_id' => null,
            'label' => 'Workday',
        ];
    }
}
