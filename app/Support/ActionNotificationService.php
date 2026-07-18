<?php

namespace App\Support;

use App\Models\AssetHandoverRequest;
use App\Models\AssetRepair;
use App\Models\AssetRequest;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRegularizationRequest;
use App\Models\DeviceAgent;
use App\Models\EmployeeDocumentRequest;
use App\Models\LeaveRequest;
use App\Models\Organization;
use App\Models\OrganizationProductSubscription;
use App\Models\PartnerCommission;
use App\Models\PayrollRun;
use App\Models\SoftwareLicense;
use App\Models\SoftwareRequest;
use App\Models\SoftwareUsageReview;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;

class ActionNotificationService
{
    public static function forUser(?User $user): array
    {
        if (!$user) {
            return ['count' => 0, 'items' => []];
        }

        $items = [];

        if ($user->isSuperAdmin()) {
            $items = self::superAdminItems();
        } elseif (!$user->organization_id) {
            return ['count' => 0, 'items' => []];
        } elseif ($user->isAdmin()) {
            $items = self::adminItems($user);
        } elseif ($user->isStaff()) {
            $items = self::staffItems($user);
        }

        return [
            'count' => array_sum(array_column($items, 'count')),
            'items' => array_slice($items, 0, 10),
        ];
    }

    private static function superAdminItems(): array
    {
        $items = [];

        self::push($items, OrganizationProductSubscription::whereIn('status', ['overdue', 'suspended'])->count(), [
            'title' => 'Product subscriptions need attention',
            'subtitle' => 'Review overdue or suspended organization access',
            'icon' => 'bi-exclamation-triangle',
            'color' => 'danger',
            'url' => route('super-admin.product-subscriptions.index'),
            'priority' => 95,
        ]);

        self::push($items, OrganizationProductSubscription::where('status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', '>=', now()->toDateString())
            ->whereDate('trial_ends_at', '<=', now()->addDays(7)->toDateString())
            ->count(), [
            'title' => 'Trials ending soon',
            'subtitle' => 'Follow up before trial subscriptions expire',
            'icon' => 'bi-hourglass-split',
            'color' => 'warning',
            'url' => route('super-admin.product-subscriptions.index', ['status' => 'trial']),
            'priority' => 80,
        ]);

        self::push($items, PartnerCommission::where('status', 'pending')->count(), [
            'title' => 'Partner commissions pending',
            'subtitle' => 'Approve or process partner payouts',
            'icon' => 'bi-cash-coin',
            'color' => 'success',
            'url' => route('super-admin.partner-commissions.index', ['status' => 'pending']),
            'priority' => 70,
        ]);

        self::push($items, Organization::whereIn('billing_status', ['overdue', 'suspended'])->count(), [
            'title' => 'Organizations need billing review',
            'subtitle' => 'Check organization billing status and access',
            'icon' => 'bi-building-exclamation',
            'color' => 'danger',
            'url' => route('super-admin.organizations.index'),
            'priority' => 90,
        ]);

        return self::sortItems($items);
    }

