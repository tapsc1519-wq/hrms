<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeProfile;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Illuminate\Support\Collection;

class HrmsDashboardController extends Controller
{
    public function index()
    {
        $orgId = $this->orgId();
        $today = now()->toDateString();
        $year = now()->year;

        $stats = [
            'employees' => EmployeeProfile::where('organization_id', $orgId)->count(),
            'active_employees' => EmployeeProfile::where('organization_id', $orgId)->where('employment_status', 'active')->count(),
            'today_present' => AttendanceRecord::where('organization_id', $orgId)->whereDate('attendance_date', $today)->count(),
            'currently_signed_in' => AttendanceRecord::where('organization_id', $orgId)->whereDate('attendance_date', $today)->whereNull('sign_out_at')->count(),
            'pending_leaves' => LeaveRequest::where('organization_id', $orgId)->where('status', 'pending')->count(),
            'pending_documents' => EmployeeDocumentRequest::where('organization_id', $orgId)->where('status', 'pending')->count(),
            'low_leave_balances' => LeaveBalance::where('organization_id', $orgId)->where('year', $year)->get()->filter(fn($balance) => $balance->available <= 2)->count(),
        ];

        $recentJoiners = EmployeeProfile::where('organization_id', $orgId)
            ->with(['user.department'])
            ->latest('joining_date')
            ->limit(6)
            ->get();

        $pendingLeaves = LeaveRequest::where('organization_id', $orgId)
            ->where('status', 'pending')
            ->with(['user.department', 'leaveType'])
            ->latest()
            ->limit(6)
            ->get();

        $pendingDocuments = EmployeeDocumentRequest::where('organization_id', $orgId)
            ->where('status', 'pending')
            ->with(['employee.user.department'])
            ->latest()
            ->limit(6)
            ->get();

        $departmentHeadcount = EmployeeProfile::where('organization_id', $orgId)
            ->with('user.department')
            ->get()
            ->groupBy(fn($employee) => $employee->user?->department?->name ?? 'No Department')
            ->map(fn(Collection $employees, string $name) => [
                'name' => $name,
                'count' => $employees->count(),
            ])
            ->sortByDesc('count')
            ->values();

        $attendanceTrend = collect(range(6, 0))->map(function (int $daysAgo) use ($orgId) {
            $date = now()->subDays($daysAgo);

            return [
                'label' => $date->format('d M'),
                'count' => AttendanceRecord::where('organization_id', $orgId)
                    ->whereDate('attendance_date', $date->toDateString())
                    ->count(),
            ];
        });

        return view('admin.hrms.dashboard', compact(
            'stats',
            'recentJoiners',
            'pendingLeaves',
            'pendingDocuments',
            'departmentHeadcount',
            'attendanceTrend'
        ));
    }
}
