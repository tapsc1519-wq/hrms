<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AssetAssignment;
use App\Models\AssetHandoverRequest;
use App\Models\AssetRequest;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRegularizationRequest;
use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeProfile;
use App\Models\LeaveRequest;
use App\Models\PayrollRunItem;
use App\Models\SoftwareAssignment;
use App\Models\Task;
use App\Models\Ticket;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $org = $user->organization?->load('modules');
        $moduleEnabled = fn (string $module): bool => !$org || $org->hasModule($module);

        $hasItam = $moduleEnabled('itam');
        $hasSam = $moduleEnabled('sam');
        $hasHrms = $moduleEnabled('hrms');
        $hasPayroll = $moduleEnabled('payroll');
        $hasSupport = $moduleEnabled('support');

        $employee = EmployeeProfile::where('user_id', $user->id)
            ->with(['user.department', 'manager', 'facility', 'location', 'shift'])
            ->first();

        $todayAttendance = null;
        $recentLeaves = collect();
        $pendingDocuments = collect();
        $attendanceRegularizations = 0;

        if ($hasHrms && $employee) {
            $todayAttendance = AttendanceRecord::where('user_id', $user->id)
                ->whereDate('attendance_date', today())
                ->with('sessions')
                ->first();

            $recentLeaves = LeaveRequest::where('user_id', $user->id)
                ->with('leaveType')
                ->latest()
                ->take(4)
                ->get();

            $pendingDocuments = EmployeeDocumentRequest::where('employee_profile_id', $employee->id)
                ->whereIn('status', ['pending', 'rejected'])
                ->latest()
                ->take(4)
                ->get();

            $attendanceRegularizations = AttendanceRegularizationRequest::where('user_id', $user->id)
                ->where('status', 'pending')
                ->count();
        }

        $myAssets = $hasItam
            ? AssetAssignment::where('user_id', $user->id)
                ->where('status', 'active')
                ->with('asset.category')
                ->latest('assigned_date')
                ->take(5)
                ->get()
            : collect();

        $myRequests = $hasItam
            ? AssetRequest::where('requester_id', $user->id)->latest()->take(5)->get()
            : collect();

        $incomingHandovers = $hasItam
            ? AssetHandoverRequest::where('to_user_id', $user->id)->where('status', 'pending')->count()
            : 0;

        $myTickets = $hasSupport
            ? Ticket::where('requester_id', $user->id)->latest()->take(5)->get()
            : collect();

        $myTasks = Task::where('organization_id', $user->organization_id)
            ->where('assigned_to', $user->id)
            ->open()
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->latest()
            ->take(5)
            ->get();

        $softwareAssignments = $hasSam
            ? SoftwareAssignment::where('user_id', $user->id)
                ->where('status', 'active')
                ->with('license.software')
                ->latest('assigned_date')
                ->take(4)
                ->get()
            : collect();

        $latestPayslip = null;
        if ($hasPayroll && $employee) {
            $latestPayslip = PayrollRunItem::where('employee_profile_id', $employee->id)
                ->whereHas('run', fn ($q) => $q->whereIn('status', ['approved', 'paid']))
                ->with('run')
                ->latest()
                ->first();
        }

        $stats = [
            'assigned_assets' => $hasItam ? AssetAssignment::where('user_id', $user->id)->where('status', 'active')->count() : 0,
            'pending_requests' => $hasItam ? AssetRequest::where('requester_id', $user->id)->where('status', 'pending')->count() : 0,
            'open_tickets' => $hasSupport ? Ticket::where('requester_id', $user->id)->whereIn('status', ['open', 'in_progress'])->count() : 0,
            'pending_leaves' => $hasHrms ? LeaveRequest::where('user_id', $user->id)->where('status', 'pending')->count() : 0,
            'pending_documents' => $hasHrms && $employee ? EmployeeDocumentRequest::where('employee_profile_id', $employee->id)->whereIn('status', ['pending', 'rejected'])->count() : 0,
            'software_assigned' => $hasSam ? SoftwareAssignment::where('user_id', $user->id)->where('status', 'active')->count() : 0,
            'attendance_regularizations' => $attendanceRegularizations,
            'incoming_handovers' => $incomingHandovers,
            'open_tasks' => Task::where('organization_id', $user->organization_id)->where('assigned_to', $user->id)->open()->count(),
            'overdue_tasks' => Task::where('organization_id', $user->organization_id)->where('assigned_to', $user->id)->open()->whereNotNull('due_at')->where('due_at', '<', now())->count(),
        ];

        $managementCards = $this->managementCards($user, [
            'itam' => $hasItam,
            'hrms' => $hasHrms,
            'payroll' => $hasPayroll,
            'support' => $hasSupport,
        ]);

        return view('staff.dashboard', compact(
            'employee',
            'todayAttendance',
            'recentLeaves',
            'pendingDocuments',
            'myAssets',
            'myRequests',
            'myTickets',
            'myTasks',
            'softwareAssignments',
            'latestPayslip',
            'stats',
            'managementCards',
            'hasItam',
            'hasSam',
            'hasHrms',
            'hasPayroll',
            'hasSupport'
        ));
    }

    private function managementCards($user, array $enabled): array
    {
        $cards = [];

        if ($enabled['hrms'] && $user->hasPermission('employees.manage')) {
            $cards[] = ['title' => 'Employees', 'subtitle' => 'Manage employee profiles and onboarding', 'icon' => 'bi-person-vcard-fill', 'color' => 'green', 'route' => 'admin.employees.index'];
        }
        if ($enabled['hrms'] && $user->hasPermission('attendance.view')) {
            $cards[] = ['title' => 'Attendance', 'subtitle' => 'View attendance and monthly summaries', 'icon' => 'bi-clock-history', 'color' => 'blue', 'route' => 'admin.attendance.index'];
        }
        if ($enabled['hrms'] && $user->hasPermission('leaves.manage')) {
            $cards[] = ['title' => 'Leave Requests', 'subtitle' => 'Approve or reject employee leaves', 'icon' => 'bi-calendar-check-fill', 'color' => 'teal', 'route' => 'admin.leaves.index'];
        }
        if ($enabled['payroll'] && $user->hasPermission('payroll.setup')) {
            $cards[] = ['title' => 'Salary Setup', 'subtitle' => 'Manage salary structures and components', 'icon' => 'bi-cash-stack', 'color' => 'amber', 'route' => 'admin.payroll.index'];
        }
        if ($enabled['itam'] && $user->hasPermission('assets.view')) {
            $cards[] = ['title' => 'Assets', 'subtitle' => 'Manage hardware inventory', 'icon' => 'bi-box-seam-fill', 'color' => 'blue', 'route' => 'admin.assets.index'];
        }
        if ($enabled['itam'] && $user->hasPermission('assignments.view')) {
            $cards[] = ['title' => 'Assignments', 'subtitle' => 'Track assigned and returned assets', 'icon' => 'bi-person-check-fill', 'color' => 'purple', 'route' => 'admin.assignments.index'];
        }
        if ($enabled['itam'] && $user->hasPermission('requests.view')) {
            $cards[] = ['title' => 'Asset Requests', 'subtitle' => 'Review employee asset requests', 'icon' => 'bi-clipboard-check-fill', 'color' => 'orange', 'route' => 'admin.requests.index'];
        }
        if ($enabled['support'] && $user->hasPermission('tickets.manage')) {
            $cards[] = ['title' => 'Support Tickets', 'subtitle' => 'Respond to employee support tickets', 'icon' => 'bi-headset', 'color' => 'red', 'route' => 'admin.tickets.index'];
        }

        return $cards;
    }
}
