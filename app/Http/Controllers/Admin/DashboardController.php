<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetRequest;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRegularizationRequest;
use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeProfile;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\MaintenanceRecord;
use App\Models\PayrollRun;
use App\Models\PurchaseOrder;
use App\Models\Software;
use App\Models\SoftwareAssignment;
use App\Models\SoftwareLicense;
use App\Models\Ticket;
use App\Models\Supplier;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $orgId = $user->organization_id;
        $organization = $user->organization?->load('modules');
        $moduleEnabled = fn (string $module): bool => !$organization || $organization->hasModule($module);

        $stats = [
            'total_assets'       => Asset::where('organization_id', $orgId)->count(),
            'available_assets'   => Asset::where('organization_id', $orgId)->where('status', 'available')->count(),
            'assigned_assets'    => Asset::where('organization_id', $orgId)->where('status', 'assigned')->count(),
            'maintenance_assets' => Asset::where('organization_id', $orgId)->whereIn('status', ['maintenance', 'repair'])->count(),
            'total_suppliers'    => Supplier::where('organization_id', $orgId)->count(),
            'pending_pos'        => PurchaseOrder::where('organization_id', $orgId)->whereIn('status', ['draft', 'sent'])->count(),
            'pending_requests'   => AssetRequest::where('organization_id', $orgId)->where('status', 'pending')->count(),
            'upcoming_maintenance' => MaintenanceRecord::whereHas('asset', fn($q) => $q->where('organization_id', $orgId))
                ->where('status', 'scheduled')
                ->where('scheduled_date', '>=', now())
                ->where('scheduled_date', '<=', now()->addDays(30))
                ->count(),
            'total_asset_value'   => Asset::where('organization_id', $orgId)->sum('purchase_price'),
            'overdue_invoices'    => Invoice::where('organization_id', $orgId)->where('status', 'overdue')->count(),
            'open_tickets'        => Ticket::where('organization_id', $orgId)->where('status', 'open')->count(),
            'in_progress_tickets' => Ticket::where('organization_id', $orgId)->where('status', 'in_progress')->count(),
            'urgent_tickets'      => Ticket::where('organization_id', $orgId)->whereIn('status', ['open','in_progress'])->where('priority', 'urgent')->count(),
            // SAM
            'software_titles'     => Software::where('organization_id', $orgId)->count(),
            'active_licenses'     => SoftwareLicense::where('organization_id', $orgId)->where('status','active')->count(),
            'expiring_licenses'   => SoftwareLicense::where('organization_id', $orgId)->where('status','active')
                                        ->whereNotNull('expiry_date')
                                        ->where('expiry_date', '<=', now()->addDays(30))
                                        ->where('expiry_date', '>', now())
                                        ->count(),
            'assigned_software'   => SoftwareAssignment::whereHas('license', fn($q) =>
                                        $q->where('organization_id', $orgId)
                                      )->where('status','active')->count(),
        ];

        $hrmsStats = [
            'employees' => EmployeeProfile::where('organization_id', $orgId)->count(),
            'active_employees' => EmployeeProfile::where('organization_id', $orgId)
                ->whereIn('employment_status', ['active', 'probation', 'notice'])
                ->count(),
            'present_today' => AttendanceRecord::where('organization_id', $orgId)
                ->whereDate('attendance_date', now()->toDateString())
                ->whereNotNull('sign_in_at')
                ->count(),
            'pending_leaves' => LeaveRequest::where('organization_id', $orgId)->where('status', 'pending')->count(),
            'pending_regularizations' => AttendanceRegularizationRequest::where('organization_id', $orgId)->where('status', 'pending')->count(),
            'pending_documents' => EmployeeDocumentRequest::where('organization_id', $orgId)->whereIn('status', ['pending', 'submitted'])->count(),
        ];

        $latestPayrollRun = PayrollRun::where('organization_id', $orgId)->latest('month')->first();
        $payrollStats = [
            'latest_run' => $latestPayrollRun,
            'latest_net' => (float) ($latestPayrollRun?->total_net ?? 0),
            'latest_employee_count' => (int) ($latestPayrollRun?->employee_count ?? 0),
            'draft_runs' => PayrollRun::where('organization_id', $orgId)->where('status', 'draft')->count(),
            'approved_runs' => PayrollRun::where('organization_id', $orgId)->where('status', 'approved')->count(),
        ];

        $samStats = [
            'titles' => $stats['software_titles'],
            'active_licenses' => $stats['active_licenses'],
            'assigned' => $stats['assigned_software'],
            'expiring' => $stats['expiring_licenses'],
            'available_seats' => SoftwareLicense::where('organization_id', $orgId)
                ->where('status', 'active')
                ->get()
                ->sum(fn (SoftwareLicense $license) => max(0, $license->available_seats)),
        ];

        $itamStats = [
            'assets' => $stats['total_assets'],
            'assigned' => $stats['assigned_assets'],
            'available' => $stats['available_assets'],
            'suppliers' => $stats['total_suppliers'],
            'pending_requests' => $stats['pending_requests'],
            'pending_pos' => $stats['pending_pos'],
            'maintenance_due' => $stats['upcoming_maintenance'],
            'asset_value' => (float) $stats['total_asset_value'],
        ];

        $supportStats = [
            'open' => $stats['open_tickets'],
            'in_progress' => $stats['in_progress_tickets'],
            'urgent' => $stats['urgent_tickets'],
            'resolved_this_month' => Ticket::where('organization_id', $orgId)
                ->where('status', 'resolved')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
        ];

        $moduleCards = [
            'hrms' => [
                'enabled' => $moduleEnabled('hrms'),
                'title' => 'HRMS',
                'icon' => 'bi-person-vcard-fill',
                'route' => 'admin.hrms.dashboard',
                'primary' => $hrmsStats['active_employees'],
                'primary_label' => 'active employees',
                'secondary' => $hrmsStats['pending_leaves'] + $hrmsStats['pending_regularizations'] + $hrmsStats['pending_documents'],
                'secondary_label' => 'HR actions pending',
                'color' => 'green',
            ],
            'itam' => [
                'enabled' => $moduleEnabled('itam'),
                'title' => 'ITAM',
                'icon' => 'bi-box-seam-fill',
                'route' => 'admin.assets.index',
                'primary' => $itamStats['assets'],
                'primary_label' => 'assets tracked',
                'secondary' => $itamStats['pending_requests'] + $itamStats['pending_pos'] + $itamStats['maintenance_due'],
                'secondary_label' => 'asset actions pending',
                'color' => 'blue',
            ],
            'sam' => [
                'enabled' => $moduleEnabled('sam'),
                'title' => 'SAM',
                'icon' => 'bi-display-fill',
                'route' => 'admin.software.index',
                'primary' => $samStats['titles'],
                'primary_label' => 'software titles',
                'secondary' => $samStats['expiring'],
                'secondary_label' => 'licenses expiring soon',
                'color' => 'purple',
            ],
            'payroll' => [
                'enabled' => $moduleEnabled('payroll'),
                'title' => 'Payroll',
                'icon' => 'bi-cash-stack',
                'route' => 'admin.payroll.index',
                'primary' => $payrollStats['latest_employee_count'],
                'primary_label' => 'employees in latest run',
                'secondary' => $payrollStats['draft_runs'] + $payrollStats['approved_runs'],
                'secondary_label' => 'runs needing action',
                'color' => 'amber',
            ],
            'support' => [
                'enabled' => $moduleEnabled('support'),
                'title' => 'Support',
                'icon' => 'bi-headset',
                'route' => 'admin.tickets.index',
                'primary' => $supportStats['open'] + $supportStats['in_progress'],
                'primary_label' => 'active tickets',
                'secondary' => $supportStats['urgent'],
                'secondary_label' => 'urgent tickets',
                'color' => 'red',
            ],
        ];

        $recentAssets = Asset::where('organization_id', $orgId)
            ->with(['category', 'supplier'])
            ->latest()->take(5)->get();

        $recentRequests = AssetRequest::where('organization_id', $orgId)
            ->with('requester')
            ->latest()->take(5)->get();

        $upcomingMaintenance = MaintenanceRecord::whereHas('asset', fn($q) => $q->where('organization_id', $orgId))
            ->with('asset')
            ->where('status', 'scheduled')
            ->where('scheduled_date', '>=', now())
            ->orderBy('scheduled_date')
            ->take(5)->get();

        $assetsByStatus = Asset::where('organization_id', $orgId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $monthlyPurchases = PurchaseOrder::where('organization_id', $orgId)
            ->selectRaw('MONTH(order_date) as month, SUM(total_amount) as total')
            ->whereYear('order_date', now()->year)
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $purchaseChartData = [];
        for ($i = 1; $i <= 12; $i++) {
            $purchaseChartData[] = $monthlyPurchases[$i] ?? 0;
        }

        $recentTickets = Ticket::where('organization_id', $orgId)
            ->with('requester')
            ->whereIn('status', ['open', 'in_progress'])
            ->latest()->take(6)->get();

        $pendingLeaves = LeaveRequest::where('organization_id', $orgId)
            ->with('employee.user')
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        $recentPayrollRuns = PayrollRun::where('organization_id', $orgId)
            ->latest('month')
            ->take(4)
            ->get();

        return view('admin.dashboard', compact(
            'stats', 'recentAssets', 'recentRequests',
            'upcomingMaintenance', 'assetsByStatus', 'purchaseChartData',
            'recentTickets', 'organization', 'moduleCards', 'hrmsStats',
            'payrollStats', 'samStats', 'itamStats', 'supportStats',
            'pendingLeaves', 'recentPayrollRuns'
        ));
    }
}
