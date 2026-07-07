<?php

namespace App\Support;

class PermissionRegistry
{
    public static function groups(): array
    {
        return [
            'Dashboard' => [
                'dashboard.view' => 'View dashboard',
            ],
            'Assets' => [
                'assets.view' => 'View assets',
                'assets.create' => 'Create assets',
                'assets.edit' => 'Edit assets',
                'assets.delete' => 'Delete assets',
                'assets.import' => 'Bulk import assets',
                'assets.catalog' => 'Manage asset catalog',
                'assets.disposal' => 'Manage asset disposal',
                'assets.disposal.request' => 'Raise disposal requests',
                'assets.disposal.approve' => 'Approve or reject disposal requests',
                'assets.disposal.complete' => 'Complete approved disposals',
                'assets.disposal.view' => 'View disposal history',
            ],
            'Assignments' => [
                'assignments.view' => 'View assignments',
                'assignments.create' => 'Assign assets',
                'assignments.return' => 'Return assets',
            ],
            'Requests' => [
                'requests.view' => 'View asset requests',
                'requests.review' => 'Approve or reject requests',
                'requests.fulfill' => 'Fulfill approved requests',
            ],
            'Suppliers & Purchases' => [
                'suppliers.manage' => 'Manage suppliers',
                'purchase_orders.manage' => 'Manage purchase orders',
            ],
            'AMC & Repairs' => [
                'asset.repairs.manage' => 'Manage repair jobs and AMC contracts',
                'asset.repairs.qc' => 'Perform repair quality checks',
                'asset.repairs.close' => 'Close repair jobs and return assets',
                'vendors.manage' => 'Manage repair and service vendors',
            ],
            'Operations' => [
                'maintenance.manage' => 'Manage maintenance',
                'facilities.manage' => 'Manage facilities and locations',
                'departments.manage' => 'Manage departments',
            ],
            'Support' => [
                'tickets.manage' => 'Manage support tickets',
            ],
            'Software' => [
                'software.manage' => 'Manage software assets and licenses',
                'software.policies.manage' => 'Review software policy and create remediation tasks',
                'software.audit.export' => 'Export the SAM audit evidence package',
                'software.requests.view' => 'View software requests',
                'software.requests.review' => 'Approve or reject software requests',
                'software.requests.fulfill' => 'Allocate licenses for approved software requests',
                'software.optimization.view' => 'View software usage and optimization',
                'software.optimization.manage' => 'Start and complete license reclamation reviews',
                'software.agents.manage' => 'Manage device agents and inventory API tokens',
                'endpoint.view' => 'View managed endpoints and command history',
                'endpoint.software.manage' => 'Install and uninstall approved software on endpoints',
                'endpoint.device.control' => 'Lock or restart managed endpoints',
            ],
            'HRMS' => [
                'hrms.dashboard' => 'View HRMS dashboard',
                'employees.manage' => 'Manage employee profiles',
                'employees.documents' => 'Manage employee documents',
                'attendance.view' => 'View attendance records',
                'attendance.manage' => 'Manage attendance and locks',
                'attendance.regularizations.review' => 'Review attendance regularization requests',
                'leaves.manage' => 'Manage leave requests',
                'leave_balances.manage' => 'Manage leave balances',
                'hrms.settings' => 'Manage HRMS settings, shifts, holidays and leave types',
                'hrms.manage' => 'Full HRMS access (legacy)',
            ],
            'Payroll' => [
                'payroll.setup' => 'Manage salary structures and payroll components',
                'payroll.run' => 'Generate and view payroll runs',
                'payroll.approve' => 'Approve payroll runs',
                'payroll.pay' => 'Mark payroll as paid',
                'payroll.export' => 'Export payroll and payslips',
                'payroll.manage' => 'Full payroll access',
            ],
            'Administration' => [
                'roles.manage' => 'Manage roles and permissions',
                'reports.view' => 'View reports',
            ],
        ];
    }

    public static function all(): array
    {
        return collect(self::groups())->flatMap(fn($items) => $items)->keys()->all();
    }

    public static function grants(array $heldPermissions, string $requiredPermission): bool
    {
        if (in_array($requiredPermission, $heldPermissions, true)) {
            return true;
        }

        foreach (self::impliedPermissions() as $broadPermission => $permissions) {
            if (in_array($broadPermission, $heldPermissions, true) && in_array($requiredPermission, $permissions, true)) {
                return true;
            }
        }

        return false;
    }

    public static function impliedPermissions(): array
    {
        return [
            'hrms.manage' => [
                'hrms.dashboard',
                'employees.manage',
                'employees.documents',
                'attendance.view',
                'attendance.manage',
                'attendance.regularizations.review',
                'leaves.manage',
                'leave_balances.manage',
                'hrms.settings',
                'payroll.setup',
            ],
            'payroll.manage' => [
                'payroll.setup',
                'payroll.run',
                'payroll.approve',
                'payroll.pay',
                'payroll.export',
            ],
            'assets.disposal' => [
                'assets.disposal.request',
                'assets.disposal.approve',
                'assets.disposal.complete',
                'assets.disposal.view',
            ],
            'assets.disposal.request' => [
                'assets.disposal.view',
            ],
            'assets.disposal.approve' => [
                'assets.disposal.view',
            ],
            'assets.disposal.complete' => [
                'assets.disposal.view',
            ],
            'asset.repairs.manage' => [
                'asset.repairs.qc',
                'asset.repairs.close',
            ],
            'software.manage' => [
                'software.requests.view',
                'software.requests.review',
                'software.requests.fulfill',
                'software.optimization.view',
                'software.optimization.manage',
                'software.agents.manage',
                'endpoint.view',
                'endpoint.software.manage',
                'endpoint.device.control',
                'software.policies.manage',
                'software.audit.export',
            ],
            'software.requests.review' => [
                'software.requests.view',
            ],
            'software.requests.fulfill' => [
                'software.requests.view',
            ],
            'software.optimization.manage' => [
                'software.optimization.view',
            ],
            'software.agents.manage' => [
                'endpoint.view',
            ],
            'endpoint.software.manage' => [
                'endpoint.view',
            ],
            'endpoint.device.control' => [
                'endpoint.view',
            ],
        ];
    }
}
