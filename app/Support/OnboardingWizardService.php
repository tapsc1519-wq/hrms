<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetCategory;
use App\Models\AssetRequest;
use App\Models\Department;
use App\Models\DeviceAgent;
use App\Models\EmployeeProfile;
use App\Models\Facility;
use App\Models\OrganizationRole;
use App\Models\Software;
use App\Models\SoftwareLicense;
use App\Models\Supplier;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;

class OnboardingWizardService
{
    public static function forOrganization(User $user): array
    {
        $organization = $user->organization?->load('modules');
        $orgId = (int) $user->organization_id;
        $moduleEnabled = fn (string $module): bool => !$organization || $organization->hasModule($module);

        $stages = [
            [
                'key' => 'foundation',
                'title' => 'Company Foundation',
                'subtitle' => 'Create the base structure used by every module.',
                'icon' => 'bi-building',
                'items' => [
                    self::item((bool) $organization, 'Confirm organization profile', 'The admin account must be linked with an organization before setup can continue.', 'Linked', 'Review Dashboard', route('admin.dashboard')),
                    self::item(Facility::where('organization_id', $orgId)->exists(), 'Add facilities and locations', 'Facilities help assets, employees, attendance and repairs point to the correct place.', self::countLabel(Facility::where('organization_id', $orgId)->count(), 'facility', 'facilities'), 'Open Facilities', route('admin.facilities.index'), 'facilities.manage', $user),
                    self::item(Department::where('organization_id', $orgId)->exists(), 'Add departments', 'Departments make employee ownership, approvals and reporting easier to understand.', self::countLabel(Department::where('organization_id', $orgId)->count(), 'department', 'departments'), 'Open Departments', route('admin.departments.index'), 'departments.manage', $user),
                    self::item(OrganizationRole::where('organization_id', $orgId)->exists(), 'Create roles and permissions', 'Roles keep employees, IT, HR, support and managers inside the correct access boundary.', self::countLabel(OrganizationRole::where('organization_id', $orgId)->count(), 'role', 'roles'), 'Open Roles', route('admin.roles.index'), 'roles.manage', $user),
                ],
            ],
            [
                'key' => 'people',
                'title' => 'People Setup',
                'subtitle' => 'Add employee records before assigning work, assets or attendance.',
                'icon' => 'bi-people',
                'items' => [
                    self::item(EmployeeProfile::where('organization_id', $orgId)->exists(), 'Add employees', 'Employee profiles connect people with assets, tasks, software, attendance and payroll.', self::countLabel(EmployeeProfile::where('organization_id', $orgId)->count(), 'employee', 'employees'), 'Open Employees', route('admin.employees.index'), 'employees.manage', $user),
                    self::item(EmployeeProfile::where('organization_id', $orgId)->whereIn('employment_status', ['active', 'probation', 'notice'])->exists(), 'Keep active employees ready', 'At least one active employee should exist before live testing user workflows.', self::countLabel(EmployeeProfile::where('organization_id', $orgId)->whereIn('employment_status', ['active', 'probation', 'notice'])->count(), 'active employee', 'active employees'), 'Review Employees', route('admin.employees.index'), 'employees.manage', $user),
                ],
            ],
        ];

        if ($moduleEnabled('itam')) {
            $pendingRequests = AssetRequest::where('organization_id', $orgId)->where('status', 'pending')->count();

            $stages[] = [
                'key' => 'itam',
                'title' => 'IT Asset Setup',
                'subtitle' => 'Prepare asset catalog, inventory and assignment workflows.',
                'icon' => 'bi-pc-display',
                'items' => [
                    self::item(AssetCategory::where('organization_id', $orgId)->exists(), 'Build asset catalog', 'Categories, brands and models keep asset records consistent during import and daily use.', self::countLabel(AssetCategory::where('organization_id', $orgId)->count(), 'category', 'categories'), 'Open Catalog', route('admin.catalog.index'), 'assets.catalog', $user),
                    self::item(Asset::where('organization_id', $orgId)->exists(), 'Load assets', 'Add or import laptops, desktops, phones, accessories and other company-owned items.', self::countLabel(Asset::where('organization_id', $orgId)->count(), 'asset', 'assets'), 'Open Assets', route('admin.assets.index'), 'assets.view', $user),
                    self::item(Supplier::where('organization_id', $orgId)->whereIn('partner_type', ['supplier', 'both'])->exists(), 'Add suppliers', 'Supplier records keep procurement, warranty and purchase history connected.', self::countLabel(Supplier::where('organization_id', $orgId)->whereIn('partner_type', ['supplier', 'both'])->count(), 'supplier', 'suppliers'), 'Open Suppliers', route('admin.suppliers.index'), 'suppliers.manage', $user),
                    self::item(AssetAssignment::whereHas('asset', fn ($query) => $query->where('organization_id', $orgId))->where('status', 'active')->exists(), 'Start asset assignments', 'Assign assets to employees so ownership and return workflows are visible.', self::countLabel(AssetAssignment::whereHas('asset', fn ($query) => $query->where('organization_id', $orgId))->where('status', 'active')->count(), 'active assignment', 'active assignments'), 'Open Assignments', route('admin.assignments.index'), 'assignments.view', $user),
                    self::item($pendingRequests === 0, 'Review pending asset requests', 'Pending employee requests should be approved, rejected or fulfilled before go-live.', $pendingRequests === 0 ? 'Clear' : self::countLabel($pendingRequests, 'pending request', 'pending requests'), 'Review Requests', route('admin.requests.index', ['status' => 'pending']), 'requests.view', $user),
                ],
            ];
        }

        if ($moduleEnabled('sam')) {
            $unlinkedEndpoints = DeviceAgent::where('organization_id', $orgId)
                ->where(fn ($query) => $query->whereNull('asset_id')->orWhereNull('user_id'))
                ->count();

            $stages[] = [
                'key' => 'sam',
                'title' => 'SAM & Endpoint Setup',
                'subtitle' => 'Prepare software compliance and agent enrollment.',
                'icon' => 'bi-hdd-network',
                'items' => [
                    self::item(Software::where('organization_id', $orgId)->exists(), 'Add software catalog', 'Software titles are required for discovery matching, policy and compliance reporting.', self::countLabel(Software::where('organization_id', $orgId)->count(), 'software title', 'software titles'), 'Open Software', route('admin.software.index'), 'software.manage', $user),
                    self::item(SoftwareLicense::where('organization_id', $orgId)->exists(), 'Add software licenses', 'License records make audit position, allocation and renewal tracking useful.', self::countLabel(SoftwareLicense::where('organization_id', $orgId)->count(), 'license', 'licenses'), 'Open Licenses', route('admin.software-licenses.index'), 'software.manage', $user),
                    self::item(DeviceAgent::where('organization_id', $orgId)->exists(), 'Enroll at least one endpoint', 'Install the agent on a test device and confirm the endpoint reports inventory.', self::countLabel(DeviceAgent::where('organization_id', $orgId)->count(), 'endpoint', 'endpoints'), 'Open Endpoints', route('admin.agent-sources.index'), 'endpoint.view', $user),
                    self::item($unlinkedEndpoints === 0, 'Link endpoints to assets and employees', 'Endpoint links improve SAM ownership, audit accuracy and command targeting.', $unlinkedEndpoints === 0 ? 'Clear' : self::countLabel($unlinkedEndpoints, 'unlinked endpoint', 'unlinked endpoints'), 'Review Links', route('admin.agent-sources.index', ['linking' => 'asset_missing']), 'endpoint.view', $user),
                ],
            ];
        }

        if ($moduleEnabled('support')) {
            $activeTickets = Ticket::where('organization_id', $orgId)->whereIn('status', ['open', 'in_progress'])->count();

            $stages[] = [
                'key' => 'support',
                'title' => 'Support Desk',
                'subtitle' => 'Check that support queues are visible and under control.',
                'icon' => 'bi-headset',
                'items' => [
                    self::item($activeTickets === 0, 'Review active support tickets', 'Open and in-progress tickets should have owners before production handover.', $activeTickets === 0 ? 'Clear' : self::countLabel($activeTickets, 'active ticket', 'active tickets'), 'Open Tickets', route('admin.tickets.index'), 'tickets.manage', $user),
                ],
            ];
        }

        $overdueTasks = Task::where('organization_id', $orgId)->open()->whereNotNull('due_at')->where('due_at', '<', now())->count();
        $blockedTasks = Task::where('organization_id', $orgId)->where('status', 'blocked')->count();
        $readiness = ProductionReadinessService::forOrganization($user);

        $stages[] = [
            'key' => 'workflow',
            'title' => 'Workflow & Go-Live',
            'subtitle' => 'Use tasks and launch checks to close the final gaps.',
            'icon' => 'bi-rocket-takeoff',
            'items' => [
                self::item(Task::where('organization_id', $orgId)->exists(), 'Create the first task', 'Tasks give setup owners a clear place to track action items and blockers.', self::countLabel(Task::where('organization_id', $orgId)->count(), 'task', 'tasks'), 'Open Tasks', route('admin.tasks.index'), 'tasks.view', $user),
                self::item($overdueTasks === 0, 'Clear overdue tasks', 'Overdue tasks should be completed, rescheduled or reassigned before launch.', $overdueTasks === 0 ? 'Clear' : self::countLabel($overdueTasks, 'overdue task', 'overdue tasks'), 'Review Overdue', route('admin.tasks.index', ['due' => 'overdue']), 'tasks.view', $user),
                self::item($blockedTasks === 0, 'Resolve blocked tasks', 'Blocked tasks indicate decisions or missing information that can slow production rollout.', $blockedTasks === 0 ? 'Clear' : self::countLabel($blockedTasks, 'blocked task', 'blocked tasks'), 'Review Blockers', route('admin.tasks.index', ['status' => 'blocked']), 'tasks.view', $user),
                self::item($readiness['score'] >= 80, 'Review production readiness', 'Use the production checklist to verify live server, module and operational readiness.', $readiness['score'] . '% ready', 'Open Readiness', route('admin.production-readiness.index'), 'production.view', $user),
            ],
        ];

        $stages = array_map(fn (array $stage): array => self::stageWithProgress($stage), $stages);
        $flat = collect($stages)->pluck('items')->flatten(1);
        $complete = $flat->where('complete', true)->count();
        $total = $flat->count();
        $next = $flat->firstWhere('complete', false);

        return [
            'organization' => $organization,
            'stages' => $stages,
            'summary' => [
                'total' => $total,
                'complete' => $complete,
                'remaining' => max(0, $total - $complete),
                'percent' => $total > 0 ? (int) round(($complete / $total) * 100) : 100,
                'next' => $next,
            ],
            'enabled_modules' => $organization
                ? $organization->modules->where('is_enabled', true)->pluck('module_key')->values()->all()
                : [],
        ];
    }

    private static function item(bool $complete, string $title, string $description, string $metric, string $action, string $url, ?string $permission = null, ?User $user = null): array
    {
        $allowed = ! $permission || ! $user || $user->hasPermission($permission);

        return [
            'complete' => $complete,
            'title' => $title,
            'description' => $description,
            'metric' => $metric,
            'action' => $allowed ? $action : 'Needs access',
            'url' => $allowed ? $url : null,
        ];
    }

    private static function stageWithProgress(array $stage): array
    {
        $items = collect($stage['items']);
        $total = $items->count();
        $complete = $items->where('complete', true)->count();

        return $stage + [
            'total' => $total,
            'complete' => $complete,
            'percent' => $total > 0 ? (int) round(($complete / $total) * 100) : 100,
        ];
    }

    private static function countLabel(int $count, string $single, string $plural): string
    {
        return $count . ' ' . ($count === 1 ? $single : $plural);
    }
}
