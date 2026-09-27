<?php

namespace App\Support;

use App\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceGeofence
{
    public static function evaluate(Request $request, EmployeeProfile $employee, string $action): array
    {
        $location = $employee->location;
        $mode = $location?->geofence_mode ?? 'disabled';
        $requiredForAction = $action === 'sign_in'
            ? (bool) $location?->require_location_on_sign_in
            : (bool) $location?->require_location_on_sign_out;

        $prefix = $action === 'sign_in' ? 'sign_in' : 'sign_out';
        $result = [
            'work_location_id' => $location?->id,
            "{$prefix}_latitude" => null,
            "{$prefix}_longitude" => null,
            "{$prefix}_accuracy_metres" => null,
            "{$prefix}_distance_metres" => null,
            "{$prefix}_geofence_status" => 'disabled',
        ];

        if (!$location || $mode === 'disabled' || !$requiredForAction) {
            return $result;
        }

        $coordinates = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        $hasCoordinates = isset($coordinates['latitude'], $coordinates['longitude']);
        if (!$hasCoordinates) {
            return self::exceptionOrResult($mode, $result, $prefix, 'unavailable', 'Your location could not be detected. Enable location access and try again.');
        }

        if ($location->latitude === null || $location->longitude === null) {
            return self::exceptionOrResult($mode, $result, $prefix, 'unavailable', 'Geo-fencing is not fully configured for your work location. Please contact HR/Admin.');
        }

        $accuracy = isset($coordinates['accuracy']) ? (float) $coordinates['accuracy'] : null;
        $distance = self::distanceInMetres(
            (float) $location->latitude,
            (float) $location->longitude,
            (float) $coordinates['latitude'],
            (float) $coordinates['longitude']
        );

        $result["{$prefix}_latitude"] = $coordinates['latitude'];
        $result["{$prefix}_longitude"] = $coordinates['longitude'];
        $result["{$prefix}_accuracy_metres"] = $accuracy;
        $result["{$prefix}_distance_metres"] = $distance;

        if ($accuracy !== null && $accuracy > $location->maximum_gps_accuracy_metres) {
            return self::exceptionOrResult(
                $mode,
                $result,
                $prefix,
                'inaccurate',
                "GPS accuracy is {$accuracy} metres. Move to an open area and retry (maximum {$location->maximum_gps_accuracy_metres} metres)."
            );
        }

        if ($distance > $location->geofence_radius_metres) {
            return self::exceptionOrResult(
                $mode,
                $result,
                $prefix,
                'outside',
                "You are {$distance} metres from {$location->name}. The allowed radius is {$location->geofence_radius_metres} metres."
            );
        }

        $result["{$prefix}_geofence_status"] = 'inside';

        return $result;
    }

    private static function exceptionOrResult(string $mode, array $result, string $prefix, string $status, string $message): array
    {
        $result["{$prefix}_geofence_status"] = $status;

        if ($mode === 'strict') {
            throw ValidationException::withMessages(['location' => $message]);
        }

        return $result;
    }

    private static function distanceInMetres(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        $a = min(1, max(0, $a));

        return (int) round($earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
