<?php

namespace Tests\Unit;

use App\Models\EmployeeProfile;
use App\Models\Location;
use App\Support\AttendanceGeofence;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceGeofenceTest extends TestCase
{
    public function test_strict_geofence_accepts_coordinates_inside_radius(): void
    {
        $employee = $this->employeeAtLocation('strict');
        $request = Request::create('/', 'POST', [
            'latitude' => 28.61391,
            'longitude' => 77.20901,
            'accuracy' => 15,
        ]);

        $result = AttendanceGeofence::evaluate($request, $employee, 'sign_in');

        $this->assertSame('inside', $result['sign_in_geofence_status']);
        $this->assertLessThanOrEqual(100, $result['sign_in_distance_metres']);
    }

    public function test_strict_geofence_rejects_coordinates_outside_radius(): void
    {
        $this->expectException(ValidationException::class);

        AttendanceGeofence::evaluate(Request::create('/', 'POST', [
            'latitude' => 28.7041,
            'longitude' => 77.1025,
            'accuracy' => 15,
        ]), $this->employeeAtLocation('strict'), 'sign_in');
    }

    public function test_warning_geofence_records_outside_status_without_blocking(): void
    {
        $result = AttendanceGeofence::evaluate(Request::create('/', 'POST', [
            'latitude' => 28.7041,
            'longitude' => 77.1025,
            'accuracy' => 15,
        ]), $this->employeeAtLocation('warning'), 'sign_out');

        $this->assertSame('outside', $result['sign_out_geofence_status']);
        $this->assertGreaterThan(100, $result['sign_out_distance_metres']);
    }

    private function employeeAtLocation(string $mode): EmployeeProfile
    {
        $location = new Location([
            'geofence_mode' => $mode,
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'geofence_radius_metres' => 100,
            'maximum_gps_accuracy_metres' => 100,
            'require_location_on_sign_in' => true,
            'require_location_on_sign_out' => true,
        ]);
        $location->id = 10;
        $location->name = 'Main Office';

        return (new EmployeeProfile())->setRelation('location', $location);
    }
}
