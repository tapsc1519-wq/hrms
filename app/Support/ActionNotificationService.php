<?php

namespace App\Support;

use App\Models\AssetHandoverRequest;
use App\Models\AssetRequest;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRegularizationRequest;
use App\Models\EmployeeDocumentRequest;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\SoftwareLicense;
use App\Models\Ticket;
use App\Models\User;

class ActionNotificationService
{
    public static function forUser(?User $user): array
    {
        if (!$user || !$user->organization_id) {
            return ['count' => 0, 'items' => []];
        }

        $items = [];

        if ($user->isAdmin()) {
            $items = self::adminItems($user);
        } elseif ($user->isStaff()) {
            $items = self::staffItems($user);
        }

        return [
            'count' => array_sum(array_column($items, 'count')),
            'items' => array_slice($items, 0, 8),
        ];
    }

    private static function adminItems(User $user): array
    {
        $org = $user->organization;
        $orgId = (int) $user->organization_id;
        $items = [];

        if ($org?->hasModule('itam')) {
            self::push($items, AssetRequest::where('organization_id', $orgId)->where('status', 'pending')->count(), [
                'title' => 'Asset requests need review',
                'subtitle' => 'Approve, reject, or fulfil employee requests',
                'icon' => 'bi-clipboard-check',
                'color' => 'warning',
                'url' => route('admin.requests.index', ['status' => 'pending']),
            ]);
        }

        if ($org?->hasModule('support')) {
            self::push($items, Ticket::where('organization_id', $orgId)->whereIn('status', ['open', 'in_progress'])->count(), [
                'title' => 'Support tickets are open',
                'subtitle' => 'Reply, assign, or update ticket status',
                'icon' => 'bi-headset',
                'color' => 'primary',
                'url' => route('admin.tickets.index', ['status' => 'open']),
            ]);
        }

        if ($org?->hasModule('hrms') && $user->hasPermission('leaves.manage')) {
            self::push($items, LeaveRequest::where('organization_id', $orgId)->where('status', 'pending')->count(), [
                'title' => 'Leave requests pending',
                'subtitle' => 'Review employee leave applications',
                'icon' => 'bi-calendar-check',
                'color' => 'success',
                'url' => route('admin.leaves.index', ['status' => 'pending']),
            ]);
        }

        if ($org?->hasModule('hrms') && $user->hasPermission('attendance.regularizations.review')) {
            self::push($items, AttendanceRegularizationRequest::where('organization_id', $orgId)->where('status', 'pending')->count(), [
                'title' => 'Attendance corrections pending',
                'subtitle' => 'Approve or reject correction requests',
                'icon' => 'bi-clock-history',
                'color' => 'info',
                'url' => route('admin.attendance.regularizations', ['status' => 'pending']),
            ]);
        }

        if ($org?->hasModule('hrms') && $user->hasPermission('employees.documents')) {
            self::push($items, EmployeeDocumentRequest::where('organization_id', $orgId)->where('status', 'submitted')->count(), [
                'title' => 'Employee documents submitted',
                'subtitle' => 'Verify uploaded employee documents',
                'icon' => 'bi-file-earmark-check',
                'color' => 'purple',
                'url' => route('admin.employees.index'),
            ]);
        }

        if ($org?->hasModule('payroll') && ($user->hasPermission('payroll.approve') || $user->hasPermission('payroll.pay'))) {
            self::push($items, PayrollRun::where('organization_id', $orgId)->whereIn('status', ['draft', 'approved'])->count(), [
                'title' => 'Payroll actions pending',
                'subtitle' => 'Approve payroll or mark salary as paid',
                'icon' => 'bi-receipt-cutoff',
                'color' => 'danger',
                'url' => route('admin.payroll.runs'),
            ]);
        }

        if ($org?->hasModule('sam')) {
            self::push($items, SoftwareLicense::where('organization_id', $orgId)
                ->where('status', 'active')
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>=', now()->toDateString())
                ->whereDate('expiry_date', '<=', now()->addDays(30)->toDateString())
                ->count(), [
                'title' => 'Software licenses expiring soon',
                'subtitle' => 'Renew or review license compliance',
                'icon' => 'bi-key',
                'color' => 'warning',
                'url' => route('admin.software-licenses.index'),
            ]);
        }

        return $items;
    }

    private static function staffItems(User $user): array
    {
        $org = $user->organization;
        $items = [];

        if ($org?->hasModule('hrms')) {
            self::push($items, AttendanceRecord::where('user_id', $user->id)
                ->whereDate('attendance_date', today())
                ->whereNotNull('sign_in_at')
                ->whereNull('sign_out_at')
                ->count(), [
                'title' => 'Sign-out is pending',
                'subtitle' => 'Complete today\'s attendance before leaving',
                'icon' => 'bi-box-arrow-right',
                'color' => 'danger',
                'url' => route('staff.attendance.index'),
            ]);

            self::push($items, EmployeeDocumentRequest::whereHas('employee', fn($q) => $q->where('user_id', $user->id))
                ->where('status', 'pending')
                ->count(), [
                'title' => 'Documents requested by HR',
                'subtitle' => 'Upload pending employee documents',
                'icon' => 'bi-cloud-upload',
                'color' => 'warning',
                'url' => route('staff.profile.show'),
            ]);

            self::push($items, LeaveRequest::where('user_id', $user->id)->where('status', 'pending')->count(), [
                'title' => 'Leave request awaiting approval',
                'subtitle' => 'Track your submitted leave requests',
                'icon' => 'bi-calendar2-week',
                'color' => 'success',
                'url' => route('staff.leaves.index'),
            ]);

            self::push($items, AttendanceRegularizationRequest::where('user_id', $user->id)->where('status', 'pending')->count(), [
                'title' => 'Attendance correction awaiting review',
                'subtitle' => 'Track your correction request status',
                'icon' => 'bi-clock-history',
                'color' => 'info',
                'url' => route('staff.attendance.index'),
            ]);
        }

        if ($org?->hasModule('itam')) {
            self::push($items, AssetHandoverRequest::where('to_user_id', $user->id)->where('status', 'pending')->count(), [
                'title' => 'Incoming asset handover',
                'subtitle' => 'Accept or reject asset transfer requests',
                'icon' => 'bi-arrow-left-right',
                'color' => 'primary',
                'url' => route('staff.my-assets.index'),
            ]);

            self::push($items, AssetRequest::where('requester_id', $user->id)->whereIn('status', ['pending', 'approved'])->count(), [
                'title' => 'Asset requests in progress',
                'subtitle' => 'Check approval or fulfilment status',
                'icon' => 'bi-clipboard-plus',
                'color' => 'warning',
                'url' => route('staff.requests.index'),
            ]);
        }

        if ($org?->hasModule('support')) {
            self::push($items, Ticket::where('requester_id', $user->id)->whereIn('status', ['open', 'in_progress'])->count(), [
                'title' => 'Support tickets in progress',
                'subtitle' => 'View replies and ticket updates',
                'icon' => 'bi-headset',
                'color' => 'primary',
                'url' => route('staff.tickets.index'),
            ]);
        }

        return $items;
    }

    private static function push(array &$items, int $count, array $item): void
    {
        if ($count < 1) {
            return;
        }

        $items[] = array_merge($item, ['count' => $count]);
    }
}
