<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLock;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRegularizationRequest;
use App\Models\AttendanceSession;
use App\Models\EmployeeProfile;
use App\Support\AttendanceCalculator;
use App\Support\AttendanceDayResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index()
    {
        $employee = EmployeeProfile::where('user_id', auth()->id())->with('shift')->firstOrFail();
        $today = AttendanceRecord::where('user_id', auth()->id())->whereDate('attendance_date', today())->with(['shift', 'holiday', 'sessions'])->first();
        $records = AttendanceRecord::where('user_id', auth()->id())->with(['shift', 'holiday', 'sessions'])->latest('attendance_date')->paginate(20);
        $regularizations = AttendanceRegularizationRequest::where('user_id', auth()->id())
            ->latest('attendance_date')
            ->limit(10)
            ->get();

        return view('staff.attendance.index', compact('employee', 'today', 'records', 'regularizations'));
    }

    public function signIn(Request $request)
    {
        $employee = EmployeeProfile::where('user_id', auth()->id())->with('shift')->firstOrFail();
        $now = now();
        $metrics = AttendanceCalculator::calculate($employee->shift, today(), $now, null);
        $day = AttendanceDayResolver::resolve($employee, today());

        return DB::transaction(function () use ($employee, $now, $metrics, $day, $request) {
            $record = AttendanceRecord::firstOrCreate([
                'organization_id' => $employee->organization_id,
                'user_id' => auth()->id(),
                'attendance_date' => today()->toDateString(),
            ], [
                'employee_profile_id' => $employee->id,
                'shift_id' => $employee->shift_id,
                'holiday_id' => $day['holiday_id'],
                'day_type' => $day['day_type'],
                'sign_in_at' => $now,
                'sign_in_ip' => $request->ip(),
                'late_minutes' => $metrics['late_minutes'],
                'status' => 'present',
            ]);

            $activeSession = $record->sessions()->whereNull('sign_out_at')->first();
            if ($activeSession) {
                return back()->with('error', 'You are already signed in. Please sign out before starting another session.');
            }

            AttendanceSession::create([
                'attendance_record_id' => $record->id,
                'organization_id' => $employee->organization_id,
                'employee_profile_id' => $employee->id,
                'user_id' => auth()->id(),
                'sign_in_at' => $now,
                'sign_in_ip' => $request->ip(),
            ]);

            $this->refreshRecordFromSessions($record->fresh(['employee.shift', 'shift', 'sessions']));

            return back()->with('success', 'Signed in successfully.');
        });
    }

    public function signOut(Request $request)
    {
        $record = AttendanceRecord::where('user_id', auth()->id())->whereDate('attendance_date', today())->with(['employee.shift', 'shift', 'sessions'])->first();

        if (!$record) {
            return back()->with('error', 'Please sign in before signing out.');
        }

        $activeSession = $record->sessions->firstWhere('sign_out_at', null);
        if (!$activeSession) {
            return back()->with('error', 'There is no active sign-in session to sign out from.');
        }

        $now = now();
        $activeSession->update([
            'sign_out_at' => $now,
            'sign_out_ip' => $request->ip(),
            'work_minutes' => max(0, $activeSession->sign_in_at->diffInMinutes($now)),
        ]);

        $this->refreshRecordFromSessions($record->fresh(['employee.shift', 'shift', 'sessions']));

        return back()->with('success', 'Signed out successfully.');
    }

    public function regularize(Request $request)
    {
        $employee = EmployeeProfile::where('user_id', auth()->id())->firstOrFail();

        $data = $request->validate([
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'request_type' => ['required', 'in:missed_sign_in,missed_sign_out,time_correction,work_from_home,other'],
            'requested_sign_in_time' => ['nullable', 'date_format:H:i'],
            'requested_sign_out_time' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        abort_if(empty($data['requested_sign_in_time']) && empty($data['requested_sign_out_time']), 422, 'Please enter at least sign-in or sign-out time.');

        $date = Carbon::parse($data['attendance_date']);
        abort_if(AttendanceLock::isLocked($employee->organization_id, $date->format('Y-m')), 422, 'This attendance month is locked. Please contact HR/Admin.');

        $signIn = !empty($data['requested_sign_in_time']) ? Carbon::parse($date->toDateString().' '.$data['requested_sign_in_time']) : null;
        $signOut = !empty($data['requested_sign_out_time']) ? Carbon::parse($date->toDateString().' '.$data['requested_sign_out_time']) : null;

        abort_if($signIn && $signOut && $signOut->lessThanOrEqualTo($signIn), 422, 'Sign-out time must be after sign-in time.');

        AttendanceRegularizationRequest::create([
            'organization_id' => $employee->organization_id,
            'employee_profile_id' => $employee->id,
            'user_id' => auth()->id(),
            'attendance_date' => $date->toDateString(),
            'request_type' => $data['request_type'],
            'requested_sign_in_at' => $signIn,
            'requested_sign_out_at' => $signOut,
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        return back()->with('success', 'Attendance regularization request submitted.');
    }

    public function cancelRegularization(AttendanceRegularizationRequest $regularization)
    {
        abort_if($regularization->user_id !== auth()->id(), 403);
        abort_if($regularization->status !== 'pending', 422, 'Only pending requests can be cancelled.');

        $regularization->update(['status' => 'cancelled']);

        return back()->with('success', 'Regularization request cancelled.');
    }

    private function refreshRecordFromSessions(AttendanceRecord $record): void
    {
        $record->loadMissing(['employee.shift', 'shift', 'sessions']);

        $sessions = $record->sessions;
        $firstSignIn = $sessions->sortBy('sign_in_at')->first()?->sign_in_at;
        $lastCompletedSession = $sessions->whereNotNull('sign_out_at')->sortByDesc('sign_out_at')->first();
        $lastSignOut = $lastCompletedSession?->sign_out_at;
        $activeSession = $sessions->firstWhere('sign_out_at', null);
        $workMinutes = (int) $sessions->sum('work_minutes');

        if ($activeSession) {
            $workMinutes += max(0, $activeSession->sign_in_at->diffInMinutes(now()));
        }

        $employee = $record->employee;
        $shift = $record->shift ?? $employee?->shift;
        $metrics = AttendanceCalculator::calculate($shift, $record->attendance_date, $firstSignIn, $lastSignOut);
        $day = $employee ? AttendanceDayResolver::resolve($employee, $record->attendance_date) : ['day_type' => 'workday', 'holiday_id' => null];

        $record->update([
            'shift_id' => $record->shift_id ?? $employee?->shift_id,
            'holiday_id' => $record->holiday_id ?? $day['holiday_id'],
            'day_type' => $record->day_type ?? $day['day_type'],
            'sign_in_at' => $firstSignIn,
            'sign_out_at' => $activeSession ? null : $lastSignOut,
            'sign_in_ip' => $sessions->first()?->sign_in_ip,
            'sign_out_ip' => $activeSession ? null : $lastCompletedSession?->sign_out_ip,
            'work_minutes' => $workMinutes,
            'late_minutes' => $metrics['late_minutes'],
            'early_leave_minutes' => $activeSession ? 0 : $metrics['early_leave_minutes'],
            'overtime_minutes' => $activeSession ? 0 : $metrics['overtime_minutes'],
            'status' => $activeSession ? 'present' : $metrics['status'],
        ]);
    }
}
