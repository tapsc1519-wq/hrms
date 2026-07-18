<?php

namespace App\Support;

class RoleTemplateRegistry
{
    public static function all(): array
    {
        return [
            [
                'name' => 'IT Manager',
                'portal_role' => 'admin',
                'description' => 'Manages IT assets, SAM, endpoints, support tickets and IT work queues.',
                'permissions' => [
                    'dashboard.view', 'assets.view', 'assets.create', 'assets.edit', 'assets.catalog',
                    'assignments.view', 'assignments.create', 'assignments.return',
                    'requests.view', 'requests.review', 'requests.fulfill',
                    'asset.repairs.manage', 'asset.repairs.qc', 'asset.repairs.close', 'vendors.manage',
                    'tasks.view', 'tasks.create', 'tasks.edit',
                    'software.manage', 'software.policies.manage', 'software.requests.view', 'software.requests.review',
                    'software.requests.fulfill', 'software.optimization.view', 'software.optimization.manage',
                    'software.agents.manage', 'endpoint.view', 'endpoint.software.manage', 'endpoint.device.control',
                    'tickets.manage', 'production.view', 'reports.view',
                ],
            ],
            [
                'name' => 'IT Support',
                'portal_role' => 'admin',
                'description' => 'Handles endpoint inventory, asset assignments, support tickets and basic IT tasks.',
                'permissions' => [
                    'dashboard.view', 'assets.view', 'assignments.view', 'assignments.create', 'assignments.return',
                    'requests.view', 'requests.fulfill', 'asset.repairs.manage', 'tasks.view', 'tasks.create',
                    'tasks.edit', 'endpoint.view', 'software.requests.view', 'tickets.manage',
                ],
            ],
            [
                'name' => 'HR Manager',
                'portal_role' => 'admin',
                'description' => 'Manages employee records, attendance, leaves, HR settings and HR tasks.',
                'permissions' => [
                    'dashboard.view', 'employees.manage', 'employees.documents', 'attendance.view', 'attendance.manage',
                    'attendance.regularizations.review', 'leaves.manage', 'leave_balances.manage', 'hrms.settings',
                    'tasks.view', 'tasks.create', 'tasks.edit', 'reports.view',
                ],
            ],
            [
                'name' => 'HR Executive',
                'portal_role' => 'admin',
                'description' => 'Maintains employee profiles, documents, attendance and leave operations.',
                'permissions' => [
                    'dashboard.view', 'employees.manage', 'employees.documents', 'attendance.view',
                    'attendance.regularizations.review', 'leaves.manage', 'tasks.view', 'tasks.create',
                ],
            ],
            [
                'name' => 'Procurement Manager',
                'portal_role' => 'admin',
                'description' => 'Manages suppliers, purchase orders, procurement requests and related reports.',
                'permissions' => [
                    'dashboard.view', 'assets.view', 'assets.create', 'assets.catalog', 'suppliers.manage',
                    'purchase_orders.manage', 'requests.view', 'requests.review', 'requests.fulfill',
                    'tasks.view', 'tasks.create', 'tasks.edit', 'reports.view',
                ],
            ],
            [
                'name' => 'Asset Manager',
                'portal_role' => 'admin',
                'description' => 'Maintains asset catalog, assets, assignments, repairs and disposal workflows.',
                'permissions' => [
                    'dashboard.view', 'assets.view', 'assets.create', 'assets.edit', 'assets.import', 'assets.catalog',
                    'assignments.view', 'assignments.create', 'assignments.return',
                    'requests.view', 'requests.review', 'requests.fulfill',
                    'asset.repairs.manage', 'asset.repairs.qc', 'asset.repairs.close',
                    'assets.disposal', 'assets.disposal.view', 'assets.disposal.request',
                    'tasks.view', 'tasks.create', 'tasks.edit', 'reports.view',
                ],
            ],
            [
                'name' => 'Finance Manager',
                'portal_role' => 'admin',
                'description' => 'Manages payroll setup/runs and reviews finance-related operational reports.',
                'permissions' => [
                    'dashboard.view', 'payroll.setup', 'payroll.run', 'payroll.approve', 'payroll.pay',
                    'payroll.export', 'tasks.view', 'tasks.create', 'reports.view',
                ],
            ],
            [
                'name' => 'Department Manager',
                'portal_role' => 'admin',
                'description' => 'Reviews department requests, tasks, attendance visibility and team activity.',
                'permissions' => [
                    'dashboard.view', 'assets.view', 'assignments.view', 'requests.view', 'requests.review',
                    'attendance.view', 'leaves.manage', 'tasks.view', 'tasks.create', 'tasks.edit',
                    'tickets.manage',
                ],
            ],
            [
                'name' => 'Support Agent',
                'portal_role' => 'admin',
                'description' => 'Works support tickets and assigned operational tasks.',
                'permissions' => [
                    'dashboard.view', 'tickets.manage', 'tasks.view', 'tasks.create', 'tasks.edit',
                    'assets.view', 'endpoint.view',
                ],
            ],
            [
                'name' => 'Auditor / Read-only Reviewer',
                'portal_role' => 'admin',
                'description' => 'Read-only reviewer for assets, reports, endpoints and audit evidence.',
                'permissions' => [
                    'dashboard.view', 'assets.view', 'assignments.view', 'requests.view',
                    'assets.disposal.view', 'endpoint.view', 'software.optimization.view',
                    'software.audit.export', 'production.view', 'reports.view',
                ],
            ],
            [
                'name' => 'Employee Self Service',
                'portal_role' => 'staff',
                'description' => 'Basic staff role for employee self-service pages.',
                'permissions' => [
                    'assets.disposal.request',
                ],
            ],
        ];
    }

    public static function names(): array
    {
        return array_column(self::all(), 'name');
    }
}