    private static function adminItems(User $user): array
    {
        $org = $user->organization;
        $orgId = (int) $user->organization_id;
        $items = [];

        self::push($items, Task::where('organization_id', $orgId)
            ->open()
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count(), [
            'title' => 'Tasks are overdue',
            'subtitle' => 'Review delayed assigned work',
            'icon' => 'bi-list-task',
            'color' => 'danger',
            'url' => route('admin.tasks.index', ['due' => 'overdue']),
            'priority' => 100,
        ]);

        self::push($items, Task::where('organization_id', $orgId)->where('status', 'blocked')->count(), [
            'title' => 'Tasks are blocked',
            'subtitle' => 'Unblock work waiting for a decision',
            'icon' => 'bi-sign-stop',
            'color' => 'danger',
            'url' => route('admin.tasks.index', ['status' => 'blocked']),
            'priority' => 95,
        ]);

        self::push($items, Task::where('organization_id', $orgId)->where('status', 'review')->count(), [
            'title' => 'Tasks waiting for review',
            'subtitle' => 'Verify completed work and close tasks',
            'icon' => 'bi-check2-square',
            'color' => 'info',
            'url' => route('admin.tasks.index', ['status' => 'review']),
            'priority' => 70,
        ]);

        if ($org?->hasModule('itam')) {
            self::push($items, AssetRequest::where('organization_id', $orgId)->where('status', 'pending')->count(), [
                'title' => 'Asset requests need review',
                'subtitle' => 'Approve, reject, or fulfil employee requests',
                'icon' => 'bi-clipboard-check',
                'color' => 'warning',
                'url' => route('admin.requests.index', ['status' => 'pending']),
                'priority' => 75,
            ]);

            self::push($items, AssetRepair::where('organization_id', $orgId)->where('status', 'qc_pending')->count(), [
                'title' => 'Repair jobs need quality check',
                'subtitle' => 'Complete QC before returning repaired assets',
                'icon' => 'bi-wrench-adjustable',
                'color' => 'warning',
                'url' => route('admin.asset-repairs.index', ['status' => 'qc_pending']),
                'priority' => 85,
            ]);

            self::push($items, AssetRepair::where('organization_id', $orgId)
                ->whereIn('status', AssetRepair::OPEN_STATUSES)
                ->whereNotNull('expected_return_date')
                ->whereDate('expected_return_date', '<', today())
                ->count(), [
                'title' => 'Repair returns are overdue',
                'subtitle' => 'Follow up with IT, vendor, AMC, or market repair owner',
                'icon' => 'bi-calendar2-x',
                'color' => 'danger',
                'url' => route('admin.asset-repairs.index'),
                'priority' => 90,
            ]);
        }

        if ($org?->hasModule('support')) {
            self::push($items, Ticket::where('organization_id', $orgId)->whereIn('status', ['open', 'in_progress'])->count(), [
                'title' => 'Support tickets are open',
                'subtitle' => 'Reply, assign, or update ticket status',
                'icon' => 'bi-headset',
                'color' => 'primary',
                'url' => route('admin.tickets.index', ['status' => 'open']),
                'priority' => 60,
            ]);
        }

        if ($org?->hasModule('hrms') && $user->hasPermission('leaves.manage')) {
            self::push($items, LeaveRequest::where('organization_id', $orgId)->where('status', 'pending')->count(), [
                'title' => 'Leave requests pending',
                'subtitle' => 'Review employee leave applications',
                'icon' => 'bi-calendar-check',
                'color' => 'success',
                'url' => route('admin.leaves.index', ['status' => 'pending']),
                'priority' => 65,
            ]);
        }

        if ($org?->hasModule('hrms') && $user->hasPermission('attendance.regularizations.review')) {
            self::push($items, AttendanceRegularizationRequest::where('organization_id', $orgId)->where('status', 'pending')->count(), [
                'title' => 'Attendance corrections pending',
                'subtitle' => 'Approve or reject correction requests',
                'icon' => 'bi-clock-history',
                'color' => 'info',
                'url' => route('admin.attendance.regularizations', ['status' => 'pending']),
                'priority' => 65,
            ]);
        }

        if ($org?->hasModule('hrms') && $user->hasPermission('employees.documents')) {
            self::push($items, EmployeeDocumentRequest::where('organization_id', $orgId)->where('status', 'submitted')->count(), [
                'title' => 'Employee documents submitted',
                'subtitle' => 'Verify uploaded employee documents',
                'icon' => 'bi-file-earmark-check',
                'color' => 'purple',
                'url' => route('admin.employees.index'),
                'priority' => 55,
            ]);
        }

        if ($org?->hasModule('payroll') && ($user->hasPermission('payroll.approve') || $user->hasPermission('payroll.pay'))) {
            self::push($items, PayrollRun::where('organization_id', $orgId)->whereIn('status', ['draft', 'approved'])->count(), [
                'title' => 'Payroll actions pending',
                'subtitle' => 'Approve payroll or mark salary as paid',
                'icon' => 'bi-receipt-cutoff',
                'color' => 'danger',
                'url' => route('admin.payroll.runs'),
                'priority' => 90,
            ]);
        }

        if ($org?->hasModule('sam')) {
            self::push($items, DeviceAgent::where('organization_id', $orgId)
                ->where(fn($query) => $query->whereNull('last_seen_at')->orWhere('last_seen_at', '<', now()->subHours(24)))
                ->count(), [
                'title' => 'Endpoints need attention',
                'subtitle' => 'Review stale or offline device agents',
                'icon' => 'bi-router',
                'color' => 'danger',
                'url' => route('admin.agent-sources.index', ['health' => 'stale']),
                'priority' => 92,
            ]);

            self::push($items, DeviceAgent::where('organization_id', $orgId)
                ->where(fn($query) => $query->whereNull('asset_id')->orWhereNull('user_id'))
                ->count(), [
                'title' => 'Endpoints are not fully linked',
                'subtitle' => 'Link devices to assets and employees for accurate SAM data',
                'icon' => 'bi-link-45deg',
                'color' => 'warning',
                'url' => route('admin.agent-sources.index', ['linking' => 'asset_missing']),
                'priority' => 78,
            ]);

            self::push($items, SoftwareRequest::where('organization_id', $orgId)->where('status', 'pending')->count(), [
                'title' => 'Software requests need review',
                'subtitle' => 'Approve, reject, or allocate requested software',
                'icon' => 'bi-display',
                'color' => 'primary',
                'url' => route('admin.software-requests.index', ['status' => 'pending']),
                'priority' => 76,
            ]);

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
                'priority' => 72,
            ]);
        }

        return self::sortItems($items);
    }

    private static function staffItems(User $user): array
    {
        $org = $user->organization;
        $items = [];

        self::push($items, Task::where('organization_id', $user->organization_id)
            ->where('assigned_to', $user->id)
            ->open()
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count(), [
            'title' => 'Your tasks are overdue',
            'subtitle' => 'Update status or add a blocker note',
            'icon' => 'bi-list-task',
            'color' => 'danger',
            'url' => route('staff.tasks.index', ['due' => 'overdue']),
            'priority' => 100,
        ]);

        self::push($items, Task::where('organization_id', $user->organization_id)
            ->where('assigned_to', $user->id)
            ->whereIn('status', ['assigned', 'in_progress', 'blocked'])
            ->count(), [
            'title' => 'Tasks assigned to you',
            'subtitle' => 'Start work, update progress, or mark for review',
            'icon' => 'bi-list-task',
            'color' => 'primary',
            'url' => route('staff.tasks.index'),
            'priority' => 80,
        ]);

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
                'priority' => 95,
            ]);

            self::push($items, EmployeeDocumentRequest::whereHas('employee', fn($q) => $q->where('user_id', $user->id))
                ->where('status', 'pending')
                ->count(), [
                'title' => 'Documents requested by HR',
                'subtitle' => 'Upload pending employee documents',
                'icon' => 'bi-cloud-upload',
                'color' => 'warning',
                'url' => route('staff.profile.show'),
                'priority' => 82,
            ]);

            self::push($items, LeaveRequest::where('user_id', $user->id)->where('status', 'pending')->count(), [
                'title' => 'Leave request awaiting approval',
                'subtitle' => 'Track your submitted leave requests',
                'icon' => 'bi-calendar2-week',
                'color' => 'success',
                'url' => route('staff.leaves.index'),
                'priority' => 50,
            ]);

            self::push($items, AttendanceRegularizationRequest::where('user_id', $user->id)->where('status', 'pending')->count(), [
                'title' => 'Attendance correction awaiting review',
                'subtitle' => 'Track your correction request status',
                'icon' => 'bi-clock-history',
                'color' => 'info',
                'url' => route('staff.attendance.index'),
                'priority' => 50,
            ]);
        }

        if ($org?->hasModule('itam')) {
            self::push($items, AssetHandoverRequest::where('to_user_id', $user->id)->where('status', 'pending')->count(), [
                'title' => 'Incoming asset handover',
                'subtitle' => 'Accept or reject asset transfer requests',
                'icon' => 'bi-arrow-left-right',
                'color' => 'primary',
                'url' => route('staff.my-assets.index'),
                'priority' => 90,
            ]);

            self::push($items, AssetRequest::where('requester_id', $user->id)->whereIn('status', ['pending', 'approved'])->count(), [
                'title' => 'Asset requests in progress',
                'subtitle' => 'Check approval or fulfilment status',
                'icon' => 'bi-clipboard-plus',
                'color' => 'warning',
                'url' => route('staff.requests.index'),
                'priority' => 55,
            ]);
        }

        if ($org?->hasModule('sam')) {
            self::push($items, SoftwareUsageReview::where('owner_id', $user->id)->where('status', 'pending_user')->count(), [
                'title' => 'Software review waiting for you',
                'subtitle' => 'Confirm whether you still need assigned software',
                'icon' => 'bi-display',
                'color' => 'warning',
                'url' => route('staff.my-software.index'),
                'priority' => 78,
            ]);

            self::push($items, SoftwareRequest::where('requester_id', $user->id)->whereIn('status', ['pending', 'approved'])->count(), [
                'title' => 'Software requests in progress',
                'subtitle' => 'Track approval and allocation status',
                'icon' => 'bi-person-check',
                'color' => 'primary',
                'url' => route('staff.software-requests.index'),
                'priority' => 54,
            ]);
        }

        if ($org?->hasModule('support')) {
            self::push($items, Ticket::where('requester_id', $user->id)->whereIn('status', ['open', 'in_progress'])->count(), [
                'title' => 'Support tickets in progress',
                'subtitle' => 'View replies and ticket updates',
                'icon' => 'bi-headset',
                'color' => 'primary',
                'url' => route('staff.tickets.index'),
                'priority' => 45,
            ]);
        }

        return self::sortItems($items);
    }

    private static function push(array &$items, int $count, array $item): void
    {
        if ($count < 1) {
            return;
        }

        $items[] = array_merge(['priority' => 50], $item, ['count' => $count]);
    }

    private static function sortItems(array $items): array
    {
        usort($items, fn(array $a, array $b) => ($b['priority'] <=> $a['priority']) ?: ($b['count'] <=> $a['count']));

        return $items;
    }
}
