<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLock;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRegularizationRequest;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\LeaveRequest;
use App\Support\AttendanceCalculator;
use App\Support\AttendanceDayResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendanceRecord::where('organization_id', $this->orgId())
            ->with(['user.department', 'employee.shift', 'shift', 'holiday']);

        if ($request->filled('date')) {
            $query->whereDate('attendance_date', $request->date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        if ($request->filled('department_id')) {
            $query->whereHas('user', fn($q) => $q->where('department_id', $request->department_id));
        }

        $records = $query->latest('attendance_date')->latest('sign_in_at')->paginate(30)->withQueryString();
        $departments = Department::where('organization_id', $this->orgId())->orderBy('name')->get();

        return view('admin.attendance.index', compact('records', 'departments'));
    }

    public function summary(Request $request)
    {
        return view('admin.attendance.summary', $this->summaryPayload($request));
    }

    public function exportSummary(Request $request)
    {
        $payload = $this->summaryPayload($request);
        $month = $payload['month'];
        $filename = "attendance-summary-{$month}.csv";

        return response()->streamDownload(function () use ($payload) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Employee Code',
                'Employee Name',
                'Department',
                'Shift',
                'Present Days',
                'Leave Days',
                'Holiday Days',
                'Weekly Off Days',
                'Payable Days',
                'Work Hours',
                'Late Minutes',
                'Early Leave Minutes',
                'Overtime Minutes',
                'Month Locked',
                'Locked By',
                'Locked At',
            ]);

            foreach ($payload['rows'] as $row) {
                $employee = $row['employee'];
                $payableDays = $row['present_days'] + $row['leave_days'] + $row['holiday_days'] + $row['weekly_off_days'];
                $workHours = round(((int) $row['work_minutes']) / 60, 2);

                fputcsv($out, [
                    $employee->employee_code,
                    $employee->user?->name,
                    $employee->user?->department?->name,
                    $employee->shift?->name,
                    $row['present_days'],
                    $row['leave_days'],
                    $row['holiday_days'],
                    $row['weekly_off_days'],
                    $payableDays,
                    $workHours,
                    $row['late_minutes'],
                    $row['early_leave_minutes'],
                    $row['overtime_minutes'],
                    $payload['attendanceLock'] ? 'Yes' : 'No',
                    $payload['attendanceLock']?->locker?->name,
                    $payload['attendanceLock']?->locked_at?->format('d-m-Y h:i A'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function lockMonth(Request $request)
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        AttendanceLock::updateOrCreate([
            'organization_id' => $this->orgId(),
            'month' => $data['month'],
        ], [
            'locked_by' => auth()->id(),
            'locked_at' => now(),
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Attendance month locked successfully.');
    }

    public function unlockMonth(Request $request)
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        AttendanceLock::where('organization_id', $this->orgId())
            ->where('month', $data['month'])
            ->delete();

        return back()->with('success', 'Attendance month unlocked successfully.');
    }

    public function regularizations(Request $request)
    {
        $query = AttendanceRegularizationRequest::where('organization_id', $this->orgId())
            ->with(['user.department', 'employee', 'reviewer']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('department_id')) {
            $query->whereHas('user', fn($q) => $q->where('department_id', $request->department_id));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        $requests = $query->latest('attendance_date')->paginate(30)->withQueryString();
        $departments = Department::where('organization_id', $this->orgId())->orderBy('name')->get();
        $requestMonths = $requests->getCollection()
            ->pluck('attendance_date')
            ->filter()
            ->map(fn($date) => $date->format('Y-m'))
            ->unique()
            ->values();
        $lockedMonths = AttendanceLock::where('organization_id', $this->orgId())
            ->whereIn('month', $requestMonths)
            ->pluck('month')
            ->flip();

        return view('admin.attendance.regularizations', compact('requests', 'departments', 'lockedMonths'));
    }

    public function approveRegularization(Request $request, AttendanceRegularizationRequest $regularization)
    {
        if ($message = $this->regularizationReviewBlocker($regularization)) {
            return back()->with('error', $message);
        }

        $data = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($regularization, $data) {
            $regularization->loadMissing('employee.shift');

            $record = AttendanceRecord::firstOrNew([
                'organization_id' => $regularization->organization_id,
                'employee_profile_id' => $regularization->employee_profile_id,
                'user_id' => $regularization->user_id,
                'attendance_date' => $regularization->attendance_date->toDateString(),
            ]);

            if ($regularization->requested_sign_in_at) {
                $record->sign_in_at = $regularization->requested_sign_in_at;
            }

            if ($regularization->requested_sign_out_at) {
                $record->sign_out_at = $regularization->requested_sign_out_at;
            }

            $metrics = AttendanceCalculator::calculate($regularization->employee?->shift, $regularization->attendance_date, $record->sign_in_at, $record->sign_out_at);
            $day = $regularization->employee
                ? AttendanceDayResolver::resolve($regularization->employee, $regularization->attendance_date)
                : ['day_type' => 'workday', 'holiday_id' => null];
            $record->shift_id = $record->shift_id ?? $regularization->employee?->shift_id;
            $record->holiday_id = $record->holiday_id ?? $day['holiday_id'];
            $record->day_type = $record->day_type ?? $day['day_type'];
            $record->work_minutes = $metrics['work_minutes'];
            $record->late_minutes = $metrics['late_minutes'];
            $record->early_leave_minutes = $metrics['early_leave_minutes'];
            $record->overtime_minutes = $metrics['overtime_minutes'];
            $record->status = $metrics['status'];
            $record->notes = trim(($record->notes ? $record->notes.PHP_EOL : '').'Regularized via request #'.$regularization->id);
            $record->save();

            $regularization->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_notes' => $data['review_notes'] ?? null,
            ]);
        });

        return back()->with('success', 'Attendance regularization approved and attendance record updated.');
    }

    public function rejectRegularization(Request $request, AttendanceRegularizationRequest $regularization)
    {
        if ($message = $this->regularizationReviewBlocker($regularization)) {
            return back()->with('error', $message);
        }

        $data = $request->validate([
            'review_notes' => ['required', 'string', 'max:1000'],
        ]);

        $regularization->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'],
        ]);

        return back()->with('success', 'Attendance regularization rejected.');
    }

    private function regularizationReviewBlocker(AttendanceRegularizationRequest $regularization): ?string
    {
        abort_if($regularization->organization_id !== $this->orgId(), 403);

        if ($regularization->status !== 'pending') {
            return 'Only pending regularization requests can be reviewed. This request is already ' . $regularization->status . '.';
        }

        if (AttendanceLock::isLocked($regularization->organization_id, $regularization->attendance_date->format('Y-m'))) {
            return 'This attendance month is locked. Please unlock the month before approving or rejecting regularization requests.';
        }

        return null;
    }

    private function summaryPayload(Request $request): array
    {
        $month = $request->input('month', now()->format('Y-m'));
        abort_if(!preg_match('/^\d{4}-\d{2}$/', $month), 422, 'Invalid month format.');

        [$year, $monthNumber] = array_map('intval', explode('-', $month));
        $start = now()->setDate($year, $monthNumber, 1)->startOfDay();
        $end = (clone $start)->endOfMonth();

        $employees = EmployeeProfile::where('organization_id', $this->orgId())
            ->with(['user.department', 'shift'])
            ->whereHas('user', function ($q) use ($request) {
                if ($request->filled('department_id')) {
                    $q->where('department_id', $request->department_id);
                }
                if ($request->filled('search')) {
                    $search = $request->search;
                    $q->where(fn($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                }
            })
            ->get()
            ->sortBy(fn($employee) => $employee->user?->name ?? '')
            ->values();

        $attendance = AttendanceRecord::where('organization_id', $this->orgId())
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('employee_profile_id');

        $leaves = LeaveRequest::where('organization_id', $this->orgId())
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $end->toDateString())
            ->whereDate('to_date', '>=', $start->toDateString())
            ->get()
            ->groupBy('employee_profile_id');

        $period = collect(iterator_to_array(\Carbon\CarbonPeriod::create($start->copy(), $end->copy())));

        $rows = $employees->map(function ($employee) use ($attendance, $leaves, $period) {
            $records = $attendance->get($employee->id, collect());
            $employeeLeaves = $leaves->get($employee->id, collect());
            $dayTypes = $period->map(fn($date) => AttendanceDayResolver::resolve($employee, $date)['day_type']);

            return [
                'employee' => $employee,
                'present_days' => $records->count(),
                'work_minutes' => $records->sum('work_minutes'),
                'late_minutes' => $records->sum('late_minutes'),
                'early_leave_minutes' => $records->sum('early_leave_minutes'),
                'overtime_minutes' => $records->sum('overtime_minutes'),
                'leave_days' => $employeeLeaves->sum('total_days'),
                'holiday_days' => $dayTypes->filter(fn($type) => $type === 'holiday')->count(),
                'weekly_off_days' => $dayTypes->filter(fn($type) => $type === 'weekly_off')->count(),
            ];
        });

        $departments = Department::where('organization_id', $this->orgId())->orderBy('name')->get();
        $attendanceLock = AttendanceLock::where('organization_id', $this->orgId())
            ->where('month', $month)
            ->with('locker')
            ->first();

        return compact('rows', 'departments', 'month', 'attendanceLock');
    }
}
