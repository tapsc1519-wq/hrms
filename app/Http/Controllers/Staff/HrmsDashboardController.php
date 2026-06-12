<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeProfile;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;

class HrmsDashboardController extends Controller
{
    public function index()
    {
        $employee = EmployeeProfile::where('user_id', auth()->id())
            ->with(['user.department', 'manager', 'facility', 'location', 'shift'])
            ->first();

        if (!$employee) {
            return view('staff.profile.missing');
        }

        $today = AttendanceRecord::where('user_id', auth()->id())
            ->whereDate('attendance_date', today())
            ->first();

        $monthRecords = AttendanceRecord::where('user_id', auth()->id())
            ->whereYear('attendance_date', now()->year)
            ->whereMonth('attendance_date', now()->month)
            ->latest('attendance_date')
            ->get();

        $leaveBalances = LeaveType::where('organization_id', $employee->organization_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn($type) => LeaveBalance::ensure($employee, $type, now()->year));

        $recentLeaves = LeaveRequest::where('user_id', auth()->id())
            ->with('leaveType')
            ->latest()
            ->limit(5)
            ->get();

        $pendingDocuments = EmployeeDocumentRequest::where('employee_profile_id', $employee->id)
            ->whereIn('status', ['pending', 'rejected'])
            ->latest()
            ->limit(5)
            ->get();

        $stats = [
            'present_this_month' => $monthRecords->count(),
            'work_minutes_this_month' => $monthRecords->sum('work_minutes'),
            'pending_leaves' => LeaveRequest::where('user_id', auth()->id())->where('status', 'pending')->count(),
            'pending_documents' => EmployeeDocumentRequest::where('employee_profile_id', $employee->id)
                ->whereIn('status', ['pending', 'rejected'])
                ->count(),
            'available_leave_days' => $leaveBalances->sum(fn($balance) => $balance->available),
        ];

        return view('staff.hrms.dashboard', compact(
            'employee',
            'today',
            'monthRecords',
            'leaveBalances',
            'recentLeaves',
            'pendingDocuments',
            'stats'
        ));
    }
}
